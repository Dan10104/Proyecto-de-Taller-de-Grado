<script setup>
import { useForm } from '@inertiajs/vue3';
import InputField from './InputField.vue';
import FormErrors from './FormErrors.vue';
import { focusError } from '../spaces';
const props = defineProps({ space: Object, policies: Array });
const form = useForm({ politica_id: props.space.politica_liberacion_id ?? '' });
function save() { form.transform(d => ({ politica_id: d.politica_id === '' ? null : Number(d.politica_id) })).put(`/liberacion/asignaciones/${props.space.recurso_id}`, { preserveScroll: true, onError: focusError }); }
</script>
<template>
  <form class="release-assignment" @submit.prevent="save">
    <div class="release-assignment-space">
      <h3>{{ space.recurso.nombre }}</h3>
      <p><span class="reservation-code">{{ space.recurso.codigo }}</span> · Actual: {{ space.politica ? `${space.politica.nombre} · v${space.politica.version}${space.politica.activa ? '' : ' (inactiva)'}` : 'Sin política' }}</p>
    </div>
    <InputField :id="`politica-${space.recurso_id}`" label="Política asignada" placeholder="Sin política" v-model="form.politica_id" :options="policies.map(p => ({ value: p.id, label: `${p.nombre} · v${p.version}` }))" :error="form.errors.politica_id"/>
    <button class="btn btn-secondary" :disabled="form.processing">Guardar<span class="sr-only"> asignación de {{ space.recurso.nombre }}</span></button>
    <FormErrors :errors="form.errors"/>
  </form>
</template>
