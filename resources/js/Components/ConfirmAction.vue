<script setup>
import { ref, useId } from 'vue';

defineProps({
  label: { type: String, required: true },
  message: { type: String, required: true },
});

const emit = defineEmits(['confirm']);
const dialog = ref(null);
const titleId = useId();

function confirm() {
  dialog.value.close();
  emit('confirm');
}
</script>

<template>
  <button type="button" class="danger-link" @click="dialog.showModal()">{{ label }}</button>
  <Teleport to="body">
    <dialog ref="dialog" class="confirm-dialog" :aria-labelledby="titleId">
      <h2 :id="titleId">{{ label }}</h2>
      <p>{{ message }}</p>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" autofocus @click="dialog.close()">Cancelar</button>
        <button type="button" class="btn btn-danger" @click="confirm">Confirmar</button>
      </div>
    </dialog>
  </Teleport>
</template>
