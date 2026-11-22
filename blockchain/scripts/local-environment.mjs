import { readFile, writeFile } from 'node:fs/promises';
import { chmodSync, closeSync, existsSync, openSync, readFileSync, writeFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import { parseEnv } from 'node:util';
import { randomBytes } from 'node:crypto';
import { Wallet } from 'ethers';
const location = new URL('../../.env', import.meta.url);
export const presentationProfile = new URL('../../.env.espacio_u_presentacion', import.meta.url);

// What a .env file itself says, without the variables of the calling process. A missing file says nothing.
export async function stored(target = location) {
  try { return parseEnv(await readFile(target, 'utf8')); } catch (error) { if (error?.code === 'ENOENT') return {}; throw error; }
}

export async function environment() {
  return { ...(await stored()), ...process.env };
}

// The local simulation belongs to the presentation environment only. The file is judged by its text, strictly,
// so that no difference between dotenv parsers matters: exactly one plain APP_ENV line that is not production,
// exactly one plain DB_DATABASE line naming the presentation database, and no mention of DB_URL (which would
// override the database name). Anything written in another way is refused.
export function presentation(target = location) {
  let text;
  try { text = readFileSync(target, 'utf8'); } catch { return false; }
  const lines = text.split(/\r?\n/).filter(line => !/^\s*(#|$)/.test(line));
  const naming = name => lines.filter(line => line.includes(name));
  const [application, database] = [naming('APP_ENV'), naming('DB_DATABASE')];
  if (naming('DB_URL').length !== 0 || application.length !== 1 || !/^APP_ENV=(local|testing)$/.test(application[0])
    || database.length !== 1 || !/^DB_DATABASE=espacio_u_presentacion$/.test(database[0])) return false;
  // The lines must also be what a dotenv parser reads: a line inside a multi-line value is not an assignment.
  const parsed = parseEnv(text);
  return ['local', 'testing'].includes(parsed.APP_ENV) && parsed.DB_DATABASE === 'espacio_u_presentacion' && parsed.DB_URL === undefined;
}

export async function save(values, target = location) {
  let lines = (await readFile(target, 'utf8')).split('\n');
  for (const [key, value] of Object.entries(values)) {
    if (!/^BLOCKCHAIN_[A-Z_]+$/.test(key) || /[\r\n"]/.test(String(value))) throw new Error('INVALID_ENV');
    const assigns = line => line.startsWith(key + '=');
    // One assignment per managed key: the first is replaced and later duplicates are removed, so that the value
    // written here is the value that is read.
    const first = lines.findIndex(assigns);
    if (first === -1) {
      while (lines.length > 0 && lines.at(-1).trim() === '') lines.pop();
      lines.push(`${key}="${value}"`, '');
    } else lines = lines.filter((line, index) => index <= first || !assigns(line)).map((line, index) => index === first ? `${key}="${value}"` + (line.endsWith('\r') ? '\r' : '') : line);
  }
  await writeFile(target, lines.join('\n'), { mode: 0o600 });
}

// Restricts a file, or a directory and what is created in it afterwards, to the current user, like the private
// .env files: on Windows a file mode alone does not do that.
export function restrict(target, directory = false) {
  if (process.platform !== 'win32') { chmodSync(target, directory ? 0o700 : 0o600); return; }
  const system = join(process.env.SystemRoot ?? 'C:\\Windows', 'System32');
  const user = /S-1-5-[0-9-]+/.exec(execFileSync(join(system, 'whoami.exe'), ['/user', '/fo', 'csv', '/nh'], { encoding: 'utf8', windowsHide: true }))?.[0];
  if (!user) throw new Error('IDENTITY');
  const inherit = directory ? '(OI)(CI)' : '';
  execFileSync(join(system, 'icacls.exe'), [fileURLToPath(target), '/inheritance:r', '/grant:r', `*${user}:${inherit}(F)`, `*S-1-5-18:${inherit}(F)`], { stdio: 'ignore', windowsHide: true });
}

// Writes a new file that holds secrets: created empty, restricted to the current user, and only then filled.
// It never replaces an existing file.
export function writePrivate(target, text) {
  closeSync(openSync(target, 'wx', 0o600));
  restrict(target);
  writeFileSync(target, text);
}

// Keeps the presentation profile equal to the active simulation settings, so that switching profiles and back
// does not lose the local deployment. Only between two presentation files that are both already simulated:
// nothing that belongs to the public testnet is ever overwritten here.
const SIMULATION_KEYS = ['ENVIRONMENT', 'ENABLED', 'NETWORK', 'CHAIN_ID', 'RPC_URL', 'PRIVATE_KEY', 'CONFIRMATIONS', 'CONTRACT_ADDRESS', 'DEPLOYMENT_BLOCK', 'SIMULATION_CONTRACTS'].map(key => 'BLOCKCHAIN_' + key);
export async function mirrorSimulation() {
  if (!existsSync(presentationProfile) || !presentation() || !presentation(presentationProfile)) return false;
  const [active, profile] = [await stored(), await stored(presentationProfile)];
  if (active.BLOCKCHAIN_ENVIRONMENT !== 'local-simulation' || profile.BLOCKCHAIN_ENVIRONMENT !== 'local-simulation') return false;
  await save(Object.fromEntries(SIMULATION_KEYS.filter(key => active[key] !== undefined).map(key => [key, active[key]])), presentationProfile);
  return true;
}

export async function dedicatedWallet() {
  const current = await environment();
  if (current.BLOCKCHAIN_PRIVATE_KEY) return new Wallet(current.BLOCKCHAIN_PRIVATE_KEY);
  const wallet = new Wallet('0x' + randomBytes(32).toString('hex'));
  await save({ BLOCKCHAIN_PRIVATE_KEY: wallet.privateKey, BLOCKCHAIN_ENABLED: 'false', BLOCKCHAIN_NETWORK: 'sepolia', BLOCKCHAIN_CHAIN_ID: '11155111', BLOCKCHAIN_CONFIRMATIONS: '1' });
  return wallet;
}
