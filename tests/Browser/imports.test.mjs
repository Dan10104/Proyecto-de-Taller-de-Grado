import test from 'node:test';
import assert from 'node:assert/strict';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

// Static guard for every Vue component, pages included: an identifier that is used but neither imported
// nor declared (for example computed() without its import) compiles and builds, then throws when the
// page mounts. The component tests do not mount pages, so this check covers that class of regression.
const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);
const { parse, compileScript } = require('@vue/compiler-sfc');
const { parse: parseModule } = createRequire(require.resolve('@vue/compiler-sfc'))('@babel/parser');

const GLOBALS = new Set(['window', 'document', 'console', 'Object', 'Array', 'String', 'Number', 'Boolean', 'Math', 'JSON', 'Date', 'Intl', 'Map', 'Set', 'WeakMap', 'WeakSet', 'Promise', 'Error', 'RegExp', 'Symbol', 'URL', 'URLSearchParams', 'FormData', 'Blob', 'File', 'navigator', 'location', 'history', 'performance', 'requestAnimationFrame', 'cancelAnimationFrame', 'setTimeout', 'clearTimeout', 'setInterval', 'clearInterval', 'fetch', 'undefined', 'NaN', 'Infinity', 'isNaN', 'parseInt', 'parseFloat', 'encodeURIComponent', 'decodeURIComponent', 'globalThis', 'MutationObserver', 'IntersectionObserver', 'ResizeObserver', 'AbortController', 'Event', 'CustomEvent', 'KeyboardEvent', 'HTMLElement', 'Element', 'Node', 'Image', 'alert', 'confirm', 'crypto', 'TextEncoder', 'TextDecoder', 'structuredClone', 'queueMicrotask', 'arguments', 'BarcodeDetector', 'MediaStream', 'Uint8Array', 'Uint8ClampedArray', 'ArrayBuffer', 'atob', 'btoa', 'getComputedStyle', 'matchMedia', 'localStorage', 'sessionStorage', 'Reflect', 'Proxy', 'BigInt', 'Notification', 'screen', 'self', 'process', 'defineProps', 'defineEmits', 'defineExpose', 'withDefaults', 'defineModel', 'defineOptions', 'defineSlots']);

function components(dir) {
  return readdirSync(dir).flatMap((name) => {
    const path = join(dir, name);
    return statSync(path).isDirectory() ? components(path) : name.endsWith('.vue') ? [path] : [];
  });
}

// Minimal scope analysis over the compiled script (template inlined): returns the referenced names
// that no enclosing scope declares.
export function undeclaredNames(source) {
  const ast = parseModule(source, { sourceType: 'module' });
  const missing = new Set();
  const scopes = [new Set()];
  const declare = (name) => scopes[scopes.length - 1].add(name);
  const known = (name) => GLOBALS.has(name) || scopes.some((scope) => scope.has(name));
  const pattern = (node) => {
    if (!node) return;
    if (node.type === 'Identifier') declare(node.name);
    else if (node.type === 'ObjectPattern') node.properties.forEach((p) => pattern(p.type === 'RestElement' ? p.argument : p.value));
    else if (node.type === 'ArrayPattern') node.elements.forEach(pattern);
    else if (node.type === 'AssignmentPattern') pattern(node.left);
    else if (node.type === 'RestElement') pattern(node.argument);
  };
  const defaults = (node) => {
    if (!node) return;
    if (node.type === 'AssignmentPattern') { visit(node.right); defaults(node.left); }
    else if (node.type === 'ObjectPattern') node.properties.forEach((p) => { if (p.computed) visit(p.key); defaults(p.type === 'RestElement' ? p.argument : p.value); });
    else if (node.type === 'ArrayPattern') node.elements.forEach(defaults);
  };
  const hoist = (body) => {
    for (const node of body) {
      if (node.type === 'ImportDeclaration') node.specifiers.forEach((s) => declare(s.local.name));
      else if ((node.type === 'FunctionDeclaration' || node.type === 'ClassDeclaration') && node.id) declare(node.id.name);
      else if (node.type === 'VariableDeclaration') node.declarations.forEach((d) => pattern(d.id));
      else if ((node.type === 'ExportNamedDeclaration' || node.type === 'ExportDefaultDeclaration') && node.declaration) hoist([node.declaration]);
    }
  };
  const fn = (node) => {
    scopes.push(new Set());
    if (node.id && node.type === 'FunctionExpression') declare(node.id.name);
    node.params.forEach(pattern);
    node.params.forEach(defaults);
    if (node.body.type === 'BlockStatement') { hoist(node.body.body); node.body.body.forEach(visit); } else visit(node.body);
    scopes.pop();
  };
  function visit(node) {
    if (!node || typeof node.type !== 'string') return;
    switch (node.type) {
      case 'Identifier': if (!known(node.name)) missing.add(node.name); return;
      case 'ImportDeclaration': case 'BreakStatement': case 'ContinueStatement': return;
      case 'MemberExpression': case 'OptionalMemberExpression': visit(node.object); if (node.computed) visit(node.property); return;
      case 'ObjectProperty': if (node.computed) visit(node.key); visit(node.value); return;
      case 'ObjectMethod': case 'ClassMethod': if (node.computed) visit(node.key); fn(node); return;
      case 'FunctionDeclaration': case 'FunctionExpression': case 'ArrowFunctionExpression': fn(node); return;
      case 'BlockStatement': case 'Program': scopes.push(new Set()); hoist(node.body); node.body.forEach(visit); scopes.pop(); return;
      case 'VariableDeclaration': node.declarations.forEach((d) => { pattern(d.id); defaults(d.id); visit(d.init); }); return;
      case 'CatchClause': scopes.push(new Set()); pattern(node.param); visit(node.body); scopes.pop(); return;
      case 'ForStatement': case 'ForInStatement': case 'ForOfStatement':
        scopes.push(new Set()); ['init', 'left', 'right', 'test', 'update', 'body'].forEach((key) => visit(node[key])); scopes.pop(); return;
      case 'LabeledStatement': visit(node.body); return;
      case 'ExportNamedDeclaration': case 'ExportDefaultDeclaration': visit(node.declaration); return;
      default:
        for (const key of Object.keys(node)) {
          if (key === 'loc' || key === 'start' || key === 'end' || key === 'extra' || key === 'type') continue;
          const value = node[key];
          if (Array.isArray(value)) value.forEach(visit);
          else if (value && typeof value.type === 'string') visit(value);
        }
    }
  }
  visit(ast.program);
  return [...missing].filter((name) => !name.startsWith('_') && !name.startsWith('$'));
}

test('the analyser reports a used name that is not imported', () => {
  assert.deepEqual(undeclaredNames("import { ref } from 'vue'; const a = ref(0); const b = computed(() => a.value);"), ['computed']);
  assert.deepEqual(undeclaredNames("import { computed, ref } from 'vue'; const a = ref(0); const b = computed(() => a.value);"), []);
});

test('every Vue component declares or imports each name it uses', () => {
  const files = components(join(root, 'resources/js'));
  assert.ok(files.length > 50);
  const problems = [];
  for (const file of files) {
    const { descriptor } = parse(readFileSync(file, 'utf8'), { filename: file });
    if (!descriptor.scriptSetup && !descriptor.script) continue;
    const missing = undeclaredNames(compileScript(descriptor, { id: file, inlineTemplate: true }).content);
    if (missing.length) problems.push(`${relative(root, file).replace(/\\/g, '/')}: ${missing.join(', ')}`);
  }
  assert.deepEqual(problems, []);
});
