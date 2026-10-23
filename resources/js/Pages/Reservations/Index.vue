<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import ReservationState from '../../Components/ReservationState.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';
import { reservationStates, reservationDate } from '../../reservations';
import { focusError } from '../../spaces';
const props = defineProps({ reservas: Object, alcance: String, filtros: Object, opciones: Object });
const { can } = useAccess();
const filters = useForm({ fecha: props.filtros.fecha || '', estado: props.filtros.estado || '', espacio_id: props.filtros.espacio_id || '', ...(props.alcance === 'administrativas' ? { solicitante_id: props.filtros.solicitante_id || '' } : {}) });
function search() {
  filters.transform(data => Object.fromEntries(Object.entries({ ...data, alcance: props.alcance }).filter(([, value]) => value !== '')))
    .get('/reservas', { preserveState: true, preserveScroll: true, onError: focusError });
}
function clear() { router.get('/reservas', { alcance: props.alcance }); }
</script>
<template>
  <AppLayout title="Reservas" section="Espacios académicos">
    <div class="page-heading">
      <div><span class="eyebrow">SOLICITUDES Y SEGUIMIENTO</span><h1>Reservas de espacios</h1><p>{{ alcance === 'propias' ? 'Consulte sus solicitudes y el estado de cada reserva.' : 'Revise las solicitudes y gestione las reservas autorizadas.' }}</p></div>
      <Link v-if="can('reservas.solicitar') && can('espacios.ver')" href="/disponibilidad" class="btn btn-primary">Solicitar reserva</Link>
    </div>
    <nav class="catalog-tabs" aria-label="Alcance de reservas">
      <Link v-if="can('reservas.ver_propias')" href="/reservas?alcance=propias" :class="{ active: alcance === 'propias' }" :aria-current="alcance === 'propias' ? 'page' : undefined">Mis reservas</Link>
      <Link v-if="can('reservas.ver_administrativas')" href="/reservas?alcance=administrativas" :class="{ active: alcance === 'administrativas' }" :aria-current="alcance === 'administrativas' ? 'page' : undefined">Reservas administrativas</Link>
      <Link v-if="can('reservas.ver_administrativas') && can('reservas.resolver')" href="/reservas?alcance=administrativas&estado=PENDIENTE">Pendientes de revisión</Link>
    </nav>
    <form class="panel space-filters" @submit.prevent="search">
      <FormErrors :errors="filters.errors"/>
      <div class="reservation-filter-grid">
        <InputField id="filtro-reserva-fecha" label="Fecha" type="date" v-model="filters.fecha" min="1000-01-01" max="9999-12-31" :error="filters.errors.fecha"/>
        <InputField id="filtro-reserva-estado" label="Estado" v-model="filters.estado" placeholder="Todos los estados" :options="opciones.estados.map(value => ({ value, label: reservationStates[value] }))" :error="filters.errors.estado"/>
        <InputField id="filtro-reserva-espacio" label="Espacio" v-model="filters.espacio_id" placeholder="Todos los espacios" :options="opciones.espacios.map(s => ({ value: s.id, label: `${s.codigo} · ${s.nombre}` }))" :error="filters.errors.espacio_id"/>
        <InputField v-if="alcance === 'administrativas'" id="filtro-reserva-solicitante" label="Solicitante" v-model="filters.solicitante_id" placeholder="Todos los solicitantes" :options="opciones.solicitantes.map(u => ({ value: u.id, label: `${u.nombres} ${u.apellidos}` }))" :error="filters.errors.solicitante_id"/>
      </div>
      <div class="filter-actions"><p class="hint">La fecha incluye las reservas que abarcan parte de ese día. Hora de Bolivia.</p><div class="button-group"><button type="button" class="btn btn-secondary" @click="clear">Limpiar</button><button class="btn btn-primary" :disabled="filters.processing">Aplicar filtros</button></div></div>
    </form>
    <section class="panel">
      <div class="panel-heading"><h2>{{ reservas.total }} {{ reservas.total === 1 ? 'reserva' : 'reservas' }}</h2><p>El estado refleja la última decisión registrada.</p></div>
      <div v-if="reservas.data.length" class="table-scroll" role="region" tabindex="0" aria-label="Listado de reservas desplazable">
        <table class="reservation-table">
          <caption class="sr-only">Reservas {{ alcance === 'propias' ? 'propias' : 'administrativas' }}</caption>
          <thead><tr><th>Código</th><th>Espacio / piso</th><th v-if="alcance === 'administrativas'">Solicitante</th><th>Inicio / fin</th><th>Propósito</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody><tr v-for="reservation in reservas.data" :key="reservation.id">
            <td><Link :href="`/reservas/${reservation.id}`" class="text-link reservation-code">{{ reservation.codigo }}</Link></td>
            <td><strong>{{ reservation.espacio.nombre }}</strong><small class="cell-subline">{{ reservation.espacio.piso }}</small></td>
            <td v-if="alcance === 'administrativas'">{{ reservation.solicitante.nombres }} {{ reservation.solicitante.apellidos }}</td>
            <td class="reservation-period">{{ reservationDate(reservation.inicio) }}<small class="cell-subline">{{ reservationDate(reservation.fin) }}</small></td>
            <td><span class="reservation-purpose">{{ reservation.proposito }}</span></td>
            <td><ReservationState :value="reservation.estado"/></td>
            <td><Link :href="`/reservas/${reservation.id}`" class="text-link">Ver detalle<span class="sr-only"> de {{ reservation.codigo }}</span></Link></td>
          </tr></tbody>
        </table>
      </div>
      <div v-else class="empty-state"><Icon name="search"/><h3>No hay reservas con estos criterios</h3><p>Revise los filtros o consulte la disponibilidad para preparar una solicitud.</p></div>
    </section>
    <Pagination :data="reservas"/>
  </AppLayout>
</template>
