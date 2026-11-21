<script setup>
import { Link } from '@inertiajs/vue3';
import SpaceState from './SpaceState.vue';

// Listado de espacios tal como llega del servidor. Sin imágenes: la interfaz no ilustra un ambiente que no conoce.
defineProps({ spaces: { type: Array, default: () => [] } });
</script>

<template>
  <div class="table-scroll" role="region" tabindex="0" aria-label="Listado de espacios desplazable">
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
        <tr v-for="space in spaces" :key="space.recurso_id">
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
</template>
