<script setup>
import { releaseResults } from '../release';
defineProps({ value: String });
</script>
<template><span class="badge" :class="value === 'MANTENER_POR_USO' ? 'badge-green' : value === 'ALERTAR' ? 'badge-amber' : 'badge-gray'">{{ releaseResults[value] || value }}</span></template>
