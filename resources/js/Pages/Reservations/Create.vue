<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import SpaceIllustration from '../../Components/SpaceIllustration.vue';
import Icon from '../../Components/Icon.vue';
import { focusError } from '../../spaces';
const props = defineProps({ espacio: Object, periodo: Object });
const form = useForm({ espacio_id: props.espacio.id, inicio: props.periodo.inicio, fin: props.periodo.fin, proposito: '' });
</script>
<template>
  <AppLayout title="Solicitar reserva" section="Reservas">
    <div class="page-heading">
      <div><h1 tabindex="-1">Solicitar reserva</h1><p>Revise el horario e indique el propósito. Hora de Bolivia.</p></div>
      <Link href="/disponibilidad" class="btn btn-secondary">Elegir otro espacio</Link>
    </div>
    <form class="panel form-panel" @submit.prevent="form.post('/reservas', { onError: focusError })">
      <FormErrors :errors="form.errors"/>
      <div class="reservation-space-summary">
        <SpaceIllustration class="reservation-space-art" :kind="espacio.tipo + ' ' + espacio.nombre"/>
        <div>
          <h2>{{ espacio.nombre }}</h2>
          <p class="availability-facts">
            <span class="reservation-code">{{ espacio.codigo }}</span>
            <span><Icon name="layers"/>{{ espacio.tipo }}</span>
            <span><Icon name="building"/>{{ espacio.piso }}</span>
            <span><Icon name="users"/>{{ espacio.capacidad }} personas</span>
          </p>
        </div>
      </div>
      <div class="form-section-heading"><h2>Horario</h2></div>
      <div class="form-grid">
        <InputField id="reserva-inicio" label="Inicio" v-model="form.inicio" type="datetime-local" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.inicio"/>
        <InputField id="reserva-fin" label="Fin" v-model="form.fin" type="datetime-local" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.fin"/>
      </div>
      <div class="form-section-heading"><h2>Actividad</h2></div>
      <InputField id="reserva-proposito" label="Propósito de la actividad" v-model="form.proposito" type="textarea" required maxlength="500" :error="form.errors.proposito"/>
      <p class="hint">{{ form.proposito.length }}/500 caracteres. Los campos con * son obligatorios.</p>
      <div class="alert reservation-explanation">Su solicitud quedará pendiente de revisión. El horario se confirmará cuando un responsable la apruebe.</div>
      <div class="form-actions"><Link href="/disponibilidad" class="btn btn-secondary">Volver</Link><button class="btn btn-primary" :disabled="form.processing">{{ form.processing ? 'Registrando…' : 'Confirmar solicitud' }}</button></div>
    </form>
  </AppLayout>
</template>
