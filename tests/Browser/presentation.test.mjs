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
  // Anything added to the identifier keeps the record off the plan, a descriptive suffix included.
  assert.equal(at('Aula 11 - Bloque B', 'ESP-001'), null);
  assert.deepEqual(at('Aula 11', 'ESP-013'), { floor: 1, room: 11, kind: 'aula' }); // a code without a room type is a serial
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
test('no record that mentions a second number is drawn as a single room, whatever the separator or the script', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const at = (nombre, codigo) => locateSpace({ recurso: { nombre, codigo } });
  // Second numbers: basic digits, full-width, Arabic-Indic, Devanagari, superscript, Roman numeral characters and letters.
  const seconds = ['13', '１３', '١٣', '१३', '¹³', 'ⅩⅢ', 'XIII', 'xiii', 'IV', '13.5', '013'];
  const separators = [' / ', '/', ' - ', '-', '–', '—', ' o ', ' u ', ' y ', ' ó ', ', ', ';', '.', ' ', '\n', '\t', ' (', ' al ', ' a ', '+', ' & ', '·', '|', ' / ', ' ∕ ', '_'];
  const templates = [
    (sep, n) => `Aula 11${sep}${n}`,
    (sep, n) => `Aulas 11${sep}${n}`,
    (sep, n) => `Aula (11${sep}${n})`,
    (sep, n) => `Aula N° 11${sep}${n}`,
    (sep, n) => `Ambientes 11${sep}${n}`,
    (sep, n) => `Aula 11${sep}Aula ${n}`,
  ];
  let checked = 0;
  for (const second of seconds) {
    for (const separator of separators) {
      for (const template of templates) {
        const name = template(separator, second);
        for (const code of ['ESP-001', 'AULA-11', 'AULA_11']) {
          assert.equal(at(name, code), null, `${JSON.stringify(name)} / ${code}`);
          checked++;
        }
      }
      // The same list written in the code, with a name that says nothing about rooms.
      assert.equal(at('Sala norte', `AULA-11${separator}${second}`), null, `código AULA-11${JSON.stringify(separator)}${second}`);
      checked++;
    }
  }
  assert.ok(checked > 5000);
  // Reported counterexamples, kept verbatim.
  for (const [name, code] of [['Aula 11 / １３', 'ESP-001'], ['Aula 11 / ١٣', 'ESP-001'], ['Aula 11 / १३', 'ESP-001'], ['Aula 11 / ¹³', 'ESP-001'], ['Aula 11 / ⅩⅢ', 'ESP-001'], ['Aula 11 / XIII', 'ESP-001'], ['Sala norte', 'AULA-11/１３'], ['Aulas 11 y 13', 'AULA-11'], ['Aula (11 / 13)', 'AULA-11'], ['Sala 13', 'AULA-11']]) {
    assert.equal(at(name, code), null, `${name} / ${code}`);
  }
  // Calling the function repeatedly gives the same answer (no state kept between calls).
  for (let i = 0; i < 3; i++) assert.equal(at('Aula 11 / XIII', 'ESP-001'), null);
});
test('single rooms are still drawn after the ambiguity rules', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const at = (nombre, codigo, extra = {}) => locateSpace({ recurso: { nombre, codigo }, ...extra });
  const aula11 = { floor: 1, room: 11, kind: 'aula' };
  for (const [name, code] of [['Aula 11', 'ESP-001'], ['Aula 11', 'AULA-11'], ['Aula 11', 'ESP-013'], ['Sala norte', 'AULA_11'], ['Aula 011', 'ESP-001'], ['AULA 11', 'ESP-001'], ['  Aula   11  ', 'ESP-001'], ['Aula Nro. 11', 'ESP-001'], ['Aula １１', 'ESP-001']]) {
    assert.deepEqual(at(name, code), aula11, `${name} / ${code}`);
  }
  assert.deepEqual(at('Aula N° 13', 'ESP-001'), { floor: 1, room: 13, kind: 'aula' });
  assert.deepEqual(at('Laboratorio de redes', 'LAB-41'), { floor: 4, room: 41, kind: 'laboratorio' });
  assert.deepEqual(at('Laboratorio 48', 'ESP-005'), { floor: 4, room: 48, kind: 'laboratorio' });
  assert.deepEqual(at('Auditorio principal', 'ESP-006', { piso: { nombre: 'Cuarto piso' } }), { floor: 4, room: 'auditorium', kind: 'auditorio' });
  assert.equal(at('Auditorio principal', 'ESP-006'), null);
  assert.equal(at('Auditorio principal', 'ESP-006', { piso: { nombre: 'Segundo piso' } }), null);
  assert.equal(at('Sala para 20 personas', 'SAL-NORTE'), null);
  assert.equal(at('Aula 41', 'ESP-001'), null);
  assert.equal(at('Aula 11', 'AULA-13'), null);
  assert.equal(at('Aula 11', 'ESP-001', { piso: { nombre: 'Segundo piso' } }), null);
  assert.equal(at('Aula 11', 'ESP-001', { piso: { nombre: 'Piso ２' } }), null); // a full-width floor number still contradicts
});
test('a room is drawn only when its name or its code is exactly a room identifier', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const at = (nombre, codigo) => locateSpace({ recurso: { nombre, codigo } });
  // Reported case, kept verbatim, and the same idea with other suffixes: a different room is named.
  for (const [name, code] of [
    ['Aula 11 / Aula 11B', 'ESP-001'], ['Aula 11B', 'ESP-001'], ['Aula 11 B', 'ESP-001'], ['Aula 11 bis', 'ESP-001'], ['Aula 11 anexo', 'ESP-001'],
    ['Aula 11-A', 'ESP-001'], ['Aula 11 - Bloque B', 'ESP-001'], ['Aula 11 - Bloque C', 'ESP-001'], ['Aula 11 (aula 11)', 'ESP-001'], ['Aula 11.', 'ESP-001'],
    ['Aula 11 y laboratorio', 'ESP-001'], ['Aulas 11', 'ESP-001'], ['Ambiente 11', 'AULA-11'], ['Sala 11', 'AULA-11'], ['Sala XIII', 'AULA-11'],
    ['Sala norte', 'AULA-11B'], ['Sala norte', 'AULA-11-B'], ['Sala norte', 'AULA-11 bis'], ['Sala norte', 'AULAS-11'], ['Aula 11', 'AULA-11B'],
    ['Aula 11', 'LAB-11'], ['Laboratorio 41', 'AULA-41'],
  ]) {
    assert.equal(at(name, code), null, `${name} / ${code}`);
  }
  // Suffixes generated around a valid identifier: none may be drawn.
  let checked = 0;
  for (const suffix of ['B', 'b', ' B', ' bis', ' anexo', '-A', '/B', ' (B)', ' I', ' II', 'º', '.5', ' 1', '0000', ' y', ' sur', '\u200b13', ' / Aula 11B', ' – Aula 11']) {
    for (const base of ['Aula 11', 'Lab 41', 'Laboratorio 41', 'Aula N° 11']) {
      assert.equal(at(`${base}${suffix}`, 'ESP-001'), null, `${base}${JSON.stringify(suffix)}`);
      checked++;
    }
  }
  assert.ok(checked > 70);
});

