import { dedicatedWallet, environment, save } from './local-environment.mjs';
import { environmentOf, DEVELOPMENT_SIGNER } from '../runtime/driver.mjs';
import { fileURLToPath } from 'node:url';
try {
  // The dedicated wallet belongs to the public testnet; the writer of the local simulation is never offered as such.
  if (environmentOf(await environment()) !== 'public') throw new Error('ENVIRONMENT');
  const wallet = await dedicatedWallet();
  // The development account of the simulation has a publicly known key: it is never offered as a wallet to fund.
  if (wallet.address.toLowerCase() === DEVELOPMENT_SIGNER) throw new Error('DEVELOPMENT_SIGNER');
  await save({BLOCKCHAIN_NODE_BINARY:fileURLToPath(new URL('../../.tools/node/node-v24.22.1-win-x64/node.exe',import.meta.url)).replaceAll('\\','/')});
  console.log(JSON.stringify({ network: 'sepolia', chainId: 11155111, publicAddress: wallet.address }));
} catch { console.error('No se pudo preparar la wallet local.'); process.exitCode = 1; }
