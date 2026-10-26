import { execute } from './driver.mjs';
try {
  let input = '';
  for await (const part of process.stdin) {
    input += part;
    if (input.length > 1024) throw new Error('LIMIT');
  }
  const result = await execute(JSON.parse(input), process.env, candidate => process.stdout.write(JSON.stringify({ok:true,...candidate})+'\n'));
  process.stdout.write(JSON.stringify({ ok: true, ...result }));
} catch {
  // Never emit exception stacks, request URLs, signing material or raw provider responses.
  process.stdout.write(JSON.stringify({ ok: false, code: 'EXTERNAL_UNAVAILABLE' }));
  process.exitCode = 1;
}
