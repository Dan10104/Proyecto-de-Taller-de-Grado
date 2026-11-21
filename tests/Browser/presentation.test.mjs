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
  const html = await render('TraceabilityChain', { entries: [{ ...evidence, estado: 'REGISTRADA', referencia_externa: '0x' + 'b'.repeat(64), registrada_externamente_en: '2026-10-21T10:00:00-04:00', ultima_verificacion: '2026-10-22T10:00:00-04:00', resultado_verificacion: 'VALIDO' }] });
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
const load = (file) => import(pathToFileURL(resolve(root, file)).href);
test('the IoT plan needs an unambiguous room identifier: a bare number in a name is not a room', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const at = (nombre, codigo, extra = {}) => locateSpace({ recurso: { nombre, codigo }, ...extra });
  assert.equal(at('Sala para 20 personas', 'SAL-NORTE'), null);
  assert.equal(at('Depósito general', 'ESP-020'), null);
  assert.equal(at('Aula 41', 'ESP-041'), null); // 41 is a laboratory in the drawing: the type must agree
  assert.deepEqual(at('Aula 11', 'ESP-001'), { floor: 1, room: 11, kind: 'aula' });
  assert.deepEqual(at('Aula N° 13', 'ESP-002'), { floor: 1, room: 13, kind: 'aula' });
  assert.deepEqual(at('Laboratorio de redes', 'LAB-41'), { floor: 4, room: 41, kind: 'laboratorio' });
  assert.equal(at('Aula 11', 'ESP-001', { piso: { nombre: 'Segundo piso' } }), null); // the recorded floor contradicts the drawing
  assert.equal(at('Auditorio principal', 'ESP-006'), null); // no number and no recorded floor to confirm it
  assert.deepEqual(at('Auditorio principal', 'ESP-006', { piso: { nombre: 'Cuarto piso' } }), { floor: 4, room: 'auditorium', kind: 'auditorio' });
  // Two different rooms in one record are ambiguous; the same room named twice is not.
  assert.equal(at('Aula 11 / Aula 13', 'ESP-009'), null);
  assert.equal(at('Aula 11', 'AULA-13'), null);
  assert.deepEqual(at('Aula 11', 'AULA-11'), { floor: 1, room: 11, kind: 'aula' });
  assert.deepEqual(at('Sala norte', 'AULA_11'), { floor: 1, room: 11, kind: 'aula' });
  // Any other number next to an identifier may be another room, whatever the separator: the record is
  // ambiguous and stays off the plan.
  for (const name of ['Aula 11 / 13', 'Aula 11 y 13', 'Aula 11 o 13', 'Aula 11 u 13', 'Aula 11–13', 'Aula 11—13', 'Aula 11-13', 'Aula 11;13', 'Aula 11.13', 'Aula 11 (13)', 'Aula 11, 13 y 15', 'Aula 11 (30 personas)']) {
    assert.equal(at(name, 'ESP-001'), null, name);
  }
  assert.equal(at('Sala norte', 'AULA_11-13'), null);
  assert.equal(at('Sala norte', 'AULA-11/13'), null);
  // Text without another number keeps the identifier unambiguous.
  assert.deepEqual(at('Aula 11 - Bloque B', 'ESP-001'), { floor: 1, room: 11, kind: 'aula' });
  assert.deepEqual(at('Aula 11', 'ESP-013'), { floor: 1, room: 11, kind: 'aula' }); // a code without a room identifier is a serial
});
test('a REGISTRADA state without confirmation date or reference is not presented as an external registration', async () => {
  const html = await render('TraceabilityChain', { evidence: { ...evidence, estado: 'REGISTRADA', referencia_externa: null, registrada_externamente_en: null, ultima_verificacion: null } });
  assert.match(html, /Registro externo sin respaldo/);
  assert.match(html, /Sin confirmación externa/);
  assert.ok(!/registro externo confirmado/.test(html));
  assert.ok(!html.includes('chain-violet'));
  assert.match(html, /Estado recibido/);
  // A confirmation date without its reference is not enough either.
  const dated = await render('TraceabilityChain', { evidence: { ...evidence, estado: 'REGISTRADA', registrada_externamente_en: '2026-10-21T10:00:00-04:00' } });
  assert.match(dated, /Registro externo sin respaldo/);
  assert.ok(!/registro externo confirmado/.test(dated));
  const { externalRegistration } = await load('resources/js/traceability.js');
  assert.equal(externalRegistration({ estado: 'REGISTRADA', registrada_externamente_en: '2026-10-21', referencia_externa: '0x' + 'c'.repeat(64) }), 'registered');
  assert.equal(externalRegistration({ estado: 'REGISTRADA', registrada_externamente_en: null, referencia_externa: null }), 'unbacked');
  assert.equal(externalRegistration({ estado: 'PENDIENTE' }), 'pending');
});
test('an expired reading stays expired when the detail is mounted later, as when another space is selected', async () => {
  const reading = { sensor_id: 5, tipo: 'PRESENCIA', valor: '1.00', vigente_por_segundos: 1, fecha_evento: '2026-10-28 08:00:00', fecha_recepcion: '2026-10-28 08:00:01' };
  const fresh = await render('IotEvidence', { readings: [reading], embedded: true });
  assert.match(fresh, /Lectura de presencia/);
  const late = await render('IotEvidence', { readings: [reading], embedded: true, elapsed: 8 });
  assert.match(late, /Sin lecturas recientes suficientes/);
  assert.ok(!late.includes('Lectura de presencia'));
  // The compact detail keeps the reception time the server sent.
  const compact = await render('IotEvidence', { readings: [reading], compact: true });
  assert.match(compact, /Recepción/);
  assert.match(compact, /08:00:01/);
});
test('the state breakdown keeps the indicator title and the clarification sent with it', async () => {
  const html = await render('StateBreakdown', { title: 'Espacios por estado operativo', context: 'Estado operativo actual · no es disponibilidad temporal', total: 3, values: { DISPONIBLE: 2, BLOQUEADO: 1 } });
  assert.match(html, /Espacios por estado operativo/);
  assert.match(html, /no es disponibilidad temporal/);
});
test('home and the sidebar share one navigation: a single view permission is an authorized module', async () => {
  const { authorizedModules, navigationGroups } = await load('resources/js/navigation.js');
  const only = (...codes) => (code) => codes.includes(code);
  assert.deepEqual(authorizedModules(only('reservas.ver_administrativas')).map(item => item.href), ['/reservas']);
  // Registering without listing opens the form, never the list.
  assert.deepEqual(authorizedModules(only('incidencias.gestionar')).map(item => item.href), ['/incidencias/crear']);
  assert.deepEqual(authorizedModules(only('iot.sensores.gestionar')).map(item => item.href), ['/iot/sensores/crear']);
  assert.deepEqual(authorizedModules(only('espacios.gestionar')).map(item => item.href), ['/espacios/crear']);
  assert.deepEqual(authorizedModules(only('espacios.ver')).map(item => item.href), ['/disponibilidad', '/espacios']);
  assert.deepEqual(authorizedModules(only()), []);
  assert.deepEqual(navigationGroups(only()).flatMap(group => group.items).map(item => item.href), ['/inicio']);
});
test('mobile row cards label every data cell, the first one included', async () => {
  const { labelTables } = await load('resources/js/tableLabels.js');
  const node = (extra = {}) => ({ attrs: {}, hasAttribute(name) { return name in this.attrs; }, setAttribute(name, value) { this.attrs[name] = value; }, ...extra });
  const cell = (text, controls = false) => node({ tagName: 'TD', textContent: text, dataset: {}, querySelector: () => (controls ? {} : null), cloneNode: () => ({ textContent: text, querySelectorAll: () => [] }) });
  const head = node({ cells: [cell('Espacio histórico'), cell('Acción'), cell('Acción')] });
  const row = node({ cells: [cell('Aula 11'), cell('Reserva aprobada'), cell('Ver', true)] });
  const table = node({ tHead: node({ rows: [head] }), tBodies: [node({ rows: [row] })] });
  labelTables({ querySelectorAll: () => [table] });
  assert.equal(row.cells[0].dataset.label, 'Espacio histórico');
  assert.equal(row.cells[1].dataset.label, 'Acción');
  assert.equal(row.cells[2].dataset.label, undefined);
  assert.equal(row.cells[2].dataset.actions, '');
  assert.equal(table.attrs.role, 'table');
});
test('the IoT inspector keeps the recorded location reference of a space in its technical details', async () => {
  const spaceWithReference = { recurso_id: 51, piso_id: 3, capacidad: 20, estado: 'DISPONIBLE', referencia_ubicacion: 'Pasillo sur, puerta azul', recurso: { codigo: 'ESP-051', nombre: 'Sala norte' } };
  const html = await render('IotTwin', { sensors: [{ ...sensor, id: 12, identificador: 'SNS-REF', espacio_actual_id: 51, espacio_actual: spaceWithReference }] });
  assert.match(html, /Referencia de ubicación registrada/);
  assert.match(html, /Pasillo sur, puerta azul/);
});
