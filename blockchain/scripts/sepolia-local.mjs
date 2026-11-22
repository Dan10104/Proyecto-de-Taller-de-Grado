// Local Ethereum network compatible with Sepolia, for controlled validation and demonstration.
//
// It is a real EVM (Hardhat Network / EDR) running the same TraceabilityRegistry contract: it produces real
// blocks, transaction hashes, receipts, logs and confirmations. It is NOT the public testnet, and nothing here
// sends a transaction to the Internet.
//
// - Listens on 127.0.0.1 only, on a private path. Only read methods and eth_sendRawTransaction are served:
//   no development accounts, no eth_sendTransaction, no hardhat_*/evm_* methods on the public path.
// - The network identifies itself with web3_clientVersion «EspacioU-SepoliaLocal/…», so the driver can tell it
//   apart from public Sepolia; both use chain id 11155111.
// - Hardhat Network keeps its state in memory. To survive a restart, every accepted raw transaction is written
//   to a local journal together with its block timestamp and replayed, in order, on start. Replaying identical
//   signed transactions on identical timestamps yields the same transaction hashes, blocks and contract state.
// - The listening port is the lock of the data directory: the network takes it before reading the journal, and
//   emptying the chain takes it too. The operating system gives it to one process at a time.
// - Local data (journal, private paths, pid) lives in blockchain/runtime-data/, ignored by Git.
//
// Usage: node scripts/sepolia-local.mjs <serve|status|stop|time <unix|real>|reset|configure>
import { createServer } from 'node:http';
import { randomBytes } from 'node:crypto';
import { closeSync, existsSync, fsyncSync, mkdirSync, openSync, readFileSync, renameSync, rmSync, statSync, truncateSync, writeFileSync, writeSync } from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { isAbsolute, relative, resolve, sep } from 'node:path';
import { HDNodeWallet, Transaction } from 'ethers';
import { restrict } from './local-environment.mjs';

// Nothing leaves this machine, Hardhat's usage telemetry included. Set before Hardhat is loaded.
process.env.HARDHAT_DISABLE_TELEMETRY = 'true';

export const CLIENT = 'EspacioU-SepoliaLocal/1';
const CHAIN = 11155111;
const NETWORK = 'sepoliaLocal';
const JOURNAL = 3;
// SEPOLIA_LOCAL_DATA lets the automated test run its own throwaway network next to the real one. The private
// paths of the network must never land where Git could pick them up: inside the repository, only the ignored
// runtime-data directory is accepted.
const within = (parent, child) => { const path = relative(parent, child); return path === '' || (path.split(sep)[0] !== '..' && !isAbsolute(path)); };
const chosen = process.env.SEPOLIA_LOCAL_DATA ? resolve(process.env.SEPOLIA_LOCAL_DATA) : null;
const misplaced = chosen !== null && within(fileURLToPath(new URL('../../', import.meta.url)), chosen) && !within(fileURLToPath(new URL('../runtime-data/', import.meta.url)), chosen);
const directory = chosen ? pathToFileURL(chosen + '/') : new URL('../runtime-data/sepolia-local/', import.meta.url);
const files = { state: new URL('state.json', directory), journal: new URL('journal.jsonl', directory), pid: new URL('simulator.pid', directory), deployment: new URL('deployment.json', directory) };
const READ_METHODS = new Set(['eth_chainId', 'eth_blockNumber', 'eth_getCode', 'eth_call', 'eth_getBlockByNumber', 'eth_getBlockByHash', 'eth_getLogs', 'eth_getTransactionByHash',
  'eth_getTransactionReceipt', 'eth_getBalance', 'eth_estimateGas', 'eth_getTransactionCount', 'eth_gasPrice', 'eth_maxPriorityFeePerGas', 'eth_feeHistory', 'net_version']);
const now = () => Math.floor(Date.now() / 1000);
const pause = milliseconds => new Promise(done => setTimeout(done, milliseconds));

