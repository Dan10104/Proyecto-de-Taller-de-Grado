<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import { focusError } from '../../spaces';
const props = defineProps({ espacio: Object, periodo: Object });
const form = useForm({ espacio_id: props.espacio.id, inicio: props.periodo.inicio, fin: props.periodo.fin, proposito: '' });
</script>
<template>
  <AppLayout title="Solicitar reserva" section="Reservas">
    <div class="page-heading">
      <div><h1 tabindex="-1">Solicitar reserva</h1><p>Indique el propósito de su actividad y revise el horario. Hora de Bolivia.</p></div>
      <Link href="/disponibilidad" class="btn btn-secondary">Elegir otro espacio</Link>
    </div>
    <form class="panel form-panel" @submit.prevent="form.post('/reservas', { onError: focusError })">
      <FormErrors :errors="form.errors"/>
      <div class="reservation-space-summary"><span class="section-context">{{ espacio.codigo }}</span><h2>{{ espacio.nombre }}</h2><p>{{ espacio.tipo }} · {{ espacio.piso }} · {{ espacio.capacidad }} personas</p></div>
      <div class="form-grid">
        <InputField id="reserva-inicio" label="Inicio" v-model="form.inicio" type="datetime-local" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.inicio"/>
        <InputField id="reserva-fin" label="Fin" v-model="form.fin" type="datetime-local" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.fin"/>
        <InputField class="span-2" id="reserva-proposito" label="Propósito de la actividad" v-model="form.proposito" type="textarea" required maxlength="500" :error="form.errors.proposito"/>
      </div>
      <p class="hint">{{ form.proposito.length }}/500 caracteres. Los campos con * son obligatorios.</p>
      <div class="alert reservation-explanation">Su solicitud quedará pendiente de revisión. El horario se confirmará cuando un responsable la apruebe.</div>
      <div class="form-actions"><Link href="/disponibilidad" class="btn btn-secondary">Volver</Link><button class="btn btn-primary" :disabled="form.processing">{{ form.processing ? 'Registrando…' : 'Confirmar solicitud' }}</button></div>
    </form>
  </AppLayout>
</template>
