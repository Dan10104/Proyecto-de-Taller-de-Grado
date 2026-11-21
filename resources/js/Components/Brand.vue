<script setup>
defineProps({ light: Boolean });
</script>

<template>
  <span class="brand" :class="{'brand-light':light}">
    <span class="brand-mark" aria-hidden="true">U</span>
    <span class="brand-text">
      <strong>ESPACIO-U</strong>
      <small>FICCT</small>
    </span>
  </span>
</template>
