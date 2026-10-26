import { ContractFactory, Wallet, parseUnits } from 'ethers';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { connect } from '../runtime/driver.mjs';
import { environment, save } from './local-environment.mjs';
let provider;
try {
  const env = await environment();
  if (env.BLOCKCHAIN_NETWORK !== 'sepolia' || env.BLOCKCHAIN_CONTRACT_ADDRESS) throw new Error('CONFIG');
  provider = await connect(env);
  const wallet = new Wallet(env.BLOCKCHAIN_PRIVATE_KEY, provider);
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
  await save({ BLOCKCHAIN_CONTRACT_ADDRESS: address, BLOCKCHAIN_DEPLOYMENT_BLOCK: String(receipt.blockNumber) });
  const metadata = {network:'sepolia',chainId:11155111,address,writer:wallet.address,transaction:receipt.hash,block:receipt.blockNumber};
  await mkdir(new URL('../deployments/',import.meta.url),{recursive:true});
  await writeFile(new URL('../deployments/sepolia.json',import.meta.url),JSON.stringify(metadata,null,2)+'\n');
  console.log(JSON.stringify(metadata));
} catch { console.error('DEPLOYMENT_NOT_CONFIRMED: comprobar configuración, saldo de test ETH y RPC; no se habilitó la integración.'); process.exitCode=1; }
finally { provider?.destroy(); }
