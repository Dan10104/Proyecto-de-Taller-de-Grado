<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess, dateLabel } from '../../security';
const props = defineProps({ sensores: Object, filtros: Object, espacios: Array });
const { can } = useAccess();
const filters = useForm({ q: props.filtros.q || '', estado: props.filtros.estado || '', espacio_id: props.filtros.espacio_id || '' });
function search() { filters.get('/iot/sensores', { preserveState: true }); }
</script>
<template>
  <AppLayout title="Sensores IoT" section="IoT">
    <div class="page-heading">
      <div>
        <h1>Sensores IoT</h1>
        <p>Dispositivos asociados a cada espacio y su última comunicación.</p>
      </div>
      <Link v-if="can('iot.sensores.gestionar')" href="/iot/sensores/crear" class="btn btn-primary"><Icon name="plus"/>Registrar sensor</Link>
    </div>

    <form class="panel space-filters" @submit.prevent="search">
      <div class="space-filter-grid">
        <InputField id="iot-search" label="Identificador o tipo" v-model="filters.q" maxlength="120"/>
        <InputField id="iot-state" label="Estado" v-model="filters.estado" :options="[{value:'HABILITADO',label:'Habilitado'},{value:'DESHABILITADO',label:'Deshabilitado'}]" placeholder="Todos"/>
        <InputField id="iot-space" label="Espacio actual" v-model="filters.espacio_id" :options="espacios.map(s=>({value:s.recurso_id,label:`${s.recurso.codigo} · ${s.recurso.nombre}`}))" placeholder="Todos"/>
      </div>
      <div class="filter-actions">
        <div class="button-group">
          <Link href="/iot/sensores" class="text-link">Limpiar filtros</Link>
          <button class="btn btn-secondary" :disabled="filters.processing">Buscar</button>
        </div>
      </div>
    </form>

    <section class="panel" aria-labelledby="iot-sensors-heading">
      <div class="panel-heading">
        <h2 id="iot-sensors-heading">Sensores registrados</h2>
        <p>{{ sensores.total }} {{ sensores.total === 1 ? 'sensor' : 'sensores' }}</p>
      </div>
      <div v-if="sensores.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Sensores, tabla desplazable">
        <table>
          <caption class="sr-only">Catálogo de sensores IoT</caption>
          <thead><tr><th>Sensor</th><th>Espacio actual</th><th>Estado</th><th>Comunicación</th><th class="align-right">Acción</th></tr></thead>
          <tbody>
            <tr v-for="sensor in sensores.data" :key="sensor.id">
              <td><strong>{{ sensor.identificador }}</strong><small class="cell-subline">{{ sensor.tipo }}</small></td>
              <td>{{ sensor.espacio_actual.recurso.nombre }}<small class="cell-subline">{{ sensor.espacio_actual.recurso.codigo }}</small></td>
              <td><StatusBadge :value="sensor.estado"/></td>
              <td>{{ sensor.comunicacion === 'RECIENTE' ? 'Reciente' : 'Sin comunicación reciente' }}<small class="cell-subline">{{ sensor.ultima_comunicacion ? dateLabel(sensor.ultima_comunicacion) : 'Aún no se recibieron eventos' }}</small></td>
              <td><div class="row-actions"><Link :href="`/iot/sensores/${sensor.id}`">Ver<span class="sr-only"> sensor {{ sensor.identificador }}</span></Link></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="sensor"/>
        <h3>No hay sensores con estos criterios</h3>
        <p>Registre un dispositivo o ajuste los filtros.</p>
      </div>
      <Pagination :data="sensores"/>
    </section>
  </AppLayout>
</template>
