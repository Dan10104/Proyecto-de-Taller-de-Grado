<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SpaceFilters from '../../Components/SpaceFilters.vue';
import SpaceState from '../../Components/SpaceState.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import IotEvidence from '../../Components/IotEvidence.vue';
import SpaceIllustration from '../../Components/SpaceIllustration.vue';
import { useAccess } from '../../security';
const { can } = useAccess();
const props = defineProps({ espacios: Object, periodo: Object, filtros: Object, opciones: Object, fechaActual: String, iot: Object });
function requestUrl(space) {
  return '/reservas/crear?' + new URLSearchParams({ espacio_id: space.recurso_id, inicio: `${props.periodo.fecha}T${props.periodo.inicio}`, fin: `${props.periodo.fecha}T${props.periodo.fin}` });
}
</script>
<template>
  <AppLayout title="Disponibilidad de espacios">
    <div class="page-heading">
      <div>
        <h1>Disponibilidad de espacios</h1>
        <p>Elija un horario y vea qué espacios puede reservar.</p>
      </div>
    </div>
    <SpaceFilters :filtros="filtros" :opciones="opciones" availability :fecha-actual="fechaActual"/>
    <section v-if="periodo" class="panel availability-results" aria-labelledby="resultados-heading">
      <div class="panel-heading">
        <h2 id="resultados-heading">{{ espacios.total }} {{ espacios.total === 1 ? 'espacio disponible' : 'espacios disponibles' }}</h2>
        <p><Icon name="calendar"/>{{ periodo.fecha.split('-').reverse().join('/') }} · {{ periodo.inicio }}–{{ periodo.fin }} · Hora de Bolivia</p>
      </div>
      <p v-if="filtros?.caracteristicas?.length" class="hint">Con todas las características solicitadas.</p>
      <ul v-if="espacios.data.length" class="availability-list">
        <li v-for="space in espacios.data" :key="space.recurso_id" class="availability-item">
          <SpaceIllustration class="availability-thumb" :kind="space.tipo.nombre + ' ' + space.recurso.nombre"/>
          <div class="availability-main">
            <h3><Link :href="`/espacios/${space.recurso_id}`">{{ space.recurso.nombre }}</Link></h3>
            <p class="availability-facts">
              <span class="reservation-code">{{ space.recurso.codigo }}</span>
              <span><Icon name="building"/>{{ space.piso.nombre }}</span>
              <span><Icon name="layers"/>{{ space.tipo.nombre }}</span>
              <span><Icon name="users"/>{{ space.capacidad }} personas</span>
            </p>
            <div class="feature-chips">
              <span v-for="feature in space.caracteristicas" :key="feature.id">{{ feature.nombre }}</span>
              <span v-if="!space.caracteristicas.length">Sin características registradas</span>
            </div>
          </div>
          <div class="availability-side">
            <SpaceState :value="space.estado"/>
            <span class="availability-fit"><Icon name="checkCircle"/>Disponible en este horario</span>
            <div class="button-group">
              <Link :href="`/espacios/${space.recurso_id}`" class="text-link">Ver detalle<span class="sr-only"> de {{ space.recurso.nombre }}</span></Link>
              <Link v-if="can('reservas.solicitar')" :href="requestUrl(space)" class="btn btn-primary reservation-request-link">Solicitar reserva<span class="sr-only"> de {{ space.recurso.nombre }}</span></Link>
            </div>
          </div>
          <details class="availability-iot">
            <summary>Ver lecturas del ambiente</summary>
            <IotEvidence :readings="iot?.[space.recurso_id]?.lecturas || []" compact/>
          </details>
        </li>
      </ul>
      <div v-else class="empty-state">
        <Icon name="search"/>
        <h3>No hay espacios disponibles con estos criterios</h3>
        <p>Pruebe otro horario o ajuste los filtros. Los espacios bloqueados o en mantenimiento no se incluyen.</p>
      </div>
      <Pagination :data="espacios"/>
    </section>
    <section v-else class="panel empty-state">
      <Icon name="calendar"/>
      <h2>Elija cuándo y qué necesita</h2>
      <p>Indique una fecha y un horario para consultar los espacios disponibles.</p>
    </section>
    <details class="disclosure">
      <summary>Cómo se calcula la disponibilidad</summary>
      <p>Los resultados consideran el estado del espacio, los bloqueos, las reservas aprobadas y las incidencias abiertas. La disponibilidad se comprueba nuevamente al confirmar una solicitud.</p>
    </details>
  </AppLayout>
</template>
