<script setup>
defineProps({ modelValue: Object, prefix: { type: String, default: 'periodo' } });
const emit = defineEmits(['update:modelValue']);
function change(key, value, current) { const next = { ...current, [key]: value }; if (key === 'periodo' && value !== 'personalizado') { delete next.fecha_inicio; delete next.fecha_fin; } emit('update:modelValue', next); }
</script>
<template>
  <label class="report-field" :for="prefix"><span>Periodo</span><select :id="prefix" :value="modelValue.periodo || 'hoy'" @change="change('periodo', $event.target.value, modelValue)"><option value="hoy">Hoy</option><option value="7_dias">Últimos 7 días</option><option value="30_dias">Últimos 30 días</option><option value="personalizado">Personalizado</option></select></label>
  <template v-if="modelValue.periodo === 'personalizado'"><label class="report-field" :for="`${prefix}-inicio`"><span>Fecha inicial</span><input :id="`${prefix}-inicio`" type="date" required :value="modelValue.fecha_inicio" @input="change('fecha_inicio', $event.target.value, modelValue)"></label><label class="report-field" :for="`${prefix}-fin`"><span>Fecha final (incluida)</span><input :id="`${prefix}-fin`" type="date" required :min="modelValue.fecha_inicio" :value="modelValue.fecha_fin" @input="change('fecha_fin', $event.target.value, modelValue)"></label></template>
</template>
