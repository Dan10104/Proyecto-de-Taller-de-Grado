import { readFile, writeFile } from 'node:fs/promises';
import { parseEnv } from 'node:util';
import { randomBytes } from 'node:crypto';
import { Wallet } from 'ethers';
const location = new URL('../../.env', import.meta.url);

export async function environment() {
  return { ...parseEnv(await readFile(location, 'utf8')), ...process.env };
}

export async function save(values) {
  let text = await readFile(location, 'utf8');
  for (const [key, value] of Object.entries(values)) {
    if (!/^BLOCKCHAIN_[A-Z_]+$/.test(key) || /[\r\n"]/.test(String(value))) throw new Error('INVALID_ENV');
    const line = `${key}="${value}"`;
    const pattern = new RegExp(`^${key}=.*$`, 'm');
    text = pattern.test(text) ? text.replace(pattern, () => line) : text.trimEnd() + '\n' + line + '\n';
  }
  await writeFile(location, text, { mode: 0o600 });
}

export async function dedicatedWallet() {
  const current = await environment();
  if (current.BLOCKCHAIN_PRIVATE_KEY) return new Wallet(current.BLOCKCHAIN_PRIVATE_KEY);
  const wallet = new Wallet('0x' + randomBytes(32).toString('hex'));
  await save({ BLOCKCHAIN_PRIVATE_KEY: wallet.privateKey, BLOCKCHAIN_ENABLED: 'false', BLOCKCHAIN_NETWORK: 'sepolia', BLOCKCHAIN_CHAIN_ID: '11155111', BLOCKCHAIN_CONFIRMATIONS: '1' });
  return wallet;
}
