<script setup>
import { useForm } from '@inertiajs/vue3';
import InputField from './InputField.vue';
import FormErrors from './FormErrors.vue';
import { focusError } from '../spaces';
const props = defineProps({ space: Object, policies: Array });
const form = useForm({ politica_id: props.space.politica_liberacion_id ?? '' });
function save() { form.transform(d => ({ politica_id: d.politica_id === '' ? null : Number(d.politica_id) })).put(`/liberacion/asignaciones/${props.space.recurso_id}`, { preserveScroll: true, onError: focusError }); }
</script>
<template><form class="panel form-panel" @submit.prevent="save"><h2>{{ space.recurso.nombre }}</h2><p>{{ space.recurso.codigo }} · Actual: {{ space.politica ? `${space.politica.nombre} · v${space.politica.version}${space.politica.activa ? '' : ' (inactiva)'}` : 'Sin política' }}</p><FormErrors :errors="form.errors"/><InputField :id="`politica-${space.recurso_id}`" label="Política asignada" placeholder="Sin política" v-model="form.politica_id" :options="policies.map(p => ({ value: p.id, label: `${p.nombre} · v${p.version}` }))" :error="form.errors.politica_id"/><div class="form-actions"><button class="btn btn-secondary" :disabled="form.processing">Guardar asignación</button></div></form></template>