// With create = false, only the data of a network that already exists is used: nothing is created.
function state(create = true) {
  if (misplaced) throw new Error('DATA_DIRECTORY');
  if (!existsSync(files.state)) {
    if (!create) throw new Error('NO_DATA');
    const port = Number(process.env.SEPOLIA_LOCAL_PORT ?? 8545);
    if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('PORT');
    // The directory is restricted to the current user before anything private is written in it.
    mkdirSync(directory, { recursive: true });
    restrict(directory, true);
    // Private paths: other local programs and web pages cannot reach the network or its controls by guessing.
    // Created once: of several first callers, one writes the file and the others read it.
    try { writeFileSync(files.state, JSON.stringify({ version: 1, port, rpcPath: randomBytes(24).toString('hex'), adminPath: randomBytes(24).toString('hex') }, null, 2) + '\n', { mode: 0o600, flag: 'wx' }); }
    catch (error) { if (error.code !== 'EEXIST') throw error; }
  }
  let value;
  for (let attempt = 0; attempt < 20; attempt++) {
    try { value = JSON.parse(readFileSync(files.state, 'utf8')); break; } catch { Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 25); }
  }
  // The port is the lock of the data directory, so it must be one fixed, real port.
  if (value?.version !== 1 || !Number.isInteger(value.port) || value.port < 1024 || value.port > 65535 || !/^[0-9a-f]{48}$/.test(value.rpcPath) || !/^[0-9a-f]{48}$/.test(value.adminPath) || value.rpcPath === value.adminPath) throw new Error('STATE');
  return value;
}
export const rpcUrl = () => { const s = state(); return `http://127.0.0.1:${s.port}/${s.rpcPath}`; };

// A record is durable once its whole line, newline included, is on disk. An append interrupted by a power
// failure leaves an unfinished last line: it was never acknowledged to anyone, so it is discarded. Damage
// anywhere else is not repaired: the network refuses to start.
function journalEntries() {
  if (!existsSync(files.journal)) return [];
  const text = readFileSync(files.journal, 'utf8');
  const complete = text.slice(0, text.lastIndexOf('\n') + 1);
  if (complete.length !== text.length) truncateSync(files.journal, Buffer.byteLength(complete));
  const entries = complete.split('\n').filter(Boolean).map(line => JSON.parse(line));
  // The first record names the format. A journal written in another format is not interpreted.
  if (entries.length > 0 && entries[0].journal !== JOURNAL) throw new Error('JOURNAL_DOES_NOT_REPLAY');
  return entries.slice(1);
}
function append(entry) {
  // Durable before the caller hears about it: a restart never loses an accepted transaction.
  const records = (existsSync(files.journal) && statSync(files.journal).size > 0 ? '' : JSON.stringify({ journal: JOURNAL }) + '\n') + JSON.stringify(entry) + '\n';
  const record = Buffer.from(records);
  const descriptor = openSync(files.journal, 'a', 0o600);
  try {
    for (let written = 0; written < record.length;) written += writeSync(descriptor, record, written);
    fsyncSync(descriptor);
  } finally { closeSync(descriptor); }
}

// The process that holds the data directory, as far as its pid file tells. Informative only: what excludes two
// owners is the port.
const holder = () => {
  try {
    const pid = Number(readFileSync(files.pid, 'utf8'));
    if (!Number.isInteger(pid) || pid <= 0) return null;
    try { process.kill(pid, 0); return pid; } catch (error) { return error.code === 'EPERM' ? pid : null; }
  } catch { return null; }
};
const listen = (server, port) => new Promise((done, reject) => { server.once('error', reject); server.listen({ port, host: '127.0.0.1', exclusive: true }, done); });

async function admin(method, params = []) {
  const s = state();
  // The controls are offered only to the process that holds the data directory; with no holder, nothing is sent.
  if (holder() === null) throw new Error('STOPPED');
  const response = await fetch(`http://127.0.0.1:${s.port}/${s.adminPath}`, { method: 'POST', redirect: 'error', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method, params }), signal: AbortSignal.timeout(5000) });
  const body = await response.json();
  if (body.error) throw new Error('ADMIN');
  return body.result;
}

