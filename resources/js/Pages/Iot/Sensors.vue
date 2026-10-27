<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import { useAccess, dateLabel } from '../../security';
const props = defineProps({ sensores: Object, filtros: Object, espacios: Array });
const { can } = useAccess();
const filters = useForm({ q: props.filtros.q || '', estado: props.filtros.estado || '', espacio_id: props.filtros.espacio_id || '' });
function search() { filters.get('/iot/sensores', { preserveState: true }); }
</script>
<template>
  <AppLayout title="Sensores IoT" section="IoT">
    <div class="page-heading"><div><h1>Sensores IoT</h1><p>Administre la asociación de cada dispositivo y consulte su última comunicación.</p></div><Link v-if="can('iot.sensores.gestionar')" href="/iot/sensores/crear" class="btn btn-primary">Registrar sensor</Link></div>
    <form class="panel form-panel" @submit.prevent="search"><div class="form-grid">
      <InputField id="iot-search" label="Identificador o tipo" v-model="filters.q" maxlength="120"/>
      <InputField id="iot-state" label="Estado" v-model="filters.estado" :options="[{value:'HABILITADO',label:'Habilitado'},{value:'DESHABILITADO',label:'Deshabilitado'}]" placeholder="Todos"/>
      <InputField id="iot-space" label="Espacio actual" v-model="filters.espacio_id" :options="espacios.map(s=>({value:s.recurso_id,label:`${s.recurso.codigo} · ${s.recurso.nombre}`}))" placeholder="Todos"/>
    </div><div class="form-actions"><Link href="/iot/sensores" class="btn btn-secondary">Limpiar filtros</Link><button class="btn btn-primary" :disabled="filters.processing">Buscar</button></div></form>
    <section class="panel"><div class="panel-heading"><h2>{{ sensores.total }} sensores registrados</h2></div>
      <div v-if="sensores.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Sensores, tabla desplazable"><table><caption class="sr-only">Catálogo de sensores IoT</caption><thead><tr><th>Sensor</th><th>Espacio actual</th><th>Estado</th><th>Comunicación</th><th>Acción</th></tr></thead><tbody>
        <tr v-for="sensor in sensores.data" :key="sensor.id"><td><strong>{{ sensor.identificador }}</strong><p>{{ sensor.tipo }}</p></td><td>{{ sensor.espacio_actual.recurso.codigo }}<p>{{ sensor.espacio_actual.recurso.nombre }}</p></td><td>{{ sensor.estado === 'HABILITADO' ? 'Habilitado' : 'Deshabilitado' }}</td><td>{{ sensor.comunicacion === 'RECIENTE' ? 'Reciente' : 'Sin comunicación reciente' }}<p>{{ sensor.ultima_comunicacion ? dateLabel(sensor.ultima_comunicacion) : 'Aún no se recibieron eventos' }}</p></td><td><Link :href="`/iot/sensores/${sensor.id}`" class="text-link">Ver sensor<span class="sr-only"> {{ sensor.identificador }}</span></Link></td></tr>
      </tbody></table></div><div v-else class="empty-state"><h3>No hay sensores con estos criterios</h3><p>Registre un dispositivo o ajuste los filtros.</p></div><Pagination :data="sensores"/>
    </section>
  </AppLayout>
</template>
