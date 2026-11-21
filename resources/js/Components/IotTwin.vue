<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Icon from './Icon.vue';
import IotEvidence from './IotEvidence.vue';
import StatusBadge from './StatusBadge.vue';
import { BUILDING_FLOORS, BUILDING_NOTE, ROOM_KINDS, locateSpace, roomName } from '../building.js';

const props = defineProps({ sensors: { type: Array, default: () => [] } });

// Selección local y geometría de presentación. Sin peticiones, reglas de monitoreo ni inferencia de ocupación.
const choice = ref(null);

// Misma vigencia que aplica IotEvidence: una lectura deja de contarse como reciente al vencer su ventana.
const elapsed = ref(0);
let received = performance.now(), timer;
watch(() => props.sensors, () => { received = performance.now(); elapsed.value = 0; });
onMounted(() => { timer = setInterval(() => { elapsed.value = Math.floor((performance.now() - received) / 1000); }, 1000); });
onBeforeUnmount(() => clearInterval(timer));
const hasRecent = sensor => (sensor.lecturas || []).some(reading => elapsed.value <= reading.vigente_por_segundos);
const sensorState = sensor => (sensor.estado !== 'HABILITADO' ? 'off' : hasRecent(sensor) ? 'recent' : 'idle');
const stateText = { recent: 'Lectura reciente', idle: 'Sin lectura reciente', off: 'Sensor deshabilitado' };
const moment = new Intl.DateTimeFormat('es-BO', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'America/La_Paz' });
const windowText = sensor => (sensor.comunicacion === 'RECIENTE' ? 'dentro de la ventana de comunicación' : 'fuera de la ventana de comunicación');
const communication = sensor => (sensor.ultima_comunicacion ? `Última comunicación: ${moment.format(new Date(sensor.ultima_comunicacion))} · ${windowText(sensor)}` : 'Sin comunicación registrada');

// Espacios con sensores recibidos en esta página. Los que no pueden ubicarse en la maqueta se listan aparte.
const groups = computed(() => {
  const map = new Map();
  for (const sensor of props.sensors) {
    const id = sensor.espacio_actual_id ?? 'sin-espacio';
    if (!map.has(id)) {
      const space = sensor.espacio_actual || null;
      map.set(id, { id, space, place: space ? locateSpace(space) : null, cell: null, sensors: [] });
    }
    map.get(id).sensors.push(sensor);
  }
  const list = [...map.values()];
  // Si dos espacios distintos caen en el mismo ambiente del dibujo, ninguno se dibuja:
  // la interfaz no elige uno por el orden en que llegan los sensores.
  const cellOf = group => `${group.place.floor}-${group.place.room}`;
  const uses = new Map();
  for (const group of list) if (group.place) uses.set(cellOf(group), (uses.get(cellOf(group)) || 0) + 1);
  for (const group of list) {
    if (!group.place) continue;
    if (uses.get(cellOf(group)) > 1) group.place = null;
    else group.cell = cellOf(group);
  }
  return list;
});
const placed = computed(() => groups.value.filter(group => group.place));
const others = computed(() => groups.value.filter(group => !group.place));
const byCell = computed(() => new Map(placed.value.map(group => [group.cell, group])));
const selected = computed(() => groups.value.find(group => group.id === choice.value) || groups.value[0] || null);
const spaceName = group => group.space?.recurso?.nombre || 'Sin espacio asociado';
// El piso se lee del registro del espacio; la maqueta provisional nunca lo sustituye.
const floorName = group => group?.space?.piso?.nombre || '';
const groupState = group => (group.sensors.some(hasRecent) ? 'recent' : group.sensors.every(sensor => sensor.estado !== 'HABILITADO') ? 'off' : 'idle');
const recentCount = computed(() => props.sensors.filter(hasRecent).length);

const featureLabels = { projector: 'Proyector', air: 'Aire acondicionado' };
const view = computed(() => {
  const group = selected.value;
  if (!group) return null;
  const kind = group.place ? ROOM_KINDS[group.place.kind] : null;
  const recent = group.sensors.filter(hasRecent).length;
  return {
    group,
    space: group.space,
    name: spaceName(group),
    // Piso del registro (si llega) y tipo; la posición en el dibujo se declara provisional.
    where: [floorName(group), group.place ? `${kind.label} · posición provisional en el dibujo` : 'Sin ubicación en la distribución provisional'].filter(Boolean).join(' · '),
    features: kind?.features || [],
    recent,
    recentText: recent === 0 ? 'No hay lecturas recientes' : recent === 1 ? 'Sí, de 1 sensor' : `Sí, de ${recent} sensores`,
  };
});