async function serve() {
  const s = state();
  let request, version, connection;
  let ready = false;
  let stopping = false;
  let lastTimestamp = 0;
  let offset = 0;              // seconds added to the wall clock; only the local controls can change it
  let transactions = 0;
  const minedHashes = new Set();
  let queue = Promise.resolve();
  const exclusive = task => { const run = queue.then(task, task); queue = run.catch(() => {}); return run; };
  // The journal and the chain can no longer be trusted to agree: stop instead of answering anything else.
  const fatal = () => { console.error('JOURNAL_UNCERTAIN'); process.exit(1); };

  // One block per transaction, on an explicit timestamp, so that the chain can be rebuilt exactly. A transaction
  // that is mined belongs to the chain even when its execution reverts. It is «not mined» only when the node
  // refused it and nothing of it is left behind; any other outcome without a receipt is uncertain.
  async function mine(raw, timestamp) {
    const hash = Transaction.from(raw).hash;
    // A hash is mined once: a second copy of the same signed transaction never takes another block.
    if (minedHashes.has(hash)) throw Object.assign(new Error('NOT_MINED'), { refusal: null });
    await request('evm_setNextBlockTimestamp', [timestamp]);
    let refusal = null;
    try { await request('eth_sendRawTransaction', [raw]); } catch (error) { refusal = error; }
    const receipt = await request('eth_getTransactionReceipt', [hash]);
    if (!receipt) {
      if (refusal === null) throw new Error('UNCERTAIN');
      // Never leave an unmined transaction behind: it would be mined later, outside the journal's order.
      if (await request('eth_getTransactionByHash', [hash]) !== null && await request('hardhat_dropTransaction', [hash]) !== true) throw new Error('UNCERTAIN');
      throw Object.assign(new Error('NOT_MINED'), { refusal });
    }
    const block = await request('eth_getBlockByNumber', [receipt.blockNumber, false]);
    if (Number(block?.timestamp) !== timestamp) throw new Error('DIVERGED');
    lastTimestamp = timestamp;
    transactions++;
    minedHashes.add(hash);
    return { hash, block: Number(receipt.blockNumber), blockHash: block.hash };
  }

  async function send(raw) {
    const transaction = Transaction.from(raw);
    if (transaction.chainId !== BigInt(CHAIN) || !transaction.hash || !transaction.from) throw new Error('TRANSACTION');
    // A client that lost the answer may send the same signed transaction again: it is already on the chain.
    if (minedHashes.has(transaction.hash)) return transaction.hash;
    const timestamp = Math.max(lastTimestamp + 1, now() + offset);
    let refusal = null;
    try {
      append({ raw, timestamp });
      let mined = null;
      try { mined = await mine(raw, timestamp); } catch (error) {
        if (error.message !== 'NOT_MINED') throw error;
        refusal = error.refusal ?? error;
      }
      append(mined ? { done: transaction.hash, block: mined.block, blockHash: mined.blockHash } : { done: transaction.hash, rejected: true });
    } catch { return fatal(); }
    if (refusal) throw refusal;
    return transaction.hash;
  }

  const server = createServer(async (req, res) => {
    const reply = (status, body) => { res.writeHead(status, { 'Content-Type': 'application/json' }); res.end(JSON.stringify(body)); };
    // Browsers always send Origin on cross-site requests: no web page may talk to the local network.
    const isAdmin = req.url === '/' + s.adminPath;
    if (req.method !== 'POST' || req.headers.origin || (!isAdmin && req.url !== '/' + s.rpcPath)) { res.writeHead(404).end(); return; }
    let id = null;
    try {
      let body = '';
      for await (const part of req) { body += part; if (body.length > 65536) throw new Error('LIMIT'); }
      const input = JSON.parse(body);
      id = input.id ?? null;
      if (typeof input.method !== 'string' || !Array.isArray(input.params)) throw new Error('REQUEST');
      let result;
      if (isAdmin && input.method === 'sim_stop') { stopping = true; result = true; }
      else if (!ready) {
        // Still rebuilding the chain: the owner answers its controls, nothing else.
        if (!isAdmin || input.method !== 'sim_status') throw new Error('STARTING');
        result = { ready: false };
      } else if (isAdmin) {
        const clock = () => ({ clock: offset === 0 ? 'real' : 'simulated', time: now() + offset, lastBlockTime: lastTimestamp, nextBlockTime: Math.max(lastTimestamp + 1, now() + offset) });
        if (input.method === 'sim_status') result = { ready: true, client: version, chainId: CHAIN, block: Number(await request('eth_blockNumber')), transactions, ...clock() };
        else if (input.method === 'sim_setTime') {
          const target = input.params[0];
          if (target !== null && !(Number.isSafeInteger(target) && target > 0)) throw new Error('TIME');
          offset = target === null ? 0 : target - now();
          // Block timestamps never go back: when the chain is already past the requested time, the next block
          // follows the chain, and the answer says so.
          result = { ...clock(), chainAhead: lastTimestamp >= now() + offset };
        } else throw new Error('METHOD');
      } else if (input.method === 'web3_clientVersion') result = version;
      else if (input.method === 'eth_sendRawTransaction') result = await exclusive(() => send(String(input.params[0])));
      else if (READ_METHODS.has(input.method)) result = await request(input.method, input.params);
      else throw new Error('METHOD');
      reply(200, { jsonrpc: '2.0', id, result });
    } catch (error) {
      // Standard JSON-RPC error without internal details; contract reverts keep their public data for the client.
      const data = typeof error?.data === 'string' && /^0x[0-9a-f]*$/i.test(error.data) ? error.data : undefined;
      reply(200, { jsonrpc: '2.0', id, error: { code: Number.isInteger(error?.code) ? error.code : -32000, message: data ? 'execution reverted' : 'rejected by the local network', ...(data ? { data } : {}) } });
    }
    if (stopping) shutdown();
  });
  async function shutdown() {
    await queue;
    server.closeAllConnections();
    await new Promise(done => server.close(done));
    await connection?.close();
    process.exit(0);
  }
  // The port is the lock, and it is taken before the journal is read. Whoever else holds it, a running network
  // or a reset in progress, makes this start fail here without having touched anything.
  await listen(server, s.port);
  writeFileSync(files.pid, String(process.pid));
  process.on('exit', () => { try { if (readFileSync(files.pid, 'utf8') === String(process.pid)) rmSync(files.pid, { force: true }); } catch { /* already gone */ } });
  for (const signal of ['SIGINT', 'SIGTERM']) process.on(signal, shutdown);

  const { network } = await import('hardhat');
  connection = await network.create(NETWORK);
  request = (method, params = []) => connection.provider.request({ method, params });
  if (Number(await request('eth_chainId')) !== CHAIN) throw new Error('CHAIN');
  version = `${CLIENT} ${await request('web3_clientVersion')}`;
  lastTimestamp = Number((await request('eth_getBlockByNumber', ['latest', false])).timestamp);

  // Rebuild the chain from the journal. Appends are serialised, so every transaction is followed by its own
  // outcome; only the last one may lack it (the process stopped in between). Nobody was told the outcome of
  // that one: like any broadcast whose answer was lost, it is settled now, on its recorded timestamp.
  const intents = [];
  for (const entry of journalEntries()) {
    if (typeof entry.raw === 'string') intents.push({ raw: entry.raw, timestamp: entry.timestamp, hash: Transaction.from(entry.raw).hash, outcome: null });
    else {
      const intent = intents.at(-1);
      if (!intent || intent.outcome || intent.hash !== entry.done) throw new Error('JOURNAL_DOES_NOT_REPLAY');
      intent.outcome = entry;
    }
  }
  for (const [index, intent] of intents.entries()) {
    if (intent.outcome?.rejected) continue;
    if (!intent.outcome && index !== intents.length - 1) throw new Error('JOURNAL_DOES_NOT_REPLAY');
    let mined = null;
    try { mined = await mine(intent.raw, intent.timestamp); } catch (error) {
      if (intent.outcome || error.message !== 'NOT_MINED') throw new Error('JOURNAL_DOES_NOT_REPLAY');
    }
    // The rebuilt block must be the recorded one, hash included: same height is not enough.
    if (intent.outcome && (intent.outcome.block !== mined.block || intent.outcome.blockHash !== mined.blockHash)) throw new Error('JOURNAL_DOES_NOT_REPLAY');
    if (!intent.outcome) append(mined ? { done: intent.hash, block: mined.block, blockHash: mined.blockHash } : { done: intent.hash, rejected: true });
  }
  ready = true;
  console.log(JSON.stringify({ network: 'sepolia-local', chainId: CHAIN, listening: `127.0.0.1:${s.port}`, block: Number(await request('eth_blockNumber')), replayed: transactions }));
}