test('the IoT plan places a space by its stable records: space code, type code and floor number', async () => {
  const { locateSpace, BUILDING_FLOORS } = await load('resources/js/building.js');
  // The shape the monitoring page receives: the space with its resource, its catalog type and its floor.
  const record = (codigo, nombre, tipo, piso) => locateSpace({ recurso: { codigo, nombre }, ...(tipo ? { tipo: { codigo: tipo, nombre: tipo } } : {}), ...(piso ? { piso: { numero: piso, nombre: `Piso ${piso}` } } : {}) });
  const auditorium = { floor: 4, room: 'auditorium', kind: 'auditorio' };

  // The auditorium is found by its catalog type or by its code; its name is only presentation text.
  assert.deepEqual(record('AUD-01', 'Auditorio', 'AUDITORIO', 4), auditorium);
  assert.deepEqual(record('AUD-01', 'Salón de actos', 'AUDITORIO', 4), auditorium);
  assert.deepEqual(record('ESP-006', 'Salón de actos', 'AUDITORIO', 4), auditorium);
  assert.deepEqual(record('AUDITORIO', 'Salón de actos', null, 4), auditorium);
  assert.deepEqual(record('AUD-01', 'Auditorio', null, 4), auditorium);
  // It has no room number: the recorded floor must confirm it, and nothing in the record may contradict it.
  assert.equal(record('AUD-01', 'Auditorio', 'AUDITORIO', null), null);
  assert.equal(record('AUD-01', 'Auditorio', 'AUDITORIO', 3), null);
  assert.equal(record('AUD-01', 'Auditorio', 'AULA', 4), null);
  assert.equal(record('AULA-11', 'Auditorio', 'AUDITORIO', 4), null);
  assert.equal(record('LAB-41', 'Auditorio 41', 'AUDITORIO', 4), null);
  // A name that only mentions an auditorium does not override a recognised catalog type.
  assert.equal(record('ESP-030', 'Sala junto al auditorio', 'AULA', 4), null);

  // Every room of the provisional distribution, with the project's code convention.
  let rooms = 0;
  for (const floor of BUILDING_FLOORS) {
    for (const item of [...floor.left, ...floor.right].filter((entry) => typeof entry === 'number')) {
      const lab = floor.kind === 'laboratorio';
      const code = `${lab ? 'LAB' : 'AULA'}-${item}`, name = `${lab ? 'Laboratorio' : 'Aula'} ${item}`, type = lab ? 'LABORATORIO' : 'AULA';
      assert.deepEqual(record(code, name, type, floor.number), { floor: floor.number, room: item, kind: floor.kind }, code);
      // The code alone decides when the name is descriptive; the catalog type must agree with it.
      assert.deepEqual(record(code, 'Ambiente de prácticas', type, floor.number), { floor: floor.number, room: item, kind: floor.kind }, code);
      assert.equal(record(code, name, lab ? 'AULA' : 'LABORATORIO', floor.number), null, code);
      assert.equal(record(code, name, type, floor.number === 4 ? 1 : floor.number + 1), null, code);
      rooms++;
    }
  }
  assert.equal(rooms, 30);
  assert.deepEqual(record('LAB-48', 'Laboratorio de robótica', 'LABORATORIO', 4), { floor: 4, room: 48, kind: 'laboratorio' });
  // An unknown catalog type says nothing: the code and the name decide as before.
  assert.deepEqual(record('AULA-11', 'Aula 11', 'T-01', 1), { floor: 1, room: 11, kind: 'aula' });
});

