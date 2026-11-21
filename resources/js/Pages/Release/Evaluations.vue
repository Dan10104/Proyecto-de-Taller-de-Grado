<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import ReleaseResult from '../../Components/ReleaseResult.vue';
import Icon from '../../Components/Icon.vue';
import { releaseResults } from '../../release';
import { reservationDate } from '../../reservations';
import { useAccess } from '../../security';
const props = defineProps({ evaluaciones: Object, alcance: String, filtros: Object });
const { can } = useAccess();
const form = useForm({ alcance: props.alcance, resultado: props.filtros.resultado || '', politica_id: props.filtros.politica_id || '', reserva_id: props.filtros.reserva_id || '' });
</script>
<template>
  <AppLayout title="Evaluaciones de reservas">
    <div class="page-heading"><div><h1>Evaluaciones de reservas</h1><p>{{ alcance === 'propias' ? 'Resultado de las políticas aplicadas a sus reservas.' : 'Decisiones tomadas y evidencia utilizada.' }}</p></div></div>
    <nav class="catalog-tabs" aria-label="Alcance de evaluaciones"><Link v-if="can('liberacion.evaluaciones.ver_propias')" href="/liberacion/evaluaciones?alcance=propias" :class="{ active: alcance === 'propias' }">Mis evaluaciones</Link><Link v-if="can('liberacion.evaluaciones.ver_administrativas')" href="/liberacion/evaluaciones?alcance=administrativas" :class="{ active: alcance === 'administrativas' }">Evaluaciones administrativas</Link></nav>
    <form class="panel space-filters" @submit.prevent="form.get('/liberacion/evaluaciones')">
      <InputField id="resultado" label="Resultado" placeholder="Todos los resultados" v-model="form.resultado" :options="Object.entries(releaseResults).map(([value, label]) => ({ value, label }))" :error="form.errors.resultado"/>
      <div class="filter-actions"><div class="button-group"><button class="btn btn-secondary">Filtrar</button><Link href="/liberacion/evaluaciones" class="text-link">Limpiar filtros</Link></div></div>
    </form>
    <section class="panel">
      <div v-if="evaluaciones.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Evaluaciones de reservas, tabla desplazable">
        <table><caption class="sr-only">Evaluaciones de reservas</caption>
          <thead><tr><th>Reserva</th><th>Espacio</th><th>Fecha de evaluación</th><th>Resultado</th><th>Política</th><th class="align-right">Acción</th></tr></thead>
          <tbody><tr v-for="e in evaluaciones.data" :key="e.id">
            <td class="reservation-code">{{ e.reserva.codigo }}</td>
            <td>{{ e.espacio.nombre }}</td>
            <td class="date-cell">{{ reservationDate(e.fecha_hora) }}</td>
            <td><ReleaseResult :value="e.resultado"/></td>
            <td>{{ e.politica.nombre }}<small class="cell-subline">Versión {{ e.politica.version }}</small></td>
            <td><div class="row-actions"><Link :href="`/liberacion/evaluaciones/${e.id}?alcance=${alcance}`">Ver<span class="sr-only"> evaluación de {{ e.reserva.codigo }}</span></Link></div></td>
          </tr></tbody>
        </table>
      </div>
      <div v-else class="empty-state"><Icon name="checklist"/><h3>No hay evaluaciones para estos filtros</h3><p>Las evaluaciones aparecen cuando una política revisa una reserva aprobada.</p></div>
      <Pagination :data="evaluaciones"/>
    </section>
  </AppLayout>
</template>