// Isometría 2:1. Unidades de planta: x recorre el pasillo desde la escalera; y cruza de izquierda a derecha.
const U = 24, LENGTH = 7.4, WIDTH = 4.4, DEPTH = 1.8, THICK = 7, PITCH = 122, OX = 166, OY = 16, INSET = 0.07;
const VIEW = { width: 358, height: 554 };
const TOP = Math.max(...BUILDING_FLOORS.map(floor => floor.number));
const BOTTOM = Math.min(...BUILDING_FLOORS.map(floor => floor.number));
const round = value => Math.round(value * 10) / 10;
const at = (o, x, y, z = 0) => [round(OX + (x - y) * U), round(o + (x + y) * U / 2 - z)];
const join = points => points.map(point => point.join(',')).join(' ');
const quad = (o, x, y, w, h, z = 0) => join([at(o, x, y, z), at(o, x + w, y, z), at(o, x + w, y + h, z), at(o, x, y + h, z)]);
const line = (o, x1, y1, x2, y2, z = 0) => `M${at(o, x1, y1, z).join(' ')}L${at(o, x2, y2, z).join(' ')}`;
const ring = (o, x, y, w, h, z = 0) => `M${at(o, x, y, z).join(' ')}L${at(o, x + w, y, z).join(' ')}L${at(o, x + w, y + h, z).join(' ')}L${at(o, x, y + h, z).join(' ')}Z`;
const spanOf = (item, side) => (item === 'stairs' ? 1.3 : item === 'elevator' ? (side.includes('auditorium') ? 0.7 : 1.3) : item === 'auditorium' ? 1.7 : typeof item === 'string' ? 0.9 : null);
const serviceTitles = { stairs: 'Escalera', elevator: 'Ascensor', 'restroom-men': 'Baños de hombres', 'restroom-women': 'Baños de mujeres' };

function serviceShape(o, cell) {
  const { x, y, w, h, item } = cell;
  const cx = x + w / 2, cy = y + h / 2;
  let glyph = '', text = null;
  if (item === 'stairs') glyph = [0.2, 0.35, 0.5, 0.65, 0.8].map(step => line(o, x + w * step, y + 0.25, x + w * step, y + h - 0.25)).join('');
  else if (item === 'elevator') {
    const half = Math.min(w - 0.2, 0.7) / 2;
    glyph = ring(o, cx - half, cy - half, half * 2, half * 2) + line(o, cx - half, cy - half, cx + half, cy + half) + line(o, cx - half, cy + half, cx + half, cy - half);
  } else text = item === 'restroom-men' ? 'H' : 'M';
  const [tx, ty] = at(o, cx, cy);
  return { key: cell.key, title: serviceTitles[item], top: quad(o, x, y, w, h), glyph, text, tx, ty: round(ty + 3.5) };
}

// Maqueta fija: placas, pasillo, servicios y ambientes de cada piso.
const scaffold = BUILDING_FLOORS.map(floor => {
  const o = OY + (TOP - floor.number) * PITCH;
  const cells = [];
  for (const [name, y0] of [['left', 0], ['right', WIDTH - DEPTH]]) {
    const side = floor[name];
    const fixed = side.reduce((sum, item) => sum + (spanOf(item, side) || 0), 0);
    const share = (LENGTH - fixed) / Math.max(1, side.filter(item => typeof item === 'number').length);
    let x0 = 0;
    for (const item of side) {
      const span = spanOf(item, side) ?? share;
      const isRoom = typeof item === 'number' || item === 'auditorium';
      cells.push({ key: `${floor.number}-${item}`, item, isRoom, kind: item === 'auditorium' ? 'auditorio' : isRoom ? floor.kind : null, x: x0 + INSET, y: y0 + INSET, w: span - INSET * 2, h: DEPTH - INSET * 2 });
      x0 += span;
    }
  }
  const [lx, ly] = at(o, 0, WIDTH), [rx, ry] = at(o, LENGTH, 0);
  return {
    number: floor.number,
    o,
    top: quad(o, 0, 0, LENGTH, WIDTH),
    left: join([at(o, 0, WIDTH), at(o, LENGTH, WIDTH), at(o, LENGTH, WIDTH, -THICK), at(o, 0, WIDTH, -THICK)]),
    right: join([at(o, LENGTH, 0), at(o, LENGTH, WIDTH), at(o, LENGTH, WIDTH, -THICK), at(o, LENGTH, 0, -THICK)]),
    edge: join([at(o, 0, WIDTH), at(o, LENGTH, WIDTH), at(o, LENGTH, 0)]),
    hall: quad(o, 0, DEPTH, LENGTH, WIDTH - DEPTH * 2),
    axis: line(o, 0.3, WIDTH / 2, LENGTH - 0.3, WIDTH / 2),
    labelY: round(ly + 4.5),
    tick: `M46 ${ly}H${round(lx - 3)}`,
    links: floor.number > BOTTOM ? `M${lx} ${round(ly + THICK)}V${round(ly + PITCH)}M${rx} ${round(ry + THICK)}V${round(ry + PITCH)}` : '',
    services: cells.filter(cell => !cell.isRoom).map(cell => serviceShape(o, cell)),
    rooms: cells.filter(cell => cell.isRoom),
  };
});
const ground = quad(OY + (TOP - BOTTOM) * PITCH, -0.25, -0.25, LENGTH + 0.5, WIDTH + 0.5, -THICK - 9);

