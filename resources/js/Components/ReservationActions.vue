<script setup>
import { keepDialogFocus } from '../dialogFocus';
import { computed, nextTick, ref, useId } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputField from './InputField.vue';
import FormErrors from './FormErrors.vue';
const props = defineProps({ reserva: { type: Object, required: true } });
const dialog = ref(null), action = ref('aprobar');
const form = useForm({ motivo: '' });
const titleId = useId();
const reasonId = useId();
const labels = { aprobar: 'Aprobar reserva', rechazar: 'Rechazar solicitud', cancelar: 'Cancelar reserva' };
const confirmations = { aprobar: 'Confirmar aprobación', rechazar: 'Confirmar rechazo', cancelar: 'Confirmar cancelación' };
const reasonField = computed(() => action.value === 'rechazar' ? 'motivo_rechazo' : 'motivo_cancelacion');
function open(next) {
  action.value = next;
  form.reset();
  form.clearErrors();
  dialog.value.showModal();
}
function submit() {
  form.transform(data => action.value === 'aprobar' ? {} : { [reasonField.value]: data.motivo || null })
    .post(`/reservas/${props.reserva.id}/${action.value}`, {
      preserveScroll: true,
      onSuccess: async () => { dialog.value?.close(); await nextTick(); document.querySelector('h1')?.focus(); },
      onError: async () => { await nextTick(); (dialog.value?.querySelector('[aria-invalid="true"]') || dialog.value?.querySelector('[role="alert"]'))?.focus(); },
    });
}
</script>
<template>
  <div v-if="reserva.acciones.resolver || reserva.acciones.cancelar" class="panel reservation-actions">
    <div><h2>Acciones disponibles</h2><p class="muted">Revise el periodo y el propósito antes de confirmar.</p></div>
    <div class="button-group">
      <button v-if="reserva.acciones.resolver" type="button" class="btn btn-primary" @click="open('aprobar')">Aprobar</button>
      <button v-if="reserva.acciones.resolver" type="button" class="btn btn-secondary" @click="open('rechazar')">Rechazar</button>
      <button v-if="reserva.acciones.cancelar" type="button" class="btn btn-danger" @click="open('cancelar')">Cancelar reserva</button>
    </div>
  </div>
  <Teleport to="body">
    <dialog @keydown="keepDialogFocus" ref="dialog" class="confirm-dialog reservation-dialog" :aria-labelledby="titleId" @cancel="form.processing && $event.preventDefault()">
      <form @submit.prevent="submit">
        <h2 :id="titleId">{{ labels[action] }}</h2>
        <p v-if="action === 'aprobar'">Se comprobará nuevamente la disponibilidad de {{ reserva.espacio.nombre }} antes de aprobar esta solicitud.</p>
        <p v-else-if="action === 'rechazar'">La solicitud quedará rechazada. Esta decisión no se puede deshacer.</p>
        <p v-else>La reserva quedará cancelada. Solo es posible confirmar antes de su hora de inicio.</p>
        <p><strong>{{ reserva.espacio.nombre }}</strong></p>
        <FormErrors :errors="form.errors"/>
        <InputField v-if="action !== 'aprobar'" :id="reasonId" label="Motivo (opcional)" v-model="form.motivo" type="textarea" maxlength="500" :error="form.errors[reasonField]"/>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" autofocus :disabled="form.processing" @click="dialog.close()">Volver</button>
          <button class="btn" :class="action === 'aprobar' ? 'btn-primary' : 'btn-danger'" :disabled="form.processing">{{ form.processing ? 'Procesando…' : confirmations[action] }}</button>
        </div>
      </form>
    </dialog>
  </Teleport>
</template>
