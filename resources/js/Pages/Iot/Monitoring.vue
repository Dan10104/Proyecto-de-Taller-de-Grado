<script setup>
import { Link, useForm, usePoll } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import IotTwin from '../../Components/IotTwin.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import IotEvidence from '../../Components/IotEvidence.vue';
import Icon from '../../Components/Icon.vue';
import { dateLabel, useAccess } from '../../security';
const props = defineProps({ sensores: Object, eventos: Object, filtros: Object, consultado: String, ventana: Number });
const { can } = useAccess();
const filters = useForm({ sensor_id: props.filtros.sensor_id || '', espacio_id: props.filtros.espacio_id || '', tipo: props.filtros.tipo || '', desde: props.filtros.desde || '', hasta: props.filtros.hasta || '' });
usePoll(3000, { only: ['sensores', 'eventos', 'consultado'], preserveScroll: true });
function search() { filters.get('/iot/monitoreo', { preserveState: true }); }
</script>
<template>
  <AppLayout title="Monitoreo de ocupación" section="IoT">
    <div class="page-heading"><div><h1>Monitoreo de ocupación</h1><p>Consulta: {{ consultado }} · Bolivia. Actualización cada 3 segundos.</p></div><Link v-if="can('iot.sensores.ver')" href="/iot/sensores" class="btn btn-secondary">Gestionar sensores</Link></div>
    <IotTwin :sensors="sensores.data"/>
    <details class="compact-help"><summary>Interpretación y vigencia de las lecturas</summary><p>Una lectura reciente requiere medición y recepción dentro de {{ ventana }} segundos. La comunicación reciente por sí sola no demuestra ocupación. La interpretación depende del dispositivo.</p></details>
    <form class="panel form-panel sensor-filters" @submit.prevent="search"><FormErrors :errors="filters.errors"/><div class="form-grid">
      <InputField id="sensor-filter" label="Referencia del sensor" type="number" v-model="filters.sensor_id" min="1" :error="filters.errors.sensor_id"/>
      <InputField id="space-filter" label="Referencia del espacio" type="number" v-model="filters.espacio_id" min="1" :error="filters.errors.espacio_id"/>
      <InputField id="type-filter" label="Tipo de evento" v-model="filters.tipo" :options="[{value:'PRESENCIA',label:'Presencia'},{value:'OCUPACION',label:'Ocupación'}]" placeholder="Todos"/>
      <InputField id="from-filter" label="Medición desde (incluido)" v-model="filters.desde" placeholder="2026-10-23T08:00:00-04:00" :error="filters.errors.desde"/>
      <InputField id="until-filter" label="Medición hasta (excluido)" v-model="filters.hasta" placeholder="2026-10-23T18:00:00-04:00" :error="filters.errors.hasta"/>
    </div><p class="hint">Las fechas filtran el historial por el instante de medición e incluyen su zona horaria.</p><div class="form-actions"><Link href="/iot/monitoreo" class="btn btn-secondary">Limpiar filtros</Link><button class="btn btn-primary">Aplicar filtros</button></div></form>
    <h2 class="sensor-section-title"><Icon name="signal"/>Lecturas actuales por sensor</h2><div class="space-card-grid"><article v-for="sensor in sensores.data" :key="sensor.id" class="panel space-card sensor-card"><div class="sensor-card-head"><div><span class="section-context">Sensor #{{ sensor.id }}</span><h3>{{ sensor.identificador }}</h3></div><Icon name="sensor"/></div><p class="sensor-location">{{ sensor.espacio_actual.recurso.codigo }} · {{ sensor.espacio_actual.recurso.nombre }} (#{{ sensor.espacio_actual_id }})</p><span class="trace-status" :class="{recorded: sensor.estado === 'HABILITADO'}">{{ sensor.estado === 'HABILITADO' ? 'Habilitado' : 'Deshabilitado' }}</span><span class="sensor-communication">{{ sensor.comunicacion === 'RECIENTE' ? 'Comunicación reciente' : 'Sin comunicación reciente' }}</span><IotEvidence :readings="sensor.lecturas"/></article></div><p v-if="!sensores.data.length" class="panel empty-state">No hay sensores actuales con estos filtros.</p><Pagination :data="sensores"/>
    <section class="panel"><div class="panel-heading"><h2>Historial de eventos · {{ eventos.total }}</h2></div><div v-if="eventos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Historial IoT desplazable"><table><caption class="sr-only">Eventos ambientales, ordenados por medición</caption><thead><tr><th>Evento</th><th>Sensor</th><th>Espacio histórico</th><th>Tipo / valor</th><th>Medición · Bolivia</th><th>Recepción · Bolivia</th></tr></thead><tbody><tr v-for="event in eventos.data" :key="event.id"><td>#{{ event.id }}</td><td>{{ event.sensor.identificador }}</td><td>{{ event.espacio_historico.recurso.codigo }}<p>{{ event.espacio_historico.recurso.nombre }}</p></td><td>{{ event.tipo }}<p>{{ event.valor ?? 'No informado' }}</p></td><td>{{ dateLabel(event.fecha_evento) }}</td><td>{{ dateLabel(event.fecha_recepcion) }}</td></tr></tbody></table></div><div v-else class="empty-state"><h3>Sin eventos para este periodo</h3><p>El historial aparecerá cuando se acepten lecturas del dispositivo.</p></div><Pagination :data="eventos"/></section>
  </AppLayout>
</template>
