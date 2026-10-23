<script setup>
import { labels } from '../equipment';
defineProps({ value:String });
</script>
<template><span class="status-badge operational-state" :class="'operational-'+value?.toLowerCase()">{{ labels[value] || value }}</span></template>
