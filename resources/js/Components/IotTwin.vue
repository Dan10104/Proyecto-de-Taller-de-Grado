<script setup>
import { computed, ref } from 'vue';
import Icon from './Icon.vue';
import IotEvidence from './IotEvidence.vue';
const props = defineProps({ sensors: { type: Array, default: () => [] } });
// Local display selection and geometry only. No requests, monitoring rules or state inference.
const floorChoice = ref(null), roomChoice = ref(null);
const floors = computed(() => {
  const groups = new Map();
  for (const sensor of props.sensors) {
    const space = sensor.espacio_actual;
    if (!space) continue;
    const id = space.piso_id ?? 'sin-referencia';
    if (!groups.has(id)) groups.set(id, { id, rooms: new Map() });
    const rooms = groups.get(id).rooms;
    if (!rooms.has(sensor.espacio_actual_id)) rooms.set(sensor.espacio_actual_id, { id: sensor.espacio_actual_id, space, sensors: [] });
    rooms.get(sensor.espacio_actual_id).sensors.push(sensor);
  }
  return Array.from(groups.values(), floor => ({ ...floor, rooms: Array.from(floor.rooms.values()) }));
});
const floor = computed(() => floors.value.find(f => f.id === floorChoice.value) || floors.value[0]);
const room = computed(() => floor.value?.rooms.find(r => r.id === roomChoice.value) || floor.value?.rooms[0]);
const mapHeight = computed(() => 150 + Math.ceil((floor.value?.rooms.length || 1) / 3) * 110);
const mapWidth = computed(() => Math.min(3, floor.value?.rooms.length || 1) * 205 + 145);
const roomPosition = index => `translate(${85 + index % 3 * 205 + (Math.floor(index / 3) % 2) * 24},${95 + Math.floor(index / 3) * 110})`;
</script>
<template>
  <section class="technology-canvas iot-twin" aria-labelledby="twin-heading">
    <div class="technology-heading"><div><h2 id="twin-heading"><Icon name="sensor"/>Mapa de evidencia IoT</h2><p>Espacios y sensores de esta página · distribución esquemática</p></div><span class="technology-label">Ocupación: no determinable</span></div>
    <div v-if="floor" class="twin-layout">
      <div class="twin-map">
        <div class="floor-picker" aria-label="Pisos recibidos en esta página">
          <button v-for="f in floors" :key="f.id" type="button" :aria-pressed="floor.id === f.id" @click="floorChoice = f.id; roomChoice = null"><Icon name="building"/>{{ f.id === 'sin-referencia' ? 'Sin referencia de piso' : `Piso · ref. ${f.id}` }}</button>
        </div>
        <svg class="twin-plan" :viewBox="`0 0 ${mapWidth} ${mapHeight + 12}`" role="group" aria-label="Maqueta esquemática de los espacios con sensores recibidos">
          <path :d="`M24 80 L${mapWidth - 78} 30 L${mapWidth - 10} ${mapHeight - 66} L92 ${mapHeight - 16} Z`" class="twin-base"/>
          <path :d="`M24 80 V100 L92 ${mapHeight + 4} L${mapWidth - 10} ${mapHeight - 46} V${mapHeight - 66} L92 ${mapHeight - 16} Z`" class="twin-base-edge"/>
          <g v-for="(r, index) in floor.rooms" :key="r.id" :transform="roomPosition(index)" class="twin-room" :class="{selected: room?.id === r.id}" role="button" tabindex="0" :aria-pressed="room?.id === r.id" :aria-label="`${r.space.recurso.nombre}. Seleccionar espacio y consultar sus sensores.`" @click="roomChoice = r.id" @keydown.enter.prevent="roomChoice = r.id" @keydown.space.prevent="roomChoice = r.id">
            <title>{{ r.space.recurso.nombre }} · {{ r.space.recurso.codigo }} · Estado operativo {{ r.space.estado }}. Ocupación no determinable.</title>
            <path d="M0 0 90 34 180 0 V24 L90 58 0 24Z" class="room-side"/>
            <path d="M0 0 90 -34 180 0 90 34Z" class="room-roof"/>
            <path d="M12 -5 90 -34 168 -5 M90 34V58" class="room-edge"/>
            <text x="90" y="-2" text-anchor="middle" class="room-name">{{ r.space.recurso.nombre.length > 24 ? r.space.recurso.nombre.slice(0, 22) + '…' : r.space.recurso.nombre }}</text>
            <text x="90" y="14" text-anchor="middle" class="room-ref">Espacio #{{ r.id }}</text>
            <g v-for="(sensor, pin) in r.sensors" :key="sensor.id" :transform="`translate(${78 + pin * 24},-48)`" class="sensor-pin" :class="{enabled: sensor.estado === 'HABILITADO'}">
              <title>{{ sensor.identificador }} · {{ sensor.estado }} · {{ sensor.comunicacion === 'RECIENTE' ? 'Comunicación reciente' : 'Sin comunicación reciente' }}</title>
              <line y1="0" y2="16"/><circle r="9"/><circle r="3" class="sensor-pin-center"/>
            </g>
          </g>
        </svg>
        <div class="twin-legend"><span><i class="pin-enabled"></i>Sensor habilitado</span><span><i></i>Sensor deshabilitado</span><span>Selecciona un espacio</span></div>
        <p class="twin-map-note">La referencia identifica el piso en los datos; no indica su número físico. La geometría no representa el plano real.</p>
      </div>
      <aside v-if="room" class="twin-inspector" aria-label="Espacio seleccionado">
        <div class="twin-room-title"><Icon name="building"/><h3>{{ room.space.recurso.nombre }}</h3></div>
        <p class="technology-reference">{{ room.space.recurso.codigo }}</p>
        <dl class="twin-space-facts"><div><dt>Estado operativo</dt><dd>{{ room.space.estado.replaceAll('_', ' ') }}</dd></div><div><dt>Capacidad</dt><dd>{{ room.space.capacidad }} personas</dd></div></dl>
        <div v-for="sensor in room.sensors" :key="sensor.id" class="twin-sensor-detail">
          <strong><Icon name="sensor"/>{{ sensor.identificador }}</strong>
          <span class="sensor-config" :class="{enabled:sensor.estado === 'HABILITADO'}">{{ sensor.estado === 'HABILITADO' ? 'Habilitado' : 'Deshabilitado' }}</span>
          <p>{{ sensor.comunicacion === 'RECIENTE' ? 'Comunicación reciente' : 'Sin comunicación reciente' }}</p>
          <IotEvidence :readings="sensor.lecturas" compact/>
        </div>
      </aside>
    </div>
    <p v-else class="technology-empty">No hay sensores actuales para representar con estos filtros.</p>
    <div class="technology-foot"><Icon name="shield"/><span>Una lectura ambiental no confirma disponibilidad. El historial y el listado completo permanecen debajo.</span></div>
  </section>
</template>
