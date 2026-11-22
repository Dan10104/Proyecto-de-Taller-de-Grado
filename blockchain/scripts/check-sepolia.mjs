import { connect, environmentOf, signer } from '../runtime/driver.mjs';
import { environment, save } from './local-environment.mjs';
import { formatEther } from 'ethers';
const env = await environment();
// Public fallback, replaceable through local configuration; no credentials in source.
const rpc = env.BLOCKCHAIN_RPC_URL || 'https://ethereum-sepolia-rpc.publicnode.com';
let provider;
try {
  // Check of the public testnet only: it never reports the local simulation as «sepolia».
  if (environmentOf(env) !== 'public') throw new Error('ENVIRONMENT');
  provider = await connect({...env, BLOCKCHAIN_RPC_URL:rpc});
  const wallet = signer(env);
  const balance = await provider.getBalance(wallet.address);
  if (!env.BLOCKCHAIN_RPC_URL) await save({BLOCKCHAIN_RPC_URL:rpc});
  console.log(JSON.stringify({network:'sepolia',chainId:11155111,publicAddress:wallet.address,testEth:formatEther(balance),rpcAvailable:true}));
} catch { console.log(JSON.stringify({network:'sepolia',rpcAvailable:false})); process.exitCode=1; }
finally { provider?.destroy(); }
