import { ContractFactory, parseUnits } from 'ethers';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { connect, environmentOf, signer, ENVIRONMENTS, DEVELOPMENT_SIGNER } from '../runtime/driver.mjs';
import { environment, save, stored, presentation, mirrorSimulation } from './local-environment.mjs';
// Usage: node scripts/deploy.mjs <public|local-simulation>
// The caller names the network it means to deploy to. If the configuration points anywhere else, nothing is
// signed: a local deployment can never reach the public testnet, nor the reverse.
const expected = process.argv[2];
let provider;
try {
  if (!ENVIRONMENTS.includes(expected)) throw new Error('ENVIRONMENT');
  const env = await environment();
  if (environmentOf(env) !== expected) throw new Error('ENVIRONMENT');
  // The result is written to .env: that file itself must describe the same environment and have no deployment
  // yet, whatever the variables of this process say. A local deployment is only ever recorded in a
  // presentation environment.
  const destination = await stored();
  const simulated = expected === 'local-simulation';
  if (!existsSync(new URL('../../.env', import.meta.url)) || environmentOf(destination) !== expected || destination.BLOCKCHAIN_CONTRACT_ADDRESS || (simulated && !presentation())) throw new Error('ENVIRONMENT');
  // The record of a local deployment lives with the chain of the project's own network, never with a throwaway one.
  if (simulated && process.env.SEPOLIA_LOCAL_DATA) throw new Error('ENVIRONMENT');
  if (env.BLOCKCHAIN_NETWORK !== 'sepolia' || env.BLOCKCHAIN_CONTRACT_ADDRESS) throw new Error('CONFIG');
  provider = await connect(env);
  const wallet = signer(env, provider);
  // The simulation has one writer, the development account. A wallet that could also deploy on the public
  // testnet never deploys here, so a contract address cannot exist in both environments.
  if (simulated && wallet.address.toLowerCase() !== DEVELOPMENT_SIGNER) throw new Error('ENVIRONMENT');
  const artifact = JSON.parse(await readFile(new URL('../artifacts/contracts/TraceabilityRegistry.sol/TraceabilityRegistry.json', import.meta.url), 'utf8'));
  const factory = new ContractFactory(artifact.abi, artifact.bytecode, wallet);
  const request = await factory.getDeployTransaction(wallet.address);
  const [gas, fees, balance, nonce] = await Promise.all([provider.estimateGas({...request, from: wallet.address}), provider.getFeeData(), provider.getBalance(wallet.address), provider.getTransactionCount(wallet.address, 'pending')]);
  const gasLimit = gas * 120n / 100n;
  if (!fees.maxFeePerGas || fees.maxPriorityFeePerGas == null || fees.maxFeePerGas > parseUnits('100','gwei') || gasLimit > 3000000n || balance < gasLimit * fees.maxFeePerGas || gasLimit * fees.maxFeePerGas > parseUnits('0.1','ether')) throw new Error('INSUFFICIENT_TEST_ETH_OR_FEES');
  const deployed = await factory.deploy(wallet.address, {type:2, nonce, gasLimit, maxFeePerGas:fees.maxFeePerGas, maxPriorityFeePerGas:fees.maxPriorityFeePerGas});
  // Only public metadata is emitted, even if receipt confirmation later fails.
  console.log(JSON.stringify({ transaction: deployed.deploymentTransaction().hash, predictedAddress: await deployed.getAddress() }));
  const receipt = await deployed.deploymentTransaction().wait(Math.max(1, Number(env.BLOCKCHAIN_CONFIRMATIONS ?? 1)), 120000);
  if (!receipt || receipt.status !== 1) throw new Error('RECEIPT');
  const address = (await deployed.getAddress()).toLowerCase();
  // Same deployment for both environments; only the record of where it happened differs. The local simulation
  // keeps its record with the rest of its private runtime data, outside the repository.
  // The confirmed deployment is recorded for its own chain first, whatever happens to the configuration.
  const metadata = {network:simulated?'sepolia-local':'sepolia',environment:expected,chainId:11155111,address,writer:wallet.address,transaction:receipt.hash,block:receipt.blockNumber};
  const record = new URL(simulated ? '../runtime-data/sepolia-local/deployment.json' : '../deployments/sepolia.json', import.meta.url);
  await mkdir(new URL('./',record),{recursive:true});
  await writeFile(record,JSON.stringify(metadata,null,2)+'\n');
  console.log(JSON.stringify(metadata));
  // .env may have been reconfigured while the receipt was awaited: the address is written only if the file still
  // describes the network that was deployed to and still has no deployment.
  const current = await stored();
  if (environmentOf(current) !== expected || current.BLOCKCHAIN_CONTRACT_ADDRESS) throw new Error('DESTINATION_CHANGED');
  const settings = { BLOCKCHAIN_CONTRACT_ADDRESS: address, BLOCKCHAIN_DEPLOYMENT_BLOCK: String(receipt.blockNumber) };
  if (simulated) {
    // A contract of the local simulation stays known as such, whatever is configured later.
    const known = String(current.BLOCKCHAIN_SIMULATION_CONTRACTS ?? '').split(',').map(value => value.trim().toLowerCase()).filter(value => /^0x[0-9a-f]{40}$/.test(value));
    settings.BLOCKCHAIN_SIMULATION_CONTRACTS = [...new Set([...known, address])].join(',');
  }
  await save(settings);
  if (simulated) await mirrorSimulation();
} catch (error) {
  console.error(error?.message === 'DESTINATION_CHANGED'
    ? 'DEPLOYMENT_NOT_RECORDED: el contrato se desplegó y quedó anotado en su registro de despliegue, pero .env cambió mientras tanto y no se modificó.'
    : error?.message === 'ENVIRONMENT'
    ? 'DEPLOYMENT_REFUSED: la configuración no corresponde al entorno que se pidió desplegar; no se firmó nada.'
    : 'DEPLOYMENT_NOT_CONFIRMED: comprobar configuración, saldo de test ETH y RPC; no se habilitó la integración.');
  process.exitCode=1;
}
finally { provider?.destroy(); }
