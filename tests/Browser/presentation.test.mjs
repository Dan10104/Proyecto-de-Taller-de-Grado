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

test('resource listing shows no fabricated imagery or demo wording and keeps the existing detail destination', async () => {
  const html = await render('SpaceTable', { spaces: [space] });
  assert.ok(!/<img\b/.test(html));
  assert.ok(!/\.webp|Imagen conceptual|· Demo/.test(html));
  assert.match(html, /href="\/espacios\/23"/);
  assert.match(html, /Piso de prueba/);
  assert.match(html, /24 personas/);
});
test('resource text is escaped; a supplied name cannot inject markup', async () => {
  const html = await render('SpaceTable', { spaces: [{ ...space, recurso: { ...space.recurso, nombre: '<img src=x onerror=alert(1)>' } }] });
  assert.ok(!html.includes('<img src=x'));
  assert.match(html, /&lt;img src=x/);
});
test('IoT keeps actual floor references, labels the building layout as provisional and preserves disabled and undetermined semantics', async () => {
  const html = await render('IotTwin', { sensors: [sensor] });
  assert.match(html, /Piso · ref. 77/);
  assert.match(html, /Deshabilitado/);
  assert.match(html, /Ocupación: no determinable/);
  assert.match(html, /Sin lecturas recientes suficientes/);
  // The stacked floors are a provisional visual scaffold supplied by the faculty, not inventory.
  assert.match(html, /Distribución provisional del edificio: no representa el plano real definitivo/);
  assert.ok(!/\d+\s?%/.test(html.replace(/<style[\s\S]*?<\/style>/g, '').replace(/style="[^"]*"/g, '')));
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
test('IoT empty data draws only the provisional scaffold and invents no sensors, readings or floor references', async () => {
  const html = await render('IotTwin', { sensors: [] });
  assert.match(html, /No hay sensores actuales/);
  assert.match(html, /Distribución provisional del edificio: no representa el plano real definitivo/);
  assert.ok(!html.includes('twin-pin'));
  assert.ok(!html.includes('Piso · ref.'));
  assert.ok(!html.includes('Lectura de presencia'));
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
test('IoT shows the recorded floor of a space and never states the provisional floor as fact', async () => {
  const auditorium = { recurso_id: 31, piso_id: 2, capacidad: 60, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-006', nombre: 'Auditorio principal' }, piso: { nombre: 'Segundo piso' }, tipo: { nombre: 'Auditorio' } };
  const classroom = { recurso_id: 32, piso_id: 1, capacidad: 30, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-011', nombre: 'Aula 11' }, piso: { nombre: 'Planta baja' }, tipo: { nombre: 'Aula' } };
  const html = await render('IotTwin', { sensors: [
    { ...sensor, id: 8, identificador: 'SNS-AUD', espacio_actual_id: 31, espacio_actual: auditorium },
    { ...sensor, id: 9, identificador: 'SNS-A11', espacio_actual_id: 32, espacio_actual: classroom },
  ] });
  // The provisional drawing puts the auditorium on floor 4; a record that says otherwise is not drawn there.
  assert.match(html, /Otros espacios monitoreados/);
  assert.match(html, /Segundo piso/);
  assert.match(html, /Planta baja/);
  assert.ok(!/<small[^>]*>Piso \d+<\/small>/.test(html));
  assert.ok(!/Auditorio principal[^<]*Piso 4/.test(html));
});
test('IoT with the monitoring payload shape (no floor relation) draws numbered rooms only and asserts no floor', async () => {
  // IotMonitoringController loads espacioActual.recurso only: the space arrives without piso or tipo.
  const auditorium = { recurso_id: 31, piso_id: 2, capacidad: 60, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-006', nombre: 'Auditorio principal' } };
  const classroom = { recurso_id: 32, piso_id: 1, capacidad: 30, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-011', nombre: 'Aula 11' } };
  const html = await render('IotTwin', { sensors: [
    { ...sensor, id: 8, identificador: 'SNS-AUD', espacio_actual_id: 31, espacio_actual: auditorium },
    { ...sensor, id: 9, identificador: 'SNS-A11', espacio_actual_id: 32, espacio_actual: classroom },
  ] });
  // Without a recorded floor the auditorium cannot be checked against the drawing, so it is listed apart.
  const others = html.slice(html.indexOf('Otros espacios monitoreados'));
  assert.match(others, /Auditorio principal/);
  assert.equal((html.match(/class="twin-pin/g) || []).length, 1);
  assert.ok(!/<small[^>]*>Piso \d+<\/small>/.test(html));
});
test('IoT leaves two spaces that resolve to the same drawn room off the plan instead of picking one', async () => {
  const first = { recurso_id: 41, piso_id: 1, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-A', nombre: 'Aula 11' } };
  const second = { recurso_id: 42, piso_id: 1, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-B', nombre: 'Aula 11' } };
  const html = await render('IotTwin', { sensors: [
    { ...sensor, id: 10, identificador: 'SNS-P', espacio_actual_id: 41, espacio_actual: first },
    { ...sensor, id: 11, identificador: 'SNS-Q', espacio_actual_id: 42, espacio_actual: second },
  ] });
  assert.ok(!html.includes('twin-pin'));
  assert.match(html, /Otros espacios monitoreados/);
});
test('IoT states the last communication as a dated fact and labels typical equipment as such', async () => {
  const classroom = { recurso_id: 32, piso_id: 1, estado: 'DISPONIBLE', recurso: { codigo: 'ESP-011', nombre: 'Aula 11' } };
  const html = await render('IotTwin', { sensors: [{ ...sensor, id: 9, estado: 'HABILITADO', comunicacion: 'RECIENTE', ultima_comunicacion: '2026-10-28T12:00:00Z', espacio_actual_id: 32, espacio_actual: classroom }] });
  assert.match(html, /Última comunicación: /);
  assert.ok(!/En línea|sin señal/i.test(html));
  assert.match(html, /Habitual en este tipo de ambiente; no es el inventario del espacio/);
});
test('the evidence chain carries no label above its verdict heading', async () => {
  const html = await render('TraceabilityChain', { entries: [evidence] });
  assert.ok(!html.includes('tech-chip'));
  assert.match(html, /Abrir evidencia #5/);
});
test('mobile row cards keep the label of an "Acción" data column and drop it only for controls', async () => {
  const { cellLabel } = await import(pathToFileURL(resolve(root, 'resources/js/tableLabels.js')).href);
  assert.deepEqual(cellLabel('Acción', false), { actions: false, label: 'Acción' });
  assert.deepEqual(cellLabel('Acción', true), { actions: true, label: '' });
  assert.deepEqual(cellLabel('Estado', true), { actions: false, label: 'Estado' });
  assert.deepEqual(cellLabel('', true), { actions: false, label: '' });
});
test('the dashboard chart shows its scope next to the title, not only inside the data disclosure', async () => {
  const html = await render('AccessibleChart', { chart: { id: 'G3', titulo: 'Equipamiento por estado', tipo: 'distribucion', contexto: 'Estado actual · independiente del periodo', valores: { DISPONIBLE: 2 }, sin_datos: false } });
  const beforeDetails = html.slice(0, html.indexOf('<details'));
  assert.match(beforeDetails, /Estado actual · independiente del periodo/);
});
