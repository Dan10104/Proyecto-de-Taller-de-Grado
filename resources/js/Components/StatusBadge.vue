<script setup>
defineProps({ value: String });
</script>

<template>
  <span class="badge" :class="value==='ACTIVO'?'badge-green':'badge-gray'">
    <span class="status-dot"></span>
    {{ value==='ACTIVO'?'Activo':'Inactivo' }}
  </span>
</template>