test('the auditorium is as strict as any room: nothing in the record may point elsewhere', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const record = (codigo, nombre, tipo, piso = 4) => locateSpace({ recurso: { codigo, nombre }, ...(tipo ? { tipo: { codigo: tipo, nombre: tipo } } : {}), ...(piso ? { piso: { numero: piso } } : {}) });
  const auditorium = { floor: 4, room: 'auditorium', kind: 'auditorio' };
  // A code or a name that names a room, or several, is ambiguous whatever the catalog type says.
  assert.equal(record('LAB-41B', 'Sala norte', 'AUDITORIO'), null);
  assert.equal(record('AUD-01', 'Laboratorios 41 y 42', 'AUDITORIO'), null);
  assert.equal(record('AUD-01', 'Auditorio y aula 11', 'AUDITORIO'), null);
  assert.equal(record('AUD-01', 'Auditorio 2', 'AUDITORIO'), null);
  assert.equal(record('AUD-01', 'Auditorio II', 'AUDITORIO'), null);
  // The drawing has one auditorium: the code of another one is not it.
  assert.equal(record('AUD-02', 'Salón de actos', 'T-9'), null);
  assert.equal(record('AUD-02', 'Salón de actos', 'AUDITORIO'), null);
  assert.equal(record('AUDITORIO-3', 'Auditorio', null), null);
  for (const code of ['AUD', 'AUD-01', 'AUD-1', 'AUD_01', 'AUDITORIO', 'AUDITORIO-01']) assert.deepEqual(record(code, 'Salón de actos', 'T-9'), auditorium, code);
  // Without a recognised type, the name must say that the space itself is an auditorium.
  assert.equal(record('ESP-005', 'Sala de auditoría', 'T-9'), null);
  assert.equal(record('ESP-005', 'Sala junto al auditorio', 'T-9'), null);
  assert.equal(record('ESP-005', 'Sala junto al auditorio', null), null);
  assert.deepEqual(record('ESP-005', 'Auditorio principal', 'T-9'), auditorium);
  assert.deepEqual(record('ESP-005', 'Auditorio de la facultad', null), auditorium);
  assert.deepEqual(locateSpace({ recurso: { codigo: 'ESP-005', nombre: 'Salón de actos' }, tipo: { codigo: 'T-9', nombre: 'Auditorio' }, piso: { numero: 4 } }), auditorium);
  // A catalog code is looked up as data: a code that happens to be an object member means nothing.
  for (const type of ['CONSTRUCTOR', 'TOSTRING', '__PROTO__', 'HASOWNPROPERTY']) {
    assert.deepEqual(record('AULA-11', 'Aula 11', type, 1), { floor: 1, room: 11, kind: 'aula' }, type);
  }
  assert.deepEqual(locateSpace({ recurso: { codigo: 'AULA-11', nombre: 'Aula 11' }, piso: { nombre: 'Piso constructor' } }), { floor: 1, room: 11, kind: 'aula' });
});

