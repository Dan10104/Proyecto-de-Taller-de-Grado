// Explicit offline integration command. Never exposes the Hardhat CLI/config parser or dev accounts.
import { network } from 'hardhat';
import { createServer } from 'node:http';
import { randomBytes, createHash } from 'node:crypto';
import { spawn } from 'node:child_process';
import { Wallet, ContractFactory, JsonRpcProvider, Transaction } from 'ethers';
import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
import { execute } from '../runtime/driver.mjs';

const connection = await network.create('hardhatMainnet');
const wallet = new Wallet('0x' + randomBytes(32).toString('hex'));
await connection.provider.request({method:'hardhat_setBalance',params:[wallet.address,'0x8ac7230489e80000']});
const secretPath = '/' + randomBytes(32).toString('hex');
const allowed = new Set(['eth_chainId','eth_blockNumber','eth_getCode','eth_call','eth_getBlockByNumber','eth_getLogs','eth_getTransactionByHash','eth_getTransactionReceipt','eth_getBalance','eth_estimateGas','eth_getTransactionCount','eth_sendRawTransaction','eth_gasPrice','eth_maxPriorityFeePerGas']);
let wrongChain = false;
let dropBroadcastResponse = false;
let receiptFault = null;
let businessBroadcasts = 0;
const gateway = createServer(async (req,res) => {
  if (req.method !== 'POST' || req.url !== secretPath) { res.writeHead(404).end(); return; }
  try {
    let body = '';
    for await (const part of req) { body += part; if (body.length > 32768) throw new Error('LIMIT'); }
    const input = JSON.parse(body);
    if (!allowed.has(input.method) || !Array.isArray(input.params)) { console.log('Rejected fixture RPC method: '+String(input.method).replace(/[^a-zA-Z0-9_]/g,'')); throw new Error('METHOD'); }
    if (input.method === 'eth_sendRawTransaction') {
      const tx = Transaction.from(input.params[0]);
      if (tx.to) { assert.equal(tx.data.length,74); assert.equal(tx.value,0n); assert.equal(tx.chainId,11155111n); businessBroadcasts++; }
    }
    let result = wrongChain && input.method === 'eth_chainId' ? '0x1' : await connection.provider.request({method:input.method,params:input.params});
    // Protocol fault injection only; never presented as a real reverted transaction/reorg.
    if (input.method === 'eth_getTransactionReceipt' && result && receiptFault === 'revert') result={...result,status:'0x0',logs:[]};
    if (input.method === 'eth_getTransactionReceipt' && result && receiptFault === 'reorg') result={...result,blockHash:'0x'+'d'.repeat(64)};
    if (dropBroadcastResponse && input.method === 'eth_sendRawTransaction') { dropBroadcastResponse=false; res.destroy(); return; }
    res.setHeader('Content-Type','application/json');
    res.end(JSON.stringify({jsonrpc:'2.0',id:input.id,result}));
  } catch { res.writeHead(400).end('{"error":"LOCAL_FIXTURE_REJECTED"}'); }
});
await new Promise(resolve => gateway.listen(0,'127.0.0.1',resolve));
const rpc = `http://127.0.0.1:${gateway.address().port}${secretPath}`;
const provider = new JsonRpcProvider(rpc,11155111,{staticNetwork:true,batchMaxCount:1});
try {
  const artifact = JSON.parse(await readFile(new URL('../artifacts/contracts/TraceabilityRegistry.sol/TraceabilityRegistry.json',import.meta.url),'utf8'));
  const registry = await new ContractFactory(artifact.abi,artifact.bytecode,wallet.connect(provider)).deploy(wallet.address);
  const receipt = await registry.deploymentTransaction().wait();
  const env = {...process.env,APP_ENV:'testing',APP_CONFIG_CACHE:'.local/testing/config.php',BLOCKCHAIN_ENABLED:'true',BLOCKCHAIN_NETWORK:'sepolia',BLOCKCHAIN_CHAIN_ID:'11155111',BLOCKCHAIN_PRIVATE_KEY:wallet.privateKey,BLOCKCHAIN_RPC_URL:rpc,BLOCKCHAIN_CONTRACT_ADDRESS:(await registry.getAddress()).toLowerCase(),BLOCKCHAIN_CONFIRMATIONS:'1',BLOCKCHAIN_DEPLOYMENT_BLOCK:String(receipt.blockNumber)};
  const php = new URL('../../.tools/php/php.exe',import.meta.url);
  const {fileURLToPath} = await import('node:url');
  const output = await new Promise((resolve,reject) => {
    const child = spawn(fileURLToPath(php),['tests/Support/blockchain-integration.php'],{cwd:fileURLToPath(new URL('../../',import.meta.url)),env,windowsHide:true,stdio:['ignore','pipe','pipe']});
    let stdout='';
    child.stdout.on('data',chunk=>stdout+=chunk);
    // Suppress arbitrary child diagnostics: they could contain environment/request values.
    child.stderr.resume();
    child.on('error',()=>reject(new Error('PHP_START_FAILED')));
    child.on('exit',code=>{
      try {
        const result=JSON.parse(stdout);
        if(code!==0){ console.log(JSON.stringify({stage:result.stage,class:result.class,line:result.line})); reject(new Error('PHP_INTEGRATION_FAILED')); }
        else resolve(result);
      } catch { reject(new Error('PHP_INTEGRATION_FAILED')); }
    });
  });
  assert.equal(output.status,'passed');
  console.log('PASS PHP → bridge ethers → contrato local: cuatro operaciones, confirmación y CU25.');
  const hash=createHash('sha256').update('isolated integration edge cases').digest('hex');
  assert.equal((await execute({action:'find',hash},env)).status,'ABSENT');
  const sent=await execute({action:'register',hash},env);
  assert.equal(sent.status,'PENDING');
  const confirmed=await execute({action:'find',hash,reference:sent.reference},env);
  assert.equal(confirmed.status,'CONFIRMED');
  assert.equal((await execute({action:'find',hash},env)).reference,sent.reference);
  assert.equal((await execute({action:'register',hash},env)).reference,sent.reference);
  assert.equal((await execute({action:'find',hash,reference:sent.reference},{...env,BLOCKCHAIN_CONFIRMATIONS:'5'})).status,'PENDING');
  receiptFault='revert';
  assert.equal((await execute({action:'find',hash,reference:sent.reference},env)).status,'FAILED');
  receiptFault='reorg';
  assert.equal((await execute({action:'find',hash,reference:sent.reference},env)).status,'PENDING');
  receiptFault=null;
  await assert.rejects(execute({action:'register',hash:'c'.repeat(64)},{...env,BLOCKCHAIN_PRIVATE_KEY:''}));
  await assert.rejects(execute({action:'find',hash},{...env,BLOCKCHAIN_RPC_URL:''}));
  assert.equal((await execute({action:'find',hash:'b'.repeat(64),reference:sent.reference},env)).status,'INCONSISTENT');
  assert.equal((await execute({action:'find',hash,reference:sent.reference.replace(env.BLOCKCHAIN_CONTRACT_ADDRESS,'0x'+'a'.repeat(40))},env)).status,'INCONSISTENT');
  await assert.rejects(execute({action:'register',hash:'c'.repeat(64)},{...env,BLOCKCHAIN_PRIVATE_KEY:'0x'+randomBytes(32).toString('hex')}));
  await assert.rejects(execute({action:'find',hash},{...env,BLOCKCHAIN_CONTRACT_ADDRESS:wallet.address}));
  const lostHash=createHash('sha256').update('accepted broadcast with lost response').digest('hex');
  dropBroadcastResponse=true;
  let candidate;
  const uncertain=await execute({action:'register',hash:lostHash},env, value=>candidate=value);
  assert.equal(uncertain.reference,candidate.reference);
  assert.equal(uncertain.status,'PENDING');
  const recovered=await execute({action:'find',hash:lostHash},env);
  assert.equal(recovered.status,'CONFIRMED');
  assert.equal(recovered.reference,candidate.reference);
  assert.equal(businessBroadcasts,6); // Four PHP actions plus two explicit JS cases, no duplicate sends.
  wrongChain=true;
  await assert.rejects(execute({action:'chain'},env));
  await assert.rejects(execute({action:'register',hash},env));
  await assert.rejects(execute({action:'find',hash},env));
  console.log('PASS recuperación sin referencia/respuesta, duplicado, confirmaciones, faults receipt/reorg, privacidad calldata, hash/contrato incorrectos, configuración, writer y chain guard.');
} catch (error) {
  console.error(['PHP_INTEGRATION_FAILED','PHP_START_FAILED'].includes(error.message)?error.message:'LOCAL_INTEGRATION_FAILED');
  process.exitCode=1;
} finally {
  provider.destroy();
  gateway.closeAllConnections();
  await new Promise(resolve=>gateway.close(resolve));
  await connection.close();
}
