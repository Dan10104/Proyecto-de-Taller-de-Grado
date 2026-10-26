import { connect } from '../runtime/driver.mjs';
import { environment, save } from './local-environment.mjs';
import { Wallet, formatEther } from 'ethers';
const env = await environment();
// Public fallback, replaceable through local configuration; no credentials in source.
const rpc = env.BLOCKCHAIN_RPC_URL || 'https://ethereum-sepolia-rpc.publicnode.com';
let provider;
try {
  provider = await connect({...env, BLOCKCHAIN_RPC_URL:rpc});
  const wallet = new Wallet(env.BLOCKCHAIN_PRIVATE_KEY);
  const balance = await provider.getBalance(wallet.address);
  if (!env.BLOCKCHAIN_RPC_URL) await save({BLOCKCHAIN_RPC_URL:rpc});
  console.log(JSON.stringify({network:'sepolia',chainId:11155111,publicAddress:wallet.address,testEth:formatEther(balance),rpcAvailable:true}));
} catch { console.log(JSON.stringify({network:'sepolia',rpcAvailable:false})); process.exitCode=1; }
finally { provider?.destroy(); }
