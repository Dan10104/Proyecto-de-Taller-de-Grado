import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve, extname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { parse, compileScript } from '@vue/compiler-sfc';

// Render the actual Vue components without a browser, API or database.
const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const cache = new Map();
function componentUrl(path) {
  if (cache.has(path)) return cache.get(path);
  const { descriptor } = parse(readFileSync(path, 'utf8'), { filename: path });
  let code = compileScript(descriptor, { id: path, inlineTemplate: true }).content;
  code = code.replace(/from\s+(['"])([^'"]+)\1/g, (full, quote, specifier) => {
    let url;
    if (specifier.startsWith('.')) {
      let target = resolve(dirname(path), specifier);
      if (!extname(target)) target += '.js';
      url = target.endsWith('.vue') ? componentUrl(target) : pathToFileURL(target).href;
    } else url = import.meta.resolve(specifier);
    return `from ${JSON.stringify(url)}`;
  });
  const url = 'data:text/javascript;base64,' + Buffer.from(code).toString('base64');
  cache.set(path, url);
  return url;
}
async function render(name, props) {
  const { default: Component } = await import(componentUrl(resolve(root, `resources/js/Components/${name}.vue`)));
  return renderToString(createSSRApp({ render: () => h(Component, props) }));
}
const space = { recurso_id: 23, piso_id: 77, capacidad: 24, estado: 'DISPONIBLE', recurso: { codigo: 'DEMO-23', nombre: 'Laboratorio Demo' }, piso: { nombre: 'Piso de prueba' }, tipo: { nombre: 'Laboratorio' }, caracteristicas: [{ id: 1, nombre: 'Proyector' }] };
const sensor = { id: 4, identificador: 'SENSOR-DEMO', estado: 'DESHABILITADO', comunicacion: 'ANTIGUA', espacio_actual_id: 23, espacio_actual: space, lecturas: [] };
const evidence = { id: 5, actividad: { reserva_id: 9, accion: 'RESERVA_APROBADA', fecha_hora: '2026-10-20T15:00:00-04:00' }, algoritmo_hash: 'SHA-256', version_canonica: 'espacio-u-jcs-v1', hash: 'a'.repeat(64), estado: 'PENDIENTE', ultima_verificacion: null, registrada_externamente_en: null, registro_actividad_id: 12 };

test('resource imagery is explicitly conceptual and keeps the existing detail destination', async () => {
  const html = await render('SpaceGallery', { spaces: [space] });
  assert.match(html, /Imagen conceptual · Demo/);
  assert.match(html, /laboratory-demo.webp/);
  assert.match(html, /href="\/espacios\/23"/);
  assert.match(html, /Piso de prueba/);
  assert.match(html, /24 personas/);
});
test('resource text is escaped; a supplied name cannot inject markup', async () => {
  const html = await render('SpaceGallery', { spaces: [{ ...space, recurso: { ...space.recurso, nombre: '<img src=x onerror=alert(1)>' } }] });
  assert.ok(!html.includes('<img src=x'));
  assert.match(html, /&lt;img src=x/);
});
test('IoT uses actual floor references and preserves disabled and undetermined semantics', async () => {
  const html = await render('IotTwin', { sensors: [sensor] });
  assert.match(html, /Piso · ref. 77/);
  assert.match(html, /Deshabilitado/);
  assert.match(html, /Ocupación: no determinable/);
  assert.match(html, /Sin lecturas recientes suficientes/);
  assert.ok(!html.includes('Piso 1'));
  assert.match(html, /no representa el plano real/);
});
test('IoT retains both sensors on the same room and does not turn raw zero into availability', async () => {
  const reading = { sensor_id: 5, tipo: 'OCUPACION', valor: '0.00', vigente_por_segundos: 30, fecha_evento: '2026-10-28 08:00:00', fecha_recepcion: '2026-10-28 08:00:01' };
  const html = await render('IotTwin', { sensors: [sensor, { ...sensor, id: 5, identificador: 'SEGUNDO', estado: 'HABILITADO', lecturas: [reading] }] });
  assert.match(html, /SENSOR-DEMO/);
  assert.match(html, /SEGUNDO/);
  assert.match(html, /0.00/);
  assert.match(html, /Ocupación: no determinable/);
  assert.ok(!html.includes('0%'));
});
test('IoT empty data does not invent rooms, floors or sensors', async () => {
  const html = await render('IotTwin', { sensors: [] });
  assert.match(html, /No hay sensores actuales/);
  assert.ok(!html.includes('class="twin-room"'));
  assert.ok(!html.includes('Piso · ref.'));
});
test('pending blockchain evidence never implies an external confirmation or valid verification', async () => {
  const html = await render('TraceabilityChain', { evidence });
  assert.match(html, /Reserva 9/);
  assert.match(html, /Sin confirmación externa/);
  assert.match(html, /Sin verificar/);
  assert.match(html, /SHA-256/);
  assert.ok(!html.includes('chain-green'));
});
test('blockchain renders supplied confirmation and verification separately, with the original detail link', async () => {
  const html = await render('TraceabilityChain', { entries: [{ ...evidence, estado: 'REGISTRADA', registrada_externamente_en: '2026-10-21T10:00:00-04:00', ultima_verificacion: '2026-10-22T10:00:00-04:00', resultado_verificacion: 'VALIDO' }] });
  assert.match(html, /Registrada/);
  assert.match(html, /href="\/trazabilidad\/5"/);
  assert.ok(!html.includes('Sin confirmación externa'));
  assert.ok(!html.includes('Sin verificar'));
  assert.match(html, /no bloques enlazados entre operaciones/);
});
