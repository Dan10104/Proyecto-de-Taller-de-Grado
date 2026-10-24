<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import { focusError } from '../../spaces';
const props = defineProps({ sensor: Object, espacios: Array });
const form = useForm({ ...(props.sensor ? {} : { identificador: '' }), tipo: props.sensor?.tipo || '', espacio_actual_id: props.sensor?.espacio_actual_id || '' });
function save() { props.sensor ? form.put(`/iot/sensores/${props.sensor.id}`, { onError: focusError }) : form.post('/iot/sensores', { onError: focusError }); }
</script>
<template>
  <AppLayout :title="sensor ? 'Editar sensor' : 'Registrar sensor'" section="IoT">
    <div class="page-heading"><div><h1>{{ sensor ? 'Editar sensor' : 'Registrar sensor' }}</h1><p>Los campos con * son obligatorios.</p></div><Link href="/iot/sensores" class="btn btn-secondary">Volver a sensores</Link></div>
    <form class="panel form-panel" @submit.prevent="save"><FormErrors :errors="form.errors"/>
      <p v-if="sensor" class="alert">Identificador: <strong>{{ sensor.identificador }}</strong>. Se conserva durante toda la vida del dispositivo.</p>
      <div class="form-grid"><InputField v-if="!sensor" id="identificador" label="Identificador técnico" v-model="form.identificador" required maxlength="120" placeholder="SENSOR-LAB-001" :error="form.errors.identificador"/>
        <InputField id="tipo" label="Tipo o descripción del dispositivo" v-model="form.tipo" required maxlength="100" :error="form.errors.tipo"/>
        <InputField id="espacio_actual_id" label="Espacio actual" v-model="form.espacio_actual_id" required :options="espacios.map(s=>({value:s.recurso_id,label:`${s.recurso.codigo} · ${s.recurso.nombre}`}))" :error="form.errors.espacio_actual_id"/>
      </div><p class="hint" v-if="!sensor">Use mayúsculas A–Z, números, guion o guion bajo; comience con una letra o número. El identificador no podrá modificarse.</p>
      <p class="hint" v-else>Al reasociar, el historial anterior conserva su espacio. Actualice el código del espacio en el dispositivo antes de enviar nuevos eventos.</p>
      <p class="alert" v-if="!espacios.length">Registre primero un espacio académico para asociar el sensor.</p>
      <div class="form-actions"><Link href="/iot/sensores" class="btn btn-secondary">Cancelar</Link><button class="btn btn-primary" :disabled="form.processing || !espacios.length">{{ form.processing ? 'Guardando…' : 'Guardar sensor' }}</button></div>
    </form>
  </AppLayout>
</template>