// Un ambiente con sensores se eleva un poco; el seleccionado, más. La altura es dibujo, no un dato.
function roomShape(floor, cell, group, isSelected) {
  const { x, y, w, h } = cell, o = floor.o;
  const z = isSelected ? 11 : group ? 4 : 0;
  const cx = x + w / 2;
  const [tx, ty] = at(o, cx, y + h - 0.5, z);
  const sensors = group?.sensors || [];
  const step = Math.min(0.36, (w - 0.3) / Math.max(1, sensors.length));
  return {
    key: cell.key,
    kind: cell.kind,
    group,
    selected: isSelected,
    title: group ? [spaceName(group), floorName(group)].filter(Boolean).join(' · ') : `${roomName(cell.item, cell.kind)} · Piso ${floor.number} de la distribución provisional`,
    text: cell.item === 'auditorium' ? 'Aud.' : cell.item,
    top: quad(o, x, y, w, h, z),
    wallLeft: z ? join([at(o, x, y + h, z), at(o, x + w, y + h, z), at(o, x + w, y + h), at(o, x, y + h)]) : null,
    wallRight: z ? join([at(o, x + w, y, z), at(o, x + w, y + h, z), at(o, x + w, y + h), at(o, x + w, y)]) : null,
    mark: cell.kind === 'laboratorio' ? ring(o, x + 0.14, y + 0.14, w - 0.28, h - 0.28, z)
      : cell.kind === 'auditorio' ? [0.3, 0.5, 0.7].map(row => line(o, x + w * row, y + 0.22, x + w * row, y + h - 0.85, z)).join('') : null,
    tx,
    ty: round(ty + 4),
    pins: sensors.map((sensor, index) => {
      const [px, py] = at(o, cx + (index - (sensors.length - 1) / 2) * step, y + 0.5, z);
      const state = sensorState(sensor);
      return { id: sensor.id, x: px, y: py, state, title: `${sensor.identificador} · ${stateText[state]}` };
    }),
  };
}
const floors = computed(() => scaffold.map(floor => ({
  ...floor,
  active: selected.value?.place?.floor === floor.number,
  rooms: floor.rooms.map(cell => {
    const group = byCell.value.get(cell.key) || null;
    return roomShape(floor, cell, group, !!group && group.id === selected.value?.id);
  }),
})));
</script>
<template>
  <section class="tech-surface iot-twin" aria-labelledby="twin-heading">
    <div class="twin-head">
      <div>
        <h2 id="twin-heading">Edificio y sensores</h2>
        <p>Seleccione un espacio para ver sus sensores y lo que el sistema puede concluir.</p>
      </div>
      <dl v-if="sensors.length" class="twin-figures">
        <div><dd>{{ recentCount }}</dd><dt>sensores con lectura reciente</dt></div>
        <div><dd>{{ sensors.length - recentCount }}</dd><dt>sensores sin lectura reciente</dt></div>
      </dl>
      <span class="tech-chip twin-verdict"><i aria-hidden="true"></i>Ocupación: no determinable</span>
    </div>

    <div class="twin-layout">
      <div class="twin-stage">
        <svg class="twin-building" :viewBox="`0 0 ${VIEW.width} ${VIEW.height}`" role="img" aria-label="Edificio en isometría con sus pisos apilados. Los espacios con sensores se seleccionan también en la lista de espacios monitoreados.">
          <polygon class="twin-ground" :points="ground"/>
          <g v-for="floor in floors" :key="floor.number" class="twin-floor" :class="{ 'is-active': floor.active }">
            <path v-if="floor.links" class="twin-link" :d="floor.links"/>
            <polygon class="twin-plate-left" :points="floor.left"/>
            <polygon class="twin-plate-right" :points="floor.right"/>
            <polygon class="twin-plate-top" :points="floor.top"/>
            <polyline class="twin-plate-edge" :points="floor.edge"/>
            <polygon class="twin-hall" :points="floor.hall"/>
            <path class="twin-axis" :d="floor.axis"/>
            <g v-for="service in floor.services" :key="service.key" class="twin-service">
              <title>{{ service.title }}</title>
              <polygon :points="service.top"/>
              <path v-if="service.glyph" :d="service.glyph"/>
              <text v-if="service.text" :x="service.tx" :y="service.ty">{{ service.text }}</text>
            </g>
            <g v-for="room in floor.rooms" :key="room.key" class="twin-cell" :class="[`is-${room.kind}`, { 'has-sensors': room.group, 'is-selected': room.selected }]" @click="room.group && (choice = room.group.id)">
              <title>{{ room.title }}</title>
              <polygon v-if="room.wallLeft" class="cell-wall-left" :points="room.wallLeft"/>
              <polygon v-if="room.wallRight" class="cell-wall-right" :points="room.wallRight"/>
              <polygon class="cell-top" :points="room.top"/>
              <path v-if="room.mark" class="cell-mark" :d="room.mark"/>
              <text class="cell-label" :x="room.tx" :y="room.ty">{{ room.text }}</text>
              <g v-for="pin in room.pins" :key="pin.id" class="twin-pin" :class="`is-${pin.state}`" :transform="`translate(${pin.x} ${pin.y})`">
                <title>{{ pin.title }}</title>
                <ellipse v-if="pin.state === 'recent'" class="pin-pulse" rx="4.5" ry="2.25"/>
                <ellipse class="pin-base" rx="4.5" ry="2.25"/>
                <path class="pin-stem" d="M0 0V-9"/>
                <circle class="pin-head" cy="-11.5" r="3"/>
                <path v-if="pin.state === 'off'" class="pin-slash" d="M-3.2 -8.3 3.2 -14.7"/>
              </g>
            </g>
            <path class="twin-tick" :d="floor.tick"/>
            <text class="twin-floor-label" x="6" :y="floor.labelY">Piso {{ floor.number }}</text>
          </g>
        </svg>

        <div v-if="placed.length" class="twin-strip">
          <h3 id="twin-placed-heading">Espacios monitoreados</h3>
          <div class="twin-chips" role="group" aria-labelledby="twin-placed-heading">
            <button v-for="group in placed" :key="group.id" type="button" class="twin-space" :class="{ active: group.id === selected?.id }" :aria-pressed="group.id === selected?.id" @click="choice = group.id">
              <span class="twin-dot" :class="`is-${groupState(group)}`" aria-hidden="true"></span>
              <span class="twin-space-name">{{ spaceName(group) }}</span>
              <small v-if="floorName(group)">{{ floorName(group) }}</small>
              <span class="sr-only">. {{ stateText[groupState(group)] }}</span>
            </button>
          </div>
        </div>
        <div v-if="others.length" class="twin-strip">
          <h3 id="twin-others-heading">Otros espacios monitoreados</h3>
          <p>Tienen sensores, pero no pudieron ubicarse en la distribución provisional.</p>
          <div class="twin-chips" role="group" aria-labelledby="twin-others-heading">
            <button v-for="group in others" :key="group.id" type="button" class="twin-space" :class="{ active: group.id === selected?.id }" :aria-pressed="group.id === selected?.id" @click="choice = group.id">
              <span class="twin-dot" :class="`is-${groupState(group)}`" aria-hidden="true"></span>
              <span class="twin-space-name">{{ spaceName(group) }}</span>
              <span class="sr-only">. {{ stateText[groupState(group)] }}</span>
            </button>
          </div>
        </div>

        <ul class="twin-legend" aria-label="Leyenda del edificio">
          <li><span class="twin-dot is-recent" aria-hidden="true"></span>Lectura reciente</li>
          <li><span class="twin-dot is-idle" aria-hidden="true"></span>Sin lectura reciente</li>
          <li><span class="twin-dot is-off" aria-hidden="true"></span>Sensor deshabilitado</li>
          <li><span class="twin-swatch is-aula" aria-hidden="true"></span>Aula común</li>
          <li><span class="twin-swatch is-laboratorio" aria-hidden="true"></span>Laboratorio</li>
          <li><span class="twin-swatch is-auditorio" aria-hidden="true"></span>Auditorio</li>
          <li><span class="twin-swatch is-selected" aria-hidden="true"></span>Espacio seleccionado</li>
        </ul>
        <p class="twin-note">{{ BUILDING_NOTE }} Los ambientes atenuados no tienen sensores en esta página.</p>
      </div>

      <aside v-if="view" class="tech-panel twin-inspector" aria-labelledby="twin-space-name">
        <div class="twin-inspector-head">
          <h3 id="twin-space-name"><span class="sr-only">Espacio seleccionado: </span>{{ view.name }}</h3>
          <p>{{ view.where }}</p>
          <p v-if="view.features.length" class="twin-features-note">Habitual en este tipo de ambiente; no es el inventario del espacio:</p>
          <ul v-if="view.features.length" class="twin-features" aria-label="Equipamiento habitual de este tipo de ambiente">
            <li v-for="feature in view.features" :key="feature">
              <Icon v-if="feature === 'projector'" name="projector"/>
              <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18v8H3ZM7 9.5h10M8 17c0 1.6-1 2-1 3.5M12 17v3.5M16 17c0 1.6 1 2 1 3.5"/></svg>
              {{ featureLabels[feature] }}
            </li>
          </ul>
        </div>

        <dl class="twin-answers">
          <div>
            <dt>Lecturas recientes</dt>
            <dd><span class="twin-dot" :class="view.recent ? 'is-recent' : 'is-idle'" aria-hidden="true"></span>{{ view.recentText }}</dd>
          </div>
          <div>
            <dt>Qué puede concluir el sistema</dt>
            <dd><StatusBadge value="NO_DETERMINABLE" label="Ocupación: no determinable"/></dd>
            <dd class="twin-answer-note">Las lecturas describen el ambiente; no confirman que el espacio esté libre ni ocupado.</dd>
          </div>
        </dl>

        <h4 class="twin-subhead">Sensores de este espacio</h4>
        <ul class="twin-sensors">
          <li v-for="sensor in view.group.sensors" :key="sensor.id">
            <div class="twin-sensor-head">
              <span class="twin-dot" :class="`is-${sensorState(sensor)}`" aria-hidden="true"></span>
              <strong>{{ sensor.identificador }}</strong>
              <StatusBadge :value="sensor.estado"/>
            </div>
            <p>{{ sensor.tipo ? `${sensor.tipo} · ` : '' }}{{ communication(sensor) }}</p>
            <IotEvidence :readings="sensor.lecturas || []" :elapsed="elapsed" compact embedded/>
          </li>
        </ul>

        <details class="twin-details">
          <summary>Ver detalles técnicos del espacio</summary>
          <dl>
            <template v-if="view.space">
              <div><dt>Código del espacio</dt><dd class="twin-code">{{ view.space.recurso?.codigo }}</dd></div>
              <div><dt>Referencia del espacio</dt><dd>#{{ view.group.id }}</dd></div>
              <div v-if="view.space.piso_id != null"><dt>Piso en los datos</dt><dd>Piso · ref. {{ view.space.piso_id }}</dd></div>
              <div v-if="view.space.referencia_ubicacion"><dt>Referencia de ubicación registrada</dt><dd>{{ view.space.referencia_ubicacion }}</dd></div>
              <div><dt>Estado operativo</dt><dd><StatusBadge :value="view.space.estado"/></dd></div>
              <div><dt>Capacidad</dt><dd>{{ view.space.capacidad }} personas</dd></div>
            </template>
            <div v-for="sensor in view.group.sensors" :key="sensor.id"><dt>Sensor {{ sensor.identificador }}</dt><dd>Referencia #{{ sensor.id }}</dd></div>
          </dl>
          <p>La referencia de piso identifica el registro en los datos; no indica su número físico.</p>
        </details>
      </aside>
      <div v-else class="tech-panel twin-inspector twin-empty">
        <Icon name="sensor"/>
        <h3>No hay sensores actuales</h3>
        <p>No hay sensores para representar con estos filtros. El edificio se muestra sin sensores ni lecturas.</p>
      </div>
    </div>

    <p class="twin-foot"><Icon name="shield"/><span>Una lectura ambiental no confirma disponibilidad. El historial y el listado completo permanecen debajo.</span></p>
  </section>
</template>
