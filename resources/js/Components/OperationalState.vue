<script setup>
import StatusBadge from './StatusBadge.vue';
defineProps({ value: String });
</script>
<template><StatusBadge :value="value"/></template>
