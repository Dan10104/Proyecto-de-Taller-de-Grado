import { createHash } from 'node:crypto';
import { execute } from '../runtime/driver.mjs';
import { environment } from './local-environment.mjs';
try {
  const env = await environment();
  if (env.BLOCKCHAIN_NETWORK !== 'sepolia') throw new Error('NETWORK');
  const hash = createHash('sha256').update('ESPACIO-U synthetic public Sepolia smoke v1').digest('hex');
  const prior = await execute({action:'find',hash}, env);
  const result = prior.status === 'ABSENT' ? await execute({action:'register',hash},env) : prior;
  console.log(JSON.stringify({network:'sepolia',hash,...result}));
  if (result.status !== 'CONFIRMED') console.log('Ejecutar de nuevo después de las confirmaciones para comprobar el registro.');
} catch { console.error('SMOKE_NOT_CONFIRMED'); process.exitCode=1; }
