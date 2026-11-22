// Offline integration test of the local Sepolia-compatible network (scripts/sepolia-local.mjs).
// It starts a throwaway network in a temporary directory and on its own port, deploys the real contract through
// the same bridge the application uses, and checks registration, confirmation, the isolation of the two
// environments, an outage, and the exact reconstruction of the chain after a restart.
import { spawn } from 'node:child_process';
import { createHash, randomBytes } from 'node:crypto';
import { appendFileSync, existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createServer } from 'node:net';
import assert from 'node:assert/strict';
import { ContractFactory, JsonRpcProvider, Wallet } from 'ethers';
import { execute } from '../runtime/driver.mjs';

const simulator = fileURLToPath(new URL('./sepolia-local.mjs', import.meta.url));
const data = mkdtempSync(join(tmpdir(), 'espacio-u-sepolia-local-'));
const port = await new Promise(resolve => { const probe = createServer(); probe.listen(0, '127.0.0.1', () => { const free = probe.address().port; probe.close(() => resolve(free)); }); });
const settings = { ...process.env, SEPOLIA_LOCAL_DATA: data, SEPOLIA_LOCAL_PORT: String(port) };
const cli = (...args) => new Promise(resolve => {
  const child = spawn(process.execPath, [simulator, ...args], { env: settings, windowsHide: true, stdio: ['ignore', 'pipe', 'ignore'] });
  let out = '';
  child.stdout.on('data', chunk => out += chunk);
  child.on('exit', code => { try { resolve({ code, ...JSON.parse(out.trim().split('\n').at(-1) || '{}') }); } catch { resolve({ code }); } });
});
let server;
async function start() {
  server = spawn(process.execPath, [simulator, 'serve'], { env: settings, windowsHide: true, stdio: 'ignore' });
  for (let attempt = 0; attempt < 120; attempt++) {
    const status = await cli('status');
    if (status.running) return status;
    await new Promise(resolve => setTimeout(resolve, 250));
  }
  throw new Error('START');
}
const stop = async () => {
  // Subscribed before asking: the network may exit before the stop command returns.
  const exited = new Promise(resolve => server.once('exit', resolve));
  assert.equal((await cli('stop')).code, 0);
  await exited;
};

