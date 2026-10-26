import { dedicatedWallet, save } from './local-environment.mjs';
import { fileURLToPath } from 'node:url';
try {
  const wallet = await dedicatedWallet();
  await save({BLOCKCHAIN_NODE_BINARY:fileURLToPath(new URL('../../.tools/node/node-v24.22.1-win-x64/node.exe',import.meta.url)).replaceAll('\\','/')});
  console.log(JSON.stringify({ network: 'sepolia', chainId: 11155111, publicAddress: wallet.address }));
} catch { console.error('No se pudo preparar la wallet local.'); process.exitCode = 1; }
