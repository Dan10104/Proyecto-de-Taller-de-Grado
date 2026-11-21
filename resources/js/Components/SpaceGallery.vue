<script setup>
import { Link } from '@inertiajs/vue3';
import SpaceState from './SpaceState.vue';
defineProps({ spaces: { type: Array, default: () => [] } });
</script>
<template>
  <ul class="space-gallery">
    <li v-for="space in spaces" :key="space.recurso_id" class="space-gallery-item">
      <div class="space-gallery-name">
        <Link :href="`/espacios/${space.recurso_id}`" class="text-link">{{ space.recurso.nombre }}</Link>
        <small>{{ space.recurso.codigo }} · {{ space.tipo.nombre }}</small>
      </div>
      <span class="space-gallery-facts">{{ space.piso.nombre }} · {{ space.capacidad }} personas</span>
      <SpaceState :value="space.estado"/>
    </li>
  </ul>
</template>
