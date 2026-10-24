<script setup>
import { watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import { useAccess } from '../../security';
import { focusError } from '../../spaces';
const props = defineProps({ origen: Object });
const { can } = useAccess();
const form = useForm({ nombre: props.origen?.nombre || '', version: props.origen ? Math.min(props.origen.version + 1, 2147483647) : 1, tolerancia_min: props.origen?.tolerancia_min ?? 5, requiere_qr: props.origen?.requiere_qr ?? true, usa_iot: props.origen?.usa_iot ?? false, max_antiguedad_lectura_min: props.origen?.max_antiguedad_lectura_min ?? null, modo: props.origen?.modo || 'ALERTA', activa: true });
watch(() => form.usa_iot, value => { form.max_antiguedad_lectura_min = value ? 5 : null; });
</script>
<template>
  <AppLayout :title="origen ? 'Crear nueva versión' : 'Crear política'" section="Liberación">
    <div class="page-heading"><div><h1>{{ origen ? 'Crear nueva versión' : 'Crear política' }}</h1><p>Los campos con * son obligatorios. Las reglas de esta versión se conservarán desde su creación.</p></div><Link v-if="can('liberacion.politicas.ver')" href="/liberacion/politicas" class="btn btn-secondary">Volver a políticas</Link></div>
    <form class="panel form-panel" @submit.prevent="form.post('/liberacion/politicas', { onError: focusError })"><FormErrors :errors="form.errors"/>
      <p v-if="origen" class="alert">Basada en {{ origen.nombre }} · v{{ origen.version }}. Se creará una versión independiente; revise su número antes de guardar.</p>
      <div class="form-grid"><InputField id="nombre" label="Nombre" v-model="form.nombre" required maxlength="120" :error="form.errors.nombre"/><InputField id="version" label="Versión" v-model="form.version" type="number" min="1" max="2147483647" required :error="form.errors.version"/><InputField id="tolerancia_min" label="Tolerancia desde el inicio (minutos)" v-model="form.tolerancia_min" type="number" min="0" max="2147483647" required :error="form.errors.tolerancia_min"/><InputField id="modo" label="Modo" v-model="form.modo" required :options="[{ value: 'ALERTA', label: 'Alerta: conserva la reserva' }, { value: 'AUTOMATICA', label: 'Automática: permite liberación condicionada' }]" :error="form.errors.modo"/></div>
      <fieldset class="role-selection"><legend>Fuentes obligatorias: seleccione al menos una</legend><label class="check-option"><input type="checkbox" v-model="form.requiere_qr"> Requiere validación QR</label><label class="check-option"><input type="checkbox" v-model="form.usa_iot"> Utiliza evidencia IoT</label></fieldset>
      <InputField v-if="form.usa_iot" id="max_antiguedad_lectura_min" label="Antigüedad máxima de lectura IoT (minutos)" v-model="form.max_antiguedad_lectura_min" type="number" min="0" max="2147483647" required :error="form.errors.max_antiguedad_lectura_min"/>
      <p class="hint">Una evidencia fiable de uso impide liberar. La evidencia contradictoria o insuficiente para una fuente obligatoria conserva la reserva.</p><label class="check-option"><input type="checkbox" v-model="form.activa"> Crear versión activa</label>
      <div class="form-actions"><button class="btn btn-primary" :disabled="form.processing">{{ form.processing ? 'Guardando…' : 'Crear versión' }}</button></div>
    </form>
  </AppLayout>
</template>
