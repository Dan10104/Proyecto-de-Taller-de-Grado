import { test } from 'node:test';
import assert from 'node:assert/strict';
import { connect, environmentOf, execute } from '../runtime/driver.mjs';

const base = { BLOCKCHAIN_CHAIN_ID: '11155111' };

test('the environment is explicit: only «public» and «local-simulation» exist, and «public» is the default', () => {
  assert.equal(environmentOf({}), 'public');
  assert.equal(environmentOf({ BLOCKCHAIN_ENVIRONMENT: 'local-simulation' }), 'local-simulation');
  for (const value of ['', 'sepolia', 'local', 'PUBLIC', 'simulation']) assert.throws(() => environmentOf({ BLOCKCHAIN_ENVIRONMENT: value }));
});

test('transport is bound to the environment before any request is made', async () => {
  // Public Sepolia is never reached over plain http, not even on loopback.
  for (const url of ['http://127.0.0.1:8545/x', 'http://localhost:8545', 'http://[::1]:8545', 'http://example.org']) {
    await assert.rejects(connect({ ...base, BLOCKCHAIN_ENVIRONMENT: 'public', BLOCKCHAIN_RPC_URL: url }));
    await assert.rejects(connect({ ...base, BLOCKCHAIN_RPC_URL: url }));
  }
  // The local simulation is never reached outside loopback, with or without TLS.
  for (const url of ['https://ethereum-sepolia-rpc.publicnode.com', 'https://127.0.0.1:8545', 'http://192.168.1.10:8545', 'http://example.org:8545']) {
    await assert.rejects(connect({ ...base, BLOCKCHAIN_ENVIRONMENT: 'local-simulation', BLOCKCHAIN_RPC_URL: url }));
  }
  await assert.rejects(execute({ action: 'chain' }, { ...base, BLOCKCHAIN_ENVIRONMENT: 'testnet', BLOCKCHAIN_RPC_URL: 'https://example.org' }));
});

