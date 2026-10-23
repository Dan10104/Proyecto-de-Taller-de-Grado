<script setup>
import Icon from './Icon.vue';

defineProps({ light: Boolean });
</script>

<template>
  <div class="brand" :class="{'brand-light':light}">
    <span class="brand-mark">
      <Icon name="building" />
    </span>
    <span>
      <strong>ESPACIO-U</strong>
      <small>FICCT · Gestión académica</small>
    </span>
  </div>
</template>
