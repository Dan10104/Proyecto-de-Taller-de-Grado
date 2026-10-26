<script setup>
import { keepDialogFocus } from '../dialogFocus';
import { ref, useId } from 'vue';
defineProps({ label:String, message:String, disabled:Boolean, danger:Boolean });
const emit=defineEmits(['confirm']);
const dialog=ref(null), title=useId();
function confirm(){ dialog.value.close();emit('confirm'); }
</script>
<template>
 <form @submit.prevent="dialog.showModal()">
  <slot/>
  <div class="form-actions"><button :disabled="disabled" class="btn" :class="danger ? 'btn-danger' : 'btn-primary'">{{ disabled ? 'Procesando…' : label }}</button></div>
 </form>
 <Teleport to="body"><dialog @keydown="keepDialogFocus" ref="dialog" class="confirm-dialog" :aria-labelledby="title">
  <h2 :id="title">{{ label }}</h2><p>{{ message }}</p>
  <div class="form-actions"><button type="button" class="btn btn-secondary" autofocus @click="dialog.close()">Cancelar</button><button type="button" class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" :disabled="disabled" @click="confirm">Confirmar</button></div>
 </dialog></Teleport>
</template>
