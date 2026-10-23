<script setup>
import { reservationStates } from '../reservations';
defineProps({ value: String });
</script>
<template>
  <span class="badge" :class="value === 'APROBADA' ? 'badge-green' : value === 'PENDIENTE' ? 'badge-amber' : value === 'RECHAZADA' ? 'badge-red' : 'badge-gray'">
    <span class="status-dot"></span>{{ reservationStates[value] }}
  </span>
</template>
