<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TraceabilityChain from '../../Components/TraceabilityChain.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { operations, verificationResults } from '../../traceability';
import { traceabilityDate } from '../../traceability';
const props = defineProps({ evidencias: Object, filtros: Object });
const form = useForm({ estado: props.filtros.estado || '', operacion: props.filtros.operacion || '', resultado: props.filtros.resultado || '', desde: props.filtros.desde || '', hasta: props.filtros.hasta || '' });
const options = object => Object.entries(object).map(([value, label]) => ({ value, label }));
// Solo presentación: distingue «sin registros» de «sin resultados para los filtros».
const hasFilters = computed(() => Object.values(props.filtros || {}).some(Boolean));
</script>
<template>
  <AppLayout title="Evidencias de trazabilidad">
    <div class="page-heading">
      <div>
        <h1>Evidencias de trazabilidad</h1>
        <p>Registro verificable de aprobaciones, cancelaciones, entregas y devoluciones.</p>
      </div>
    </div>

    <TraceabilityChain :entries="evidencias.data"/>

    <form class="panel space-filters" @submit.prevent="form.get('/trazabilidad')">
      <InputField id="estado" label="Registro" v-model="form.estado" placeholder="Todos" :options="options({ PENDIENTE: 'Pendiente', REGISTRADA: 'Registrada' })" :error="form.errors.estado"/>
      <InputField id="operacion" label="Operación" v-model="form.operacion" placeholder="Todas" :options="options(operations)" :error="form.errors.operacion"/>
      <InputField id="resultado" label="Verificación" v-model="form.resultado" placeholder="Todos" :options="options(verificationResults)" :error="form.errors.resultado"/>
      <InputField id="desde" type="date" label="Desde" v-model="form.desde" :error="form.errors.desde"/>
      <InputField id="hasta" type="date" label="Hasta" v-model="form.hasta" :error="form.errors.hasta"/>
      <div class="filter-actions">
        <div class="button-group">
          <Link class="text-link" href="/trazabilidad">Limpiar filtros</Link>
          <button class="btn btn-secondary" :disabled="form.processing">Aplicar filtros</button>
        </div>
      </div>
    </form>

    <section class="panel">
      <div v-if="evidencias.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Evidencias, tabla desplazable">
        <table>
          <caption class="sr-only">Evidencias de trazabilidad</caption>
          <thead>
            <tr>
              <th scope="col">Evidencia</th>
              <th scope="col">Operación</th>
              <th scope="col">Fecha de operación</th>
              <th scope="col">Registro</th>
              <th scope="col">Última verificación</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in evidencias.data" :key="e.id">
              <td>
                <Link :href="`/trazabilidad/${e.id}`" class="text-link reservation-code">Evidencia {{ e.id }}</Link>
                <small v-if="e.actividad.reserva_id || e.actividad.prestamo_id" class="cell-subline">{{ e.actividad.reserva_id ? `Reserva ${e.actividad.reserva_id}` : `Préstamo ${e.actividad.prestamo_id}` }}</small>
              </td>
              <td>{{ operations[e.actividad.accion] }}</td>
              <td class="date-cell">{{ traceabilityDate(e.actividad.fecha_hora) }}</td>
              <td><StatusBadge :value="e.estado" domain="evidencia"/></td>
              <td>
                <StatusBadge v-if="e.ultima_verificacion" :value="e.resultado_verificacion" domain="verificacion"/>
                <StatusBadge v-else value="" label="Sin verificar"/>
                <small v-if="e.ultima_verificacion" class="cell-subline">{{ traceabilityDate(e.ultima_verificacion) }}</small>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="cube"/>
        <h2>{{ hasFilters ? 'No hay evidencias para estos filtros.' : 'Aún no hay evidencias de trazabilidad.' }}</h2>
        <p>{{ hasFilters ? 'Ajuste o limpie los filtros para consultar otros registros.' : 'Se generan al aprobar o cancelar una reserva y al entregar o devolver equipamiento.' }}</p>
        <Link v-if="hasFilters" class="text-link" href="/trazabilidad">Limpiar filtros</Link>
      </div>
      <Pagination v-if="evidencias.data.length || evidencias.prev_page_url" :data="evidencias"/>
    </section>
  </AppLayout>
</template>
