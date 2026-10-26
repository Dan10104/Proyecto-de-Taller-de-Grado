<script setup>
import { ref, onMounted } from 'vue';
const props = defineProps({ report: String, field: String, label: String, modelValue: [String, Number] });
const emit = defineEmits(['update:modelValue']);
const search = ref(''), options = ref([]), pending = ref(false), error = ref('');
let sequence = 0;
async function load(selected = false) { const ticket = ++sequence; pending.value = true; error.value = ''; try { const query = new URLSearchParams({ reporte: props.report, campo: props.field, ...(selected && props.modelValue ? { seleccionado: props.modelValue } : { buscar: search.value }) }); const response = await fetch(`/reportes/opciones?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' }); if (!response.ok) throw new Error(); const data = await response.json(); if (ticket === sequence) { options.value = data.opciones; if (!selected) emit('update:modelValue', ''); } } catch { if (ticket === sequence) error.value = 'No se pudieron cargar las opciones.'; } finally { if (ticket === sequence) pending.value = false; } }
onMounted(() => load(Boolean(props.modelValue)));
</script>
<template><div class="report-reference"><label class="report-field" :for="`filter-${field}`"><span>{{ label }}</span><select :id="`filter-${field}`" :value="modelValue || ''" @change="emit('update:modelValue', $event.target.value)"><option value="">Todos</option><option v-for="o in options" :key="o.id" :value="o.id">{{ o.nombre }}</option></select></label><div class="report-reference-search"><input :aria-label="`Buscar ${label.toLowerCase()}`" v-model="search" maxlength="80" placeholder="Buscar opciones" @keydown.enter.prevent="load()"><button type="button" class="btn btn-secondary" :disabled="pending" @click="load()">{{ pending ? '…' : 'Buscar' }}</button></div><small v-if="error" role="alert">{{ error }}</small><small v-else>Hasta 30 coincidencias.</small></div></template>