try {
  let status = await start();
  assert.match(status.client, /^EspacioU-SepoliaLocal\//);
  assert.equal(status.chainId, 11155111);
  assert.equal(status.block, 0);
  const state = JSON.parse(readFileSync(join(data, 'state.json'), 'utf8'));
  const rpc = `http://127.0.0.1:${port}/${state.rpcPath}`;

  // The network is private: unknown paths, cross-site requests and control methods are refused.
  const post = (url, method, headers = {}) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', ...headers }, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method, params: [] }) });
  assert.equal((await post(`http://127.0.0.1:${port}/`, 'eth_chainId')).status, 404);
  assert.equal((await post(rpc, 'eth_chainId', { Origin: 'https://example.org' })).status, 404);
  for (const method of ['eth_accounts', 'eth_sendTransaction', 'hardhat_setBalance', 'evm_setNextBlockTimestamp', 'hardhat_mine', 'sim_setTime', 'sim_stop']) {
    assert.ok((await (await post(rpc, method)).json()).error, method);
  }
  assert.equal((await (await post(rpc, 'eth_chainId')).json()).result, '0xaa36a7');

  // A throwaway writer funded by nobody cannot deploy: only Hardhat's development account holds test funds here.
  const provider = new JsonRpcProvider(rpc, 11155111, { staticNetwork: true, batchMaxCount: 1 });
  const artifact = JSON.parse(readFileSync(new URL('../artifacts/contracts/TraceabilityRegistry.sol/TraceabilityRegistry.json', import.meta.url), 'utf8'));
  await assert.rejects(new ContractFactory(artifact.abi, artifact.bytecode, new Wallet('0x' + randomBytes(32).toString('hex'), provider)).deploy('0x' + '1'.repeat(40)));
  provider.destroy();

  // Deploy the real contract on the throwaway network with Hardhat's development account.
  const { HDNodeWallet } = await import('ethers');
  const { network } = await import('hardhat');
  const connection = await network.create('sepoliaLocal');
  const accounts = connection.networkConfig.accounts;
  const read = async value => typeof value?.get === 'function' ? value.get() : value;
  const writer = HDNodeWallet.fromPhrase(await read(accounts.mnemonic), await read(accounts.passphrase) ?? '', `${accounts.path}/${accounts.initialIndex}`);
  await connection.close();
  const env = { BLOCKCHAIN_ENVIRONMENT: 'local-simulation', BLOCKCHAIN_CHAIN_ID: '11155111', BLOCKCHAIN_RPC_URL: rpc, BLOCKCHAIN_PRIVATE_KEY: writer.privateKey, BLOCKCHAIN_CONFIRMATIONS: '1', BLOCKCHAIN_DEPLOYMENT_BLOCK: '0' };
  const deployer = new JsonRpcProvider(rpc, 11155111, { staticNetwork: true, batchMaxCount: 1 });
  const registry = await new ContractFactory(artifact.abi, artifact.bytecode, new Wallet(writer.privateKey, deployer)).deploy(writer.address);
  const receipt = await registry.deploymentTransaction().wait();
  deployer.destroy();
  env.BLOCKCHAIN_CONTRACT_ADDRESS = (await registry.getAddress()).toLowerCase();
  env.BLOCKCHAIN_DEPLOYMENT_BLOCK = String(receipt.blockNumber);

  // Registration and confirmation through the application's bridge.
  const hash = createHash('sha256').update('local simulation: first evidence').digest('hex');
  assert.equal((await execute({ action: 'find', hash }, env)).status, 'ABSENT');
  const sent = await execute({ action: 'register', hash }, env);
  assert.equal(sent.status, 'PENDING');
  const confirmed = await execute({ action: 'find', hash, reference: sent.reference }, env);
  assert.equal(confirmed.status, 'CONFIRMED');
  assert.equal(confirmed.confirmations, 1);
  assert.equal(confirmed.block, receipt.blockNumber + 1);
  // The two environments are never interchangeable, in either direction.
  await assert.rejects(execute({ action: 'find', hash }, { ...env, BLOCKCHAIN_ENVIRONMENT: 'public' }));
  await assert.rejects(execute({ action: 'find', hash }, { ...env, BLOCKCHAIN_ENVIRONMENT: 'public', BLOCKCHAIN_RPC_URL: rpc.replace('http:', 'https:') }));

  // Simulated clock: only the private controls can set it, and block timestamps follow it.
  const past = Math.floor(Date.now() / 1000) + 3600;
  assert.equal((await cli('time', String(past))).clock, 'simulated');
  const later = createHash('sha256').update('local simulation: clock').digest('hex');
  const dated = await execute({ action: 'find', hash: later, reference: (await execute({ action: 'register', hash: later }, env)).reference }, env);
  assert.ok(dated.timestamp >= past && dated.timestamp < past + 30);
  assert.equal((await cli('time', 'real')).clock, 'real');

  // Outage: nothing is registered and nothing is claimed while the network is down.
  status = await cli('status');
  await stop();
  assert.equal((await cli('status')).running, false);
  const during = createHash('sha256').update('local simulation: during the outage').digest('hex');
  await assert.rejects(execute({ action: 'register', hash: during }, env));
  await assert.rejects(execute({ action: 'find', hash, reference: sent.reference }, env));

  // Restart: the chain is rebuilt from the journal with the same transactions, blocks and timestamps.
  const restarted = await start();
  assert.equal(restarted.block, status.block);
  assert.equal(restarted.transactions, status.transactions);
  // Same transaction, block and timestamp as before the restart; only the number of confirmations grows.
  assert.deepEqual(await execute({ action: 'find', hash, reference: sent.reference }, env), { ...confirmed, confirmations: 2 });
  assert.deepEqual(await execute({ action: 'find', hash: later }, env), dated);
  // Reconciliation after the outage: the pending evidence is registered now.
  assert.equal((await execute({ action: 'find', hash: during }, env)).status, 'ABSENT');
  const recovered = await execute({ action: 'register', hash: during }, env);
  assert.equal((await execute({ action: 'find', hash: during, reference: recovered.reference }, env)).status, 'CONFIRMED');

  // The journal keeps exactly what the chain holds. A signed transaction sent twice is one transaction…
  const reader = new JsonRpcProvider(rpc, 11155111, { staticNetwork: true, batchMaxCount: 1, cacheTimeout: -1 });
  const fees = await reader.getFeeData();
  const sign = async fields => new Wallet(writer.privateKey).signTransaction({ chainId: 11155111, type: 2, value: 0n, gasLimit: 200000n, maxFeePerGas: fees.maxFeePerGas,
    maxPriorityFeePerGas: fees.maxPriorityFeePerGas, nonce: await reader.getTransactionCount(writer.address), ...fields });
  const broadcast = async raw => (await (await fetch(rpc, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'eth_sendRawTransaction', params: [raw] }) })).json());
  const repeated = createHash('sha256').update('local simulation: sent twice').digest('hex');
  const twice = await sign({ to: env.BLOCKCHAIN_CONTRACT_ADDRESS, data: registry.interface.encodeFunctionData('registerEvidence', ['0x' + repeated]) });
  const first = await broadcast(twice);
  const height = (await cli('status')).block;
  assert.deepEqual(await broadcast(twice), first);
  assert.equal((await cli('status')).block, height);
  // …and a transaction whose execution reverts is mined, as on a public network, and stays in the chain.
  const reverted = await broadcast(await sign({ to: null, data: '0x60006000fd' }));
  assert.match(reverted.result, /^0x[0-9a-f]{64}$/);
  assert.equal((await reader.getTransactionReceipt(reverted.result)).status, 0);
  const following = createHash('sha256').update('local simulation: after a reverted transaction').digest('hex');
  const followed = await execute({ action: 'register', hash: following }, env);
  reader.destroy();
  status = await cli('status');
  assert.equal(status.block, height + 2);
  assert.equal((await cli('reset')).code, 1);   // never emptied while running
  await stop();
  // A power failure in the middle of an append leaves an unfinished last line: it is discarded, nothing else is lost.
  appendFileSync(join(data, 'journal.jsonl'), '{"raw":"0x02f8');
  const rebuilt = await start();
  assert.equal(rebuilt.block, status.block);
  assert.equal(rebuilt.transactions, status.transactions);
  assert.ok(readFileSync(join(data, 'journal.jsonl'), 'utf8').endsWith('}\n'));
  assert.equal((await execute({ action: 'find', hash: repeated }, env)).status, 'CONFIRMED');
  assert.equal((await execute({ action: 'find', hash: following, reference: followed.reference }, env)).status, 'CONFIRMED');
  // The clock never moves the chain back: asking for a time before its last block is answered as such.
  assert.equal((await cli('time', '1700000000')).chainAhead, true);
  assert.equal((await cli('time', 'real')).clock, 'real');
  assert.equal((await cli('time', 'yesterday')).code, 1);

  // Each deployment command names its network and refuses a configuration that points elsewhere, before signing.
  const deployment = fileURLToPath(new URL('./deploy.mjs', import.meta.url));
  const deploy = (expected, environment) => new Promise(resolve => {
    const child = spawn(process.execPath, [deployment, ...expected], { env: { ...process.env, ...env, BLOCKCHAIN_CONTRACT_ADDRESS: '', BLOCKCHAIN_NETWORK: 'sepolia', BLOCKCHAIN_ENVIRONMENT: environment }, windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
    let err = '';
    child.stderr.on('data', chunk => err += chunk);
    child.on('exit', code => resolve({ code, refused: err.startsWith('DEPLOYMENT_REFUSED') }));
  });
  for (const [expected, environment] of [[['local-simulation'], 'public'], [['public'], 'local-simulation'], [[], 'local-simulation'], [[], 'public']]) {
    assert.deepEqual(await deploy(expected, environment), { code: 1, refused: true });
  }
  assert.equal((await cli('status')).block, status.block);

  // Inside the repository, the private data of the network only lives in the ignored runtime-data directory.
  const misplaced = fileURLToPath(new URL('./sepolia-local-data-test/', import.meta.url));
  const refused = await new Promise(resolve => spawn(process.execPath, [simulator, 'status'], { env: { ...settings, SEPOLIA_LOCAL_DATA: misplaced }, windowsHide: true, stdio: 'ignore' }).on('exit', resolve));
  assert.equal(refused, 1);
  assert.equal(existsSync(misplaced), false);
  const dotted = fileURLToPath(new URL('../../..sepolia-local-data-test/', import.meta.url));
  assert.equal(await new Promise(resolve => spawn(process.execPath, [simulator, 'status'], { env: { ...settings, SEPOLIA_LOCAL_DATA: dotted }, windowsHide: true, stdio: 'ignore' }).on('exit', resolve)), 1);
  assert.equal(existsSync(dotted), false);
  // Starting a second network on the same data, or emptying the chain, fails while the first one holds the port.
  assert.equal(await new Promise(resolve => spawn(process.execPath, [simulator, 'serve'], { env: settings, windowsHide: true, stdio: 'ignore' }).on('exit', resolve)), 1);
  assert.equal((await cli('status')).block, status.block);
  await stop();

  // A journal that no longer describes the recorded history is refused: same transaction and height on another
  // timestamp would be a different block and a different registration date.
  const journal = join(data, 'journal.jsonl');
  const recorded = readFileSync(journal, 'utf8');
  const records = recorded.trimEnd().split('\n').map(line => JSON.parse(line));
  assert.deepEqual(records[0], { journal: 3 });
  const last = records.findLastIndex(record => typeof record.raw === 'string');
  writeFileSync(journal, records.map((record, index) => JSON.stringify(index === last ? { ...record, timestamp: record.timestamp + 1 } : record)).join('\n') + '\n');
  const serving = options => new Promise(resolve => spawn(process.execPath, [simulator, 'serve'], { env: settings, windowsHide: true, stdio: 'ignore', ...options }).on('exit', resolve));
  assert.equal(await serving(), 1);
  writeFileSync(journal, recorded);

  // The port is the lock, so the recorded port must be a real, fixed one.
  const stateFile = join(data, 'state.json');
  const kept = readFileSync(stateFile, 'utf8');
  writeFileSync(stateFile, JSON.stringify({ ...JSON.parse(kept), port: 0 }));
  assert.equal((await cli('reset')).code, 1);
  writeFileSync(stateFile, kept);
  assert.equal(readFileSync(journal, 'utf8'), recorded);

  // Emptying only ever touches the data of an existing network: a directory that never held one is left alone.
  const stranger = mkdtempSync(join(tmpdir(), 'espacio-u-not-a-network-'));
  try {
    writeFileSync(join(stranger, 'journal.jsonl'), 'unrelated');
    const code = await new Promise(resolve => spawn(process.execPath, [simulator, 'reset'], { env: { ...settings, SEPOLIA_LOCAL_DATA: stranger }, windowsHide: true, stdio: 'ignore' }).on('exit', resolve));
    assert.equal(code, 1);
    assert.equal(readFileSync(join(stranger, 'journal.jsonl'), 'utf8'), 'unrelated');
    assert.equal(existsSync(join(stranger, 'state.json')), false);
  } finally { rmSync(stranger, { recursive: true, force: true }); }

  assert.deepEqual(await cli('reset'), { code: 0, chain: 'empty' });
  assert.equal(existsSync(journal), false);
  console.log('PASS red local compatible con Sepolia: privacidad del RPC, despliegue, registro y confirmación, entornos no intercambiables, reloj, caída, reconstrucción exacta, reconciliación, reenvíos, reversiones, diario interrumpido o alterado, bloqueo por puerto y órdenes de despliegue atadas a su entorno.');
} catch (error) {
  // Only the failed expectation and its line: the checked values are public test data.
  const line = String(error?.stack ?? '').match(/integration-sepolia-local\.mjs:(\d+)/)?.[1];
  console.error('SEPOLIA_LOCAL_INTEGRATION_FAILED' + (error?.code === 'ERR_ASSERTION' ? `: línea ${line} · ${String(error.message).replace(/\s+/g, ' ').slice(0, 200)}` : ''));
  process.exitCode = 1;
} finally {
  try { server?.kill(); } catch { /* already stopped */ }
  rmSync(data, { recursive: true, force: true });
}
