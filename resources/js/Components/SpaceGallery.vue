<script setup>
import { Link } from '@inertiajs/vue3';
import SpaceMedia from './SpaceMedia.vue';
import SpaceState from './SpaceState.vue';
import Icon from './Icon.vue';
defineProps({ spaces: { type: Array, default: () => [] } });
</script>
<template>
  <div class="space-gallery">
    <article v-for="space in spaces" :key="space.recurso_id" class="gallery-card">
      <SpaceMedia :name="space.recurso.nombre"/>
      <div class="gallery-card-body">
        <div class="gallery-state"><span>{{ space.tipo.nombre }}</span><SpaceState :value="space.estado"/></div>
        <h3><Link :href="`/espacios/${space.recurso_id}`">{{ space.recurso.nombre }}</Link></h3>
        <div class="gallery-facts"><span><Icon name="building"/>{{ space.piso.nombre }}</span><span><Icon name="users"/>{{ space.capacidad }} personas</span></div>
        <div class="feature-chips"><span v-for="feature in space.caracteristicas.slice(0, 4)" :key="feature.id">{{ feature.nombre }}</span></div>
        <div class="gallery-card-foot"><span>{{ space.recurso.codigo }}</span><Link :href="`/espacios/${space.recurso_id}`" class="text-link">Ver detalle<Icon name="arrow"/><span class="sr-only"> de {{ space.recurso.nombre }}</span></Link></div>
      </div>
    </article>
  </div>
</template>