// A minimal JSON-RPC endpoint on loopback, to exercise the identity checks without any network.
const SEPOLIA_GENESIS = '0x25a5cc106eea7138acab33231d7160d69cb777ee0c2c553fcddf5138993e6dd9';
async function endpoint(answers) {
  const { createServer } = await import('node:http');
  let requests = 0;
  const server = createServer((req, res) => {
    requests++;
    if (answers.redirect) { res.writeHead(302, { Location: answers.redirect }).end(); return; }
    let body = '';
    req.on('data', chunk => body += chunk).on('end', () => {
      const { id, method } = JSON.parse(body);
      const result = { eth_chainId: '0xaa36a7', web3_clientVersion: answers.client, eth_getBlockByNumber: { hash: answers.genesis } }[method];
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify(result === undefined ? { jsonrpc: '2.0', id, error: { code: -32601, message: 'unsupported' } } : { jsonrpc: '2.0', id, result }));
    });
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  return { url: `http://127.0.0.1:${server.address().port}/rpc`, requests: () => requests, close: () => new Promise(resolve => { server.closeAllConnections(); server.close(resolve); }) };
}
const local = url => ({ ...base, BLOCKCHAIN_ENVIRONMENT: 'local-simulation', BLOCKCHAIN_RPC_URL: url });

test('the local simulation must identify itself and never carries the public genesis', async () => {
  const genuine = await endpoint({ client: 'EspacioU-SepoliaLocal/1 test', genesis: '0x' + '1'.repeat(64) });
  const unnamed = await endpoint({ client: 'Geth/v1.15.0', genesis: '0x' + '1'.repeat(64) });
  const silent = await endpoint({ client: undefined, genesis: '0x' + '1'.repeat(64) });
  const publicHistory = await endpoint({ client: 'EspacioU-SepoliaLocal/1 test', genesis: SEPOLIA_GENESIS });
  try {
    (await connect(local(genuine.url))).destroy();
    for (const other of [unnamed, silent, publicHistory]) await assert.rejects(connect(local(other.url)));
    // Without a usable genesis block there is no identity to check: refused as well.
    for (const genesis of [undefined, '0x1234', 'not-a-hash']) {
      const anonymous = await endpoint({ client: 'EspacioU-SepoliaLocal/1 test', genesis });
      try { await assert.rejects(connect(local(anonymous.url))); } finally { await anonymous.close(); }
    }
  } finally { for (const server of [genuine, unnamed, silent, publicHistory]) await server.close(); }
});

test('a redirect is a failure: only the validated endpoint is ever contacted', async () => {
  const destination = await endpoint({ client: 'EspacioU-SepoliaLocal/1 test', genesis: '0x' + '1'.repeat(64) });
  const redirector = await endpoint({ redirect: destination.url });
  try {
    await assert.rejects(connect(local(redirector.url)));
    assert.equal(destination.requests(), 0);
    assert.ok(redirector.requests() >= 1);
  } finally { await destination.close(); await redirector.close(); }
});

test('the development account of the simulation never signs on the public testnet', async () => {
  const { signer } = await import('../runtime/driver.mjs');
  const { HDNodeWallet } = await import('ethers');
  const { network } = await import('hardhat');
  const connection = await network.create('sepoliaLocal');
  const accounts = connection.networkConfig.accounts;
  const read = async value => typeof value?.get === 'function' ? value.get() : value;
  const development = HDNodeWallet.fromPhrase(await read(accounts.mnemonic), await read(accounts.passphrase) ?? '', `${accounts.path}/${accounts.initialIndex}`);
  await connection.close();
  assert.throws(() => signer({ BLOCKCHAIN_ENVIRONMENT: 'public', BLOCKCHAIN_PRIVATE_KEY: development.privateKey }));
  assert.throws(() => signer({ BLOCKCHAIN_PRIVATE_KEY: development.privateKey }));
  assert.equal(signer({ BLOCKCHAIN_ENVIRONMENT: 'local-simulation', BLOCKCHAIN_PRIVATE_KEY: development.privateKey }).address, development.address);
  assert.ok(signer({ BLOCKCHAIN_ENVIRONMENT: 'public', BLOCKCHAIN_PRIVATE_KEY: '0x' + '7'.repeat(64) }).address);
});

test('only a plainly written presentation environment is accepted as a target of the simulation', async () => {
  const { presentation } = await import('../scripts/local-environment.mjs');
  const { mkdtempSync, writeFileSync, rmSync } = await import('node:fs');
  const { tmpdir } = await import('node:os');
  const { join } = await import('node:path');
  const { pathToFileURL } = await import('node:url');
  const directory = mkdtempSync(join(tmpdir(), 'espacio-u-env-'));
  const judged = lines => { const path = join(directory, 'candidate.env'); writeFileSync(path, lines.join('\n') + '\n'); return presentation(pathToFileURL(path)); };
  try {
    assert.equal(judged(['APP_ENV=local', 'DB_DATABASE=espacio_u_presentacion']), true);
    assert.equal(judged(['# note', 'APP_ENV=testing\r', 'DB_DATABASE=espacio_u_presentacion\r']), true);
    for (const lines of [
      ['APP_ENV=production', 'DB_DATABASE=espacio_u_presentacion'],
      ['DB_DATABASE=espacio_u_presentacion'],                                              // Laravel defaults to production
      ['STAGE=production', 'APP_ENV=${STAGE}', 'DB_DATABASE=espacio_u_presentacion'],      // expanded by Laravel
      ['APP_ENV=local', 'DB_DATABASE=espacio_u'],
      ['APP_ENV=local', 'DB_DATABASE=espacio_u_presentacion', 'DB_URL=mysql://127.0.0.1/espacio_u'],
      ['APP_ENV=local', 'DB_DATABASE=espacio_u_presentacion', '"DB_URL"=mysql://127.0.0.1/espacio_u'],
      ['APP_ENV=local', 'APP_ENV=production', 'DB_DATABASE=espacio_u_presentacion'],
      ['APP_ENV=local', 'DB_DATABASE=espacio_u_presentacion', 'DB_DATABASE=espacio_u'],
      ['export APP_ENV=local', 'DB_DATABASE=espacio_u_presentacion'],
      ['APP_ENV=local', 'NOTES="fixture', 'DB_DATABASE=espacio_u_presentacion', 'end"'],              // a line inside a multi-line value
    ]) assert.equal(judged(lines), false, lines.join(' | '));
    assert.equal(presentation(pathToFileURL(join(directory, 'missing.env'))), false);
  } finally { rmSync(directory, { recursive: true, force: true }); }
});
