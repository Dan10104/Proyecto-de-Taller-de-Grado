<script setup>
import { ref } from 'vue';
import { Link, useForm, usePoll } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import IotTwin from '../../Components/IotTwin.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import IotEvidence from '../../Components/IotEvidence.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { dateLabel, useAccess } from '../../security';
const props = defineProps({ sensores: Object, eventos: Object, filtros: Object, consultado: String, ventana: Number });
const { can } = useAccess();
const filters = useForm({ sensor_id: props.filtros.sensor_id || '', espacio_id: props.filtros.espacio_id || '', tipo: props.filtros.tipo || '', desde: props.filtros.desde || '', hasta: props.filtros.hasta || '' });
usePoll(3000, { only: ['sensores', 'eventos', 'consultado'], preserveScroll: true });
function search() { filters.get('/iot/monitoreo', { preserveState: true }); }

// Solo presentación: los filtros se muestran plegados salvo que ya haya alguno aplicado.
const filtersOpen = ref(Object.values(props.filtros || {}).some(value => value !== '' && value !== null && value !== undefined));
</script>
<template>
  <AppLayout title="Monitoreo de ocupación" section="IoT">
    <div class="page-heading">
      <div>
        <h1>Monitoreo de ocupación</h1>
        <p>Se actualiza cada 3 segundos · última consulta: {{ consultado }} (hora de Bolivia).</p>
      </div>
      <Link v-if="can('iot.sensores.ver')" href="/iot/sensores" class="btn btn-secondary">Gestionar sensores</Link>
    </div>

    <IotTwin :sensors="sensores.data"/>

    <details class="disclosure">
      <summary>Cómo interpretar las lecturas</summary>
      <p>Una lectura reciente requiere medición y recepción dentro de {{ ventana }} segundos. La comunicación reciente por sí sola no demuestra ocupación. La interpretación depende del dispositivo.</p>
    </details>

    <details class="disclosure iot-filter-disclosure" :open="filtersOpen" @toggle="filtersOpen = $event.target.open">
      <summary>Filtrar historial</summary>
      <form class="space-filters iot-filters" @submit.prevent="search">
        <FormErrors :errors="filters.errors"/>
        <div class="space-filter-grid">
          <InputField id="sensor-filter" label="Referencia del sensor" type="number" v-model="filters.sensor_id" min="1" :error="filters.errors.sensor_id"/>
          <InputField id="space-filter" label="Referencia del espacio" type="number" v-model="filters.espacio_id" min="1" :error="filters.errors.espacio_id"/>
          <InputField id="type-filter" label="Tipo de evento" v-model="filters.tipo" :options="[{value:'PRESENCIA',label:'Presencia'},{value:'OCUPACION',label:'Ocupación'}]" placeholder="Todos"/>
          <InputField id="from-filter" label="Medición desde (incluido)" v-model="filters.desde" :error="filters.errors.desde"/>
          <InputField id="until-filter" label="Medición hasta (excluido)" v-model="filters.hasta" :error="filters.errors.hasta"/>
        </div>
        <div class="filter-actions">
          <p class="hint">Las fechas filtran el historial por el instante de medición e incluyen su zona horaria. Formato: AAAA-MM-DDThh:mm:ss seguido de la zona (-04:00 en Bolivia).</p>
          <div class="button-group">
            <Link href="/iot/monitoreo" class="text-link">Limpiar filtros</Link>
            <button class="btn btn-secondary">Aplicar filtros</button>
          </div>
        </div>
      </form>
    </details>

    <section class="panel" aria-labelledby="iot-readings-heading">
      <div class="panel-heading">
        <h2 id="iot-readings-heading">Lecturas actuales por sensor</h2>
        <p>Ocupación: no determinable · una lectura no confirma que el espacio esté libre</p>
      </div>
      <div v-if="sensores.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Lecturas actuales por sensor, tabla desplazable">
        <table class="iot-readings">
          <caption class="sr-only">Lecturas actuales por sensor</caption>
          <thead><tr><th>Sensor</th><th>Espacio</th><th>Estado</th><th>Lecturas recientes</th></tr></thead>
          <tbody>
            <tr v-for="sensor in sensores.data" :key="sensor.id">
              <td><strong>{{ sensor.identificador }}</strong><small class="cell-subline">Sensor #{{ sensor.id }}</small></td>
              <td>{{ sensor.espacio_actual.recurso.nombre }}<small class="cell-subline">{{ sensor.espacio_actual.recurso.codigo }} · espacio #{{ sensor.espacio_actual_id }}</small></td>
              <td><StatusBadge :value="sensor.estado"/><small class="cell-subline">{{ sensor.ultima_comunicacion ? 'Última comunicación: ' + dateLabel(sensor.ultima_comunicacion) + (sensor.comunicacion === 'RECIENTE' ? ' · dentro de la ventana de comunicación' : ' · fuera de la ventana de comunicación') : 'Sin comunicación registrada' }}</small></td>
              <td><IotEvidence :readings="sensor.lecturas" embedded/></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="sensor"/>
        <h3>No hay sensores actuales con estos filtros</h3>
        <p>Ajuste los filtros o registre un sensor para ver sus lecturas.</p>
      </div>
      <Pagination :data="sensores"/>
    </section>

    <section class="panel" aria-labelledby="iot-events-heading">
      <div class="panel-heading">
        <h2 id="iot-events-heading">Historial de eventos</h2>
        <p>{{ eventos.total }} {{ eventos.total === 1 ? 'evento' : 'eventos' }}</p>
      </div>
      <div v-if="eventos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Historial IoT desplazable">
        <table>
          <caption class="sr-only">Eventos ambientales, ordenados por medición</caption>
          <thead><tr><th>Medición · Bolivia</th><th>Sensor</th><th>Espacio histórico</th><th>Tipo y valor</th></tr></thead>
          <tbody>
            <tr v-for="event in eventos.data" :key="event.id">
              <td class="date-cell">{{ dateLabel(event.fecha_evento) }}<small class="cell-subline">Recepción: {{ dateLabel(event.fecha_recepcion) }}</small></td>
              <td>{{ event.sensor.identificador }}<small class="cell-subline">Evento #{{ event.id }}</small></td>
              <td>{{ event.espacio_historico.recurso.nombre }}<small class="cell-subline">{{ event.espacio_historico.recurso.codigo }}</small></td>
              <td>{{ event.tipo === 'PRESENCIA' ? 'Presencia' : event.tipo === 'OCUPACION' ? 'Ocupación' : event.tipo }}<small class="cell-subline">Valor informado: {{ event.valor ?? 'No informado' }}</small></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="clock"/>
        <h3>Sin eventos para este periodo</h3>
        <p>El historial aparecerá cuando se acepten lecturas del dispositivo.</p>
      </div>
      <Pagination :data="eventos"/>
    </section>
  </AppLayout>
</template>