test('code and name are read separately and must not disagree, whichever of them is checked first', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const record = (codigo, nombre, tipo, piso = 4) => locateSpace({ recurso: { codigo, nombre }, ...(tipo ? { tipo: { codigo: tipo, nombre: tipo } } : {}), ...(piso ? { piso: { numero: piso } } : {}) });
  const auditorium = { floor: 4, room: 'auditorium', kind: 'auditorio' };
  // A room word glued to a number is still a room word: these codes are not neutral serials.
  assert.equal(record('LAB41', 'Salón de actos', 'AUDITORIO'), null);
  assert.equal(record('LAB41B', 'Salón de actos', 'AUDITORIO'), null);
  assert.equal(record('AUD02', 'Salón de actos', 'AUDITORIO'), null);
  assert.equal(record('AULA11B', 'Aula 11', 'AULA', 1), null);
  assert.deepEqual(record('AUD01', 'Salón de actos', 'AUDITORIO'), auditorium);
  assert.deepEqual(record('LAB41', 'Laboratorio 41', 'LABORATORIO'), { floor: 4, room: 41, kind: 'laboratorio' });
  // The stable code of another auditorium, or a name that says «auditorio», is never overridden by a room.
  assert.equal(record('AUD-02', 'Laboratorio 41', 'LABORATORIO'), null);
  assert.equal(record('AULA-11', 'Auditorio', 'AULA', 1), null);
  assert.equal(record('AULA-11', 'Auditorio', 'AULA', 4), null);
  assert.equal(record('AUD-01', 'Laboratorio 41', 'AUDITORIO'), null);
  assert.equal(record('ESP-007', 'Aula 11', 'AUDITORIO', 1), null);
  assert.equal(record('LAB-41', 'Laboratorio 41', 'AUDITORIO'), null);
  // A name that refers to several auditoriums, or to an auditorium and a room, identifies none.
  assert.equal(record('ESP-005', 'Auditorios norte y sur', 'T-9'), null);
  assert.equal(record('ESP-005', 'Auditorios norte y sur', 'AUDITORIO'), null);
  assert.equal(record('ESP-005', 'Auditorio y laboratorio', 'T-9'), null);
  // A descriptive name says nothing: the code decides, and a mention of the auditorium is not an identity.
  assert.deepEqual(record('AULA-11', 'Sala junto al auditorio', 'AULA', 1), { floor: 1, room: 11, kind: 'aula' });
  assert.deepEqual(record('AUD-01', 'Gran auditorio', 'AUDITORIO'), auditorium);
  assert.deepEqual(record('AUD-01', 'Salón de actos', null), auditorium);
  // Only the auditorium can be found by its catalog type alone; a room always needs its identifier.
  assert.deepEqual(record('ESP-006', 'Salón de actos', 'AUDITORIO'), auditorium);
  assert.equal(record('ESP-006', 'Salón de clases', 'AULA', 1), null);
  assert.equal(record('ESP-006', 'Sala de prácticas', 'LABORATORIO'), null);
});

