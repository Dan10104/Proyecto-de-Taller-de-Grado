<script setup>
import { stateLabels } from '../spaces';
defineProps({ value: String });
</script>
<template>
  <span class="badge" :class="value === 'DISPONIBLE' ? 'badge-green' : value === 'BLOQUEADO' ? 'badge-red' : 'badge-amber'">
    <span class="status-dot"></span>
    {{ stateLabels[value] }}
  </span>
</template>
