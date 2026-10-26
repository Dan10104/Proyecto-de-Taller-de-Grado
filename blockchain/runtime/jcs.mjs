import canonicalize from 'canonicalize';
import { pathToFileURL } from 'node:url';
import { parseTree } from 'jsonc-parser';

export function parseStrict(text) {
  const errors = [];
  const tree = parseTree(text, errors, { disallowComments: true, allowTrailingComma: false, allowEmptyContent: false });
  if (!tree || errors.length) throw new Error('INVALID_JSON');
  function check(node, depth = 0) {
    if (depth > 32) throw new Error('DEPTH');
    if (node.type === 'object') {
      const names = new Set();
      for (const property of node.children ?? []) {
        const name = property.children[0].value;
        if (names.has(name)) throw new Error('DUPLICATE_KEY');
        names.add(name);
      }
    }
    for (const child of node.children ?? []) check(child, depth + 1);
  }
  check(tree);
  return JSON.parse(text);
}

// Reject non-I-JSON strings before the RFC8785 serializer; do not normalize Unicode.
export function serialize(value) {
  function validate(v) {
    if (typeof v === 'string' && !v.isWellFormed()) throw new Error('INVALID_UNICODE');
    if (typeof v === 'number' && !Number.isFinite(v)) throw new Error('INVALID_NUMBER');
    if (v && typeof v === 'object') {
      for (const [k, item] of Object.entries(v)) { validate(k); validate(item); }
    }
  }
  validate(value);
  return canonicalize(value);
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  try {
    const chunks = []; let size = 0;
    for await (const part of process.stdin) {
      chunks.push(part); size += part.length;
      if (size > 262144) throw new Error('LIMIT');
    }
    const input = new TextDecoder('utf-8', { fatal: true }).decode(Buffer.concat(chunks));
    process.stdout.write(serialize(parseStrict(input)));
  } catch { process.stderr.write('JCS_INPUT_REJECTED'); process.exitCode = 1; }
}
