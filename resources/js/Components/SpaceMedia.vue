<script setup>
import { computed } from 'vue';
const props = defineProps({ name: { type: String, default: '' }, hero: Boolean });
// Illustration selection only; this never classifies or changes the resource.
const laboratory = computed(() => /laboratorio/i.test(props.name));
</script>
<template>
  <figure class="space-media" :class="{ 'space-media-hero': hero }">
    <img :src="laboratory ? '/images/presentation/laboratory-demo.webp' : '/images/presentation/classroom-demo.webp'" :alt="laboratory ? 'Imagen conceptual de un laboratorio universitario, no fotografía del recurso.' : 'Imagen conceptual de un aula universitaria, no fotografía del recurso.'" width="1536" height="1024" loading="lazy" decoding="async">
    <figcaption>Imagen conceptual · Demo</figcaption>
  </figure>
</template>