test('a name that names two kinds of room, in either order, identifies none; a repeated identifier agrees', async () => {
  const { locateSpace } = await load('resources/js/building.js');
  const record = (codigo, nombre, tipo, piso = 4) => locateSpace({ recurso: { codigo, nombre }, ...(tipo ? { tipo: { codigo: tipo, nombre: tipo } } : {}), ...(piso ? { piso: { numero: piso } } : {}) });
  const auditorium = { floor: 4, room: 'auditorium', kind: 'auditorio' };
  // Word order does not change the answer.
  for (const name of ['Aula y auditorio', 'Auditorio y aula', 'Aula junto al auditorio', 'Aulas y laboratorios', 'Laboratorio y aula de apoyo']) {
    assert.equal(record('AULA-11', name, 'AULA', 1), null, name);
    assert.equal(record('AUD-01', name, 'AUDITORIO'), null, name);
  }
  assert.deepEqual(record('AULA-11', 'Sala junto al auditorio', 'AULA', 1), { floor: 1, room: 11, kind: 'aula' });
  assert.deepEqual(record('LAB-48', 'Laboratorio de robótica', 'LABORATORIO'), { floor: 4, room: 48, kind: 'laboratorio' });
  // A display name may repeat the identifier of the auditorium; another number is another auditorium.
  for (const name of ['Auditorio 01', 'Auditorio 1', 'Auditorio N° 1', 'Auditorio Nro. 01', 'Aud. 01', 'Auditorio']) {
    assert.deepEqual(record('AUD-01', name, 'AUDITORIO'), auditorium, name);
  }
  for (const name of ['Auditorio 02', 'Auditorio 10', 'Auditorio 1B', 'Auditorio 1 y 2']) assert.equal(record('AUD-01', name, 'AUDITORIO'), null, name);
  assert.deepEqual(record('ESP-006', 'Auditorio 01', 'T-9'), auditorium);
  // A malformed room code is unclear with any spelling the complete identifier accepts.
  for (const code of ['LABNRO.41B', 'LAB NRO 41B', 'LAB. N° 41-B', 'AULA N 11B', 'AULANRO11B', 'AUD NRO 2', 'AUDITORIO N° 3', 'LABS-41']) {
    assert.equal(record(code, 'Sala principal', 'AUDITORIO'), null, code);
    assert.equal(record(code, 'Sala principal', 'T-9'), null, code);
  }
  assert.deepEqual(record('LABNRO.41', 'Sala principal', 'LABORATORIO'), { floor: 4, room: 41, kind: 'laboratorio' });
  assert.deepEqual(record('AUD NRO 1', 'Sala principal', 'AUDITORIO'), auditorium);
  // Words that only resemble a room word stay neutral serials.
  for (const code of ['AUDIO-5', 'LABORAL-12', 'AULARIO-2', 'ESP-041']) assert.deepEqual(record(code, 'Salón de actos', 'AUDITORIO'), auditorium, code);
});
