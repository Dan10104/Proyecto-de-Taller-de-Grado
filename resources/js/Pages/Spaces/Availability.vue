<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SpaceFilters from '../../Components/SpaceFilters.vue';
import SpaceState from '../../Components/SpaceState.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';
const { can } = useAccess();
const props = defineProps({ espacios: Object, periodo: Object, filtros: Object, opciones: Object, fechaActual: String });
function requestUrl(space) {
  return '/reservas/crear?' + new URLSearchParams({ espacio_id: space.recurso_id, inicio: `${props.periodo.fecha}T${props.periodo.inicio}`, fin: `${props.periodo.fecha}T${props.periodo.fin}` });
}
</script>
<template>
  <AppLayout title="Disponibilidad de espacios">
    <section class="availability-heading">
      <div>
        <span class="eyebrow">ENCUENTRE EL ESPACIO ADECUADO</span>
        <h1>Disponibilidad de espacios</h1>
        <p>Consulte ambientes por horario, ubicación y características.</p>
      </div>
      <Icon name="building"/>
    </section>
    <div class="availability-notice">
      <strong>Disponibilidad para el periodo seleccionado</strong>
      <p>Los resultados consideran el estado del espacio, los bloqueos, las reservas aprobadas y las incidencias abiertas. La disponibilidad se comprueba nuevamente al confirmar una solicitud.</p>
    </div>
    <SpaceFilters :filtros="filtros" :opciones="opciones" availability :fecha-actual="fechaActual"/>
    <section v-if="periodo" aria-labelledby="resultados-heading">
      <div class="section-heading results-heading">
        <div>
          <h2 id="resultados-heading">{{ espacios.total }} {{ espacios.total === 1 ? 'espacio disponible' : 'espacios disponibles' }}</h2>
          <p>{{ periodo.fecha.split('-').reverse().join('/') }} · {{ periodo.inicio }}–{{ periodo.fin }} · Hora de Bolivia</p>
        </div>
        <span class="muted">Con todas las características solicitadas</span>
      </div>
      <div v-if="espacios.data.length" class="space-card-grid">
        <article v-for="space in espacios.data" :key="space.recurso_id" class="panel space-card">
          <div class="space-card-top">
            <span class="tile-icon">
              <Icon name="building"/>
            </span>
            <SpaceState :value="space.estado"/>
          </div>
          <span class="space-code">{{ space.recurso.codigo }}</span>
          <h3>{{ space.recurso.nombre }}</h3>
          <p class="muted">{{ space.tipo.nombre }} · {{ space.piso.nombre }}</p>
          <p class="space-capacity">
            <strong>{{ space.capacidad }}</strong>
            personas
          </p>
          <div class="feature-chips">
            <span v-for="feature in space.caracteristicas" :key="feature.id">{{ feature.nombre }}</span>
            <span v-if="!space.caracteristicas.length">Sin características registradas</span>
          </div>
          <div class="space-card-bottom">
            <span>Disponible en este horario</span>
            <Link :href="`/espacios/${space.recurso_id}`" class="text-link">
              Ver detalle
              <Icon name="arrow"/>
            </Link>
          </div>
          <Link v-if="can('reservas.solicitar')" :href="requestUrl(space)" class="btn btn-primary reservation-request-link">Solicitar reserva<span class="sr-only"> de {{ space.recurso.nombre }}</span></Link>
        </article>
      </div>
      <div v-else class="panel empty-state">
        <Icon name="search"/>
        <h3>No hay espacios disponibles con estos criterios</h3>
        <p>Pruebe otro horario o ajuste los filtros. Los espacios bloqueados o en mantenimiento no se incluyen.</p>
      </div>
      <Pagination :data="espacios"/>
    </section>
    <section v-else class="panel empty-state">
      <Icon name="search"/>
      <h2>Elija cuándo y qué necesita</h2>
      <p>Indique una fecha y un horario para consultar los espacios que cumplen sus condiciones.</p>
    </section>
  </AppLayout>
</template>
