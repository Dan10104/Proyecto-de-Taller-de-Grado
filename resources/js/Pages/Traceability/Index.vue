<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import { operations, verificationResults } from '../../traceability';
import { traceabilityDate } from '../../traceability';
const props = defineProps({ evidencias: Object, filtros: Object });
const form = useForm({ estado: props.filtros.estado || '', operacion: props.filtros.operacion || '', resultado: props.filtros.resultado || '', desde: props.filtros.desde || '', hasta: props.filtros.hasta || '' });
const options = object => Object.entries(object).map(([value, label]) => ({ value, label }));
</script>
<template>
  <AppLayout title="Evidencias de trazabilidad">
    <div class="page-heading"><div><span class="eyebrow">TRAZABILIDAD VERIFICABLE</span><h1>Evidencias de trazabilidad</h1><p>Consulte el registro de aprobaciones, cancelaciones, entregas y devoluciones confirmadas.</p></div></div>
    <form class="panel space-filters" @submit.prevent="form.get('/trazabilidad')">
      <InputField id="estado" label="Registro" v-model="form.estado" placeholder="Todos" :options="options({ PENDIENTE: 'Pendiente', REGISTRADA: 'Registrada' })" :error="form.errors.estado"/>
      <InputField id="operacion" label="Operación" v-model="form.operacion" placeholder="Todas" :options="options(operations)" :error="form.errors.operacion"/>
      <InputField id="resultado" label="Verificación" v-model="form.resultado" placeholder="Todos" :options="options(verificationResults)" :error="form.errors.resultado"/>
      <InputField id="desde" type="date" label="Desde" v-model="form.desde" :error="form.errors.desde"/>
      <InputField id="hasta" type="date" label="Hasta" v-model="form.hasta" :error="form.errors.hasta"/>
      <button class="btn btn-secondary" :disabled="form.processing">Filtrar</button><Link class="btn btn-secondary" href="/trazabilidad">Limpiar filtros</Link>
    </form>
    <div class="panel table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Evidencias, tabla desplazable"><table><caption class="sr-only">Evidencias de trazabilidad</caption><thead><tr><th>Evidencia / operación</th><th>Fecha de operación</th><th>Registro</th><th>Última verificación</th></tr></thead><tbody>
      <tr v-for="e in evidencias.data" :key="e.id"><td><Link :href="`/trazabilidad/${e.id}`">Evidencia {{ e.id }}</Link><br>{{ operations[e.actividad.accion] }}</td><td>{{ traceabilityDate(e.actividad.fecha_hora) }}</td><td>{{ e.estado === 'REGISTRADA' ? 'Registrada' : 'Pendiente' }}</td><td>{{ e.ultima_verificacion ? verificationResults[e.resultado_verificacion] : 'Sin verificar' }}<br>{{ e.ultima_verificacion ? traceabilityDate(e.ultima_verificacion) : '' }}</td></tr>
      <tr v-if="!evidencias.data.length"><td colspan="4" class="empty-cell">No hay evidencias para estos filtros.</td></tr>
    </tbody></table></div><Pagination :data="evidencias"/></div>
  </AppLayout>
</template>