// The writer is Hardhat's first development account: test-only, with no value on any real network. Its key is
// derived here from Hardhat's own configuration and written only to the private .env files, never to the repository.
async function writerKey() {
  const { network } = await import('hardhat');
  const connection = await network.create(NETWORK);
  try {
    const accounts = connection.networkConfig.accounts;
    const read = async value => typeof value?.get === 'function' ? value.get() : value;
    const wallet = HDNodeWallet.fromPhrase(await read(accounts.mnemonic), await read(accounts.passphrase) ?? '', `${accounts.path}/${accounts.initialIndex}`);
    const exposed = await connection.provider.request({ method: 'eth_accounts', params: [] });
    if (exposed[0]?.toLowerCase() !== wallet.address.toLowerCase()) throw new Error('ACCOUNT');
    return wallet;
  } finally { await connection.close(); }
}

// The contract deployed on the chain that the journal holds, as recorded by the local deployment.
function localDeployment() {
  try {
    const record = JSON.parse(readFileSync(files.deployment, 'utf8'));
    return existsSync(files.journal) && /^0x[0-9a-f]{40}$/.test(record.address) && Number.isSafeInteger(record.block) && record.block > 0 ? record : null;
  } catch { return null; }
}

async function configure() {
  const { stored, save, presentation, presentationProfile, writePrivate } = await import('./local-environment.mjs');
  const active = new URL('../../.env', import.meta.url);
  // The simulation is a property of the presentation environment; it is never switched on over another database.
  // Each file that will be rewritten is judged by its own content, not by the variables of this process.
  // It configures the project's own network, never a throwaway one.
  const targets = [...(existsSync(presentationProfile) ? [presentationProfile] : []), active];
  if (process.env.SEPOLIA_LOCAL_DATA || targets.some(target => !presentation(target))) throw new Error('TARGET');
  const wallet = await writerKey();
  const backup = new URL('../../.env.sepolia-publica', import.meta.url);
  const values = { BLOCKCHAIN_ENVIRONMENT: 'local-simulation', BLOCKCHAIN_ENABLED: 'true', BLOCKCHAIN_NETWORK: 'sepolia', BLOCKCHAIN_CHAIN_ID: String(CHAIN), BLOCKCHAIN_RPC_URL: rpcUrl(),
    BLOCKCHAIN_PRIVATE_KEY: wallet.privateKey, BLOCKCHAIN_CONFIRMATIONS: '1' };
  const deployed = localDeployment();
  for (const target of targets) {
    const current = await stored(target);
    const switching = current.BLOCKCHAIN_ENVIRONMENT !== 'local-simulation';
    if (switching) {
      // Keep whatever this file has for the public testnet (dedicated wallet, RPC, contract) so it can be
      // restored. Every distinct configuration that is replaced is kept; an earlier copy is set aside, never lost.
      // The values are kept as they are read, however each line was written.
      const settings = Object.entries(current).filter(([key]) => key.startsWith('BLOCKCHAIN_'));
      if (settings.some(([, value]) => /[\r\n"]/.test(value))) throw new Error('TARGET');
      const kept = settings.map(([key, value]) => `${key}="${value}"`).join('\n') + '\n';
      if (!existsSync(backup) || readFileSync(backup, 'utf8') !== kept) {
        if (existsSync(backup)) renameSync(backup, new URL(`../../.env.sepolia-publica.${new Date().toISOString().replace(/\D/g, '').slice(0, 17)}`, import.meta.url));
        writePrivate(backup, kept);
      }
    }
    // The contract of the simulation is the one deployed on the local chain, if that chain holds one; otherwise
    // a local deployment is required. Neither a public address nor the address of a chain that is gone is kept.
    const contract = deployed?.address ?? '';
    const block = String(deployed?.block ?? 0);
    // Every local contract stays recorded as such, whatever is configured later.
    const known = String(current.BLOCKCHAIN_SIMULATION_CONTRACTS ?? '').split(',').map(value => value.trim().toLowerCase()).filter(value => /^0x[0-9a-f]{40}$/.test(value));
    await save({ ...values, BLOCKCHAIN_CONTRACT_ADDRESS: contract, BLOCKCHAIN_DEPLOYMENT_BLOCK: block, BLOCKCHAIN_SIMULATION_CONTRACTS: [...new Set([...known, ...(contract ? [contract.toLowerCase()] : [])])].join(',') }, target);
  }
  console.log(JSON.stringify({ environment: 'local-simulation', chainId: CHAIN, writer: wallet.address, contract: (await stored(active)).BLOCKCHAIN_CONTRACT_ADDRESS || null, previousConfigurationKeptLocally: existsSync(backup) }));
}

async function reset() {
  // Only the data of an existing network is emptied: nothing is created or deleted anywhere else.
  const s = state(false);
  // Emptying the chain takes the same lock as the network: the port. It fails while a network is running or
  // starting, whether or not that network answers, and no network can start until this is finished.
  const lock = createServer();
  try { await listen(lock, s.port); } catch { throw new Error('RUNNING'); }
  try {
    rmSync(files.journal, { force: true });
    rmSync(files.deployment, { force: true });
    if (!process.env.SEPOLIA_LOCAL_DATA) {
      // The deployed contract disappears with the chain: every presentation file that points at the simulation
      // forgets its address, whichever of them is active, so that a new deployment is required.
      const { stored, save, presentation, presentationProfile } = await import('./local-environment.mjs');
      for (const target of [new URL('../../.env', import.meta.url), presentationProfile]) {
        if (existsSync(target) && presentation(target) && (await stored(target)).BLOCKCHAIN_ENVIRONMENT === 'local-simulation') await save({ BLOCKCHAIN_CONTRACT_ADDRESS: '', BLOCKCHAIN_DEPLOYMENT_BLOCK: '0' }, target);
      }
    }
  } finally { await new Promise(done => lock.close(done)); }
  console.log(JSON.stringify({ chain: 'empty' }));
}

async function stop() {
  // Stopping a network that is not there is not an error. A network that is there must accept the request,
  // and the command succeeds only once the port, which is the lock of the data, can be taken again.
  let s;
  try { s = state(false); } catch (error) { if (error.message !== 'NO_DATA') throw error; }
  if (s) {
    if (holder() !== null) {
      await admin('sim_stop');
      for (let attempt = 0; attempt < 100 && holder() !== null; attempt++) await pause(100);
    }
    const probe = createServer();
    try { await listen(probe, s.port); } catch { throw new Error('STILL_RUNNING'); }
    await new Promise(done => probe.close(done));
  }
  console.log(JSON.stringify({ running: false }));
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  const [command = 'serve', argument] = process.argv.slice(2);
  try {
    if (command === 'serve') await serve();
    else if (command === 'status') {
      const status = await admin('sim_status');
      if (!status.ready) throw new Error('STARTING');
      console.log(JSON.stringify({ running: true, ...status }));
    } else if (command === 'stop') await stop();
    else if (command === 'time') {
      if (argument !== 'real' && !/^[1-9][0-9]{0,15}$/.test(argument ?? '')) throw new Error('COMMAND');
      console.log(JSON.stringify(await admin('sim_setTime', [argument === 'real' ? null : Number(argument)])));
    } else if (command === 'configure') await configure();
    else if (command === 'reset') await reset();
    else throw new Error('COMMAND');
  } catch (error) {
    const known = { RUNNING: 'Detenga la red local antes de vaciar su cadena.', TARGET: 'La simulación solo se configura sobre el entorno de presentación.',
      JOURNAL_DOES_NOT_REPLAY: 'El diario local no reproduce la cadena; no se inició la red.', EADDRINUSE: 'La red local ya está en marcha o su puerto está ocupado.',
      DATA_DIRECTORY: 'Dentro del repositorio, los datos de la red local solo pueden estar en blockchain/runtime-data/.', STOPPED: 'La red local no está en marcha.',
      STILL_RUNNING: 'La red local no terminó de detenerse o su puerto sigue ocupado.', NO_DATA: 'No hay datos de una red local en ese directorio.' };
    if (command === 'status') console.log(JSON.stringify({ running: false, ...(error?.message === 'STARTING' ? { starting: true } : {}) }));
    else console.error(known[error?.message] ?? known[error?.code] ?? 'SEPOLIA_LOCAL_UNAVAILABLE');
    // A network that could not start must not stay behind holding the port.
    if (command === 'serve') process.exit(1);
    process.exitCode = 1;
  }
}
