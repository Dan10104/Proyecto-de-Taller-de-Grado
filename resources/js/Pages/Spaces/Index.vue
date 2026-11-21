<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SpaceFilters from '../../Components/SpaceFilters.vue';
import SpaceState from '../../Components/SpaceState.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';
defineProps({ espacios: Object, filtros: Object, opciones: Object });
const { can } = useAccess();
</script>
<template>
  <AppLayout title="Espacios académicos">
    <div class="page-heading">
      <div>
        <h1>Espacios académicos</h1>
        <p>Ubicación, capacidad y estado operativo de cada ambiente.</p>
      </div>
      <div v-if="can('espacios.gestionar')" class="button-group">
        <Link href="/espacios/catalogos/pisos" class="btn btn-secondary">Catálogos auxiliares</Link>
        <Link href="/espacios/crear" class="btn btn-primary">
          <Icon name="plus"/>
          Registrar espacio
        </Link>
      </div>
    </div>
    <SpaceFilters :filtros="filtros" :opciones="opciones"/>
    <section class="panel space-list-panel">
      <div class="panel-heading">
        <h2>{{ espacios.total }} {{ espacios.total === 1 ? 'espacio' : 'espacios' }}</h2>
        <p>Los bloqueos temporales se reflejan al consultar la disponibilidad de un periodo.</p>
      </div>
      <div v-if="espacios.data.length" class="table-scroll" role="region" tabindex="0" aria-label="Listado de espacios desplazable">
        <table class="space-table">
          <caption class="sr-only">Espacios académicos registrados</caption>
          <thead>
            <tr>
              <th>Espacio</th>
              <th>Piso</th>
              <th>Tipo</th>
              <th class="align-right">Capacidad</th>
              <th>Estado</th>
              <th class="align-right">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="space in espacios.data" :key="space.recurso_id">
              <td class="space-name-cell">
                <Link :href="`/espacios/${space.recurso_id}`" class="space-name-link">{{ space.recurso.nombre }}</Link>
                <small class="cell-subline">{{ space.recurso.codigo }}</small>
                <small class="cell-subline space-features" :title="space.caracteristicas.map(feature => feature.nombre).join(', ') || undefined">{{ space.caracteristicas.length ? space.caracteristicas.map(feature => feature.nombre).join(' · ') : 'Sin características' }}</small>
              </td>
              <td>{{ space.piso.nombre }}</td>
              <td>{{ space.tipo.nombre }}</td>
              <td class="align-right nowrap">{{ space.capacidad }} personas</td>
              <td>
                <SpaceState :value="space.estado"/>
              </td>
              <td class="row-actions">
                <Link :href="`/espacios/${space.recurso_id}`">Ver<span class="sr-only"> {{ space.recurso.nombre }}</span></Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="building"/>
        <h3>No hay espacios que coincidan</h3>
        <p>Ajuste los filtros{{ can('espacios.gestionar') ? ' o registre un espacio.' : '.' }}</p>
        <Link v-if="can('espacios.gestionar')" href="/espacios/crear" class="btn btn-secondary">Registrar espacio</Link>
      </div>
      <Pagination :data="espacios"/>
    </section>
  </AppLayout>
</template>
