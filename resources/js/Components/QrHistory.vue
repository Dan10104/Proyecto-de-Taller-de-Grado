<script setup>
import {dateLabel} from '../security';
import Pagination from './Pagination.vue';
defineProps({validaciones:Object, asyncPages:Boolean});
defineEmits(['page']);
</script>
<template>
 <section class="panel detail-panel"><h2>Validaciones QR registradas</h2>
  <ol v-if="validaciones?.data?.length" class="reservation-history">
   <li v-for="v in validaciones.data" :key="v.id"><strong>{{v.resultado==='VALIDA'?'Validación válida':'Validación inválida'}} · {{v.tipo_operacion.replaceAll('_',' ')}}</strong><time>{{dateLabel(v.fecha_hora)}}</time><p v-if="v.motivo_rechazo">{{v.motivo_rechazo}}</p><span v-if="v.entrega_id">Asociada a la entrega #{{v.entrega_id}}</span><span v-if="v.devolucion_id">Asociada a la devolución #{{v.devolucion_id}}</span></li>
  </ol><p v-else class="muted">Todavía no se registraron intentos QR para esta operación.</p>
  <nav v-if="asyncPages && validaciones?.last_page > 1" class="button-group" aria-label="Páginas de validaciones"><button class="btn btn-secondary" :disabled="!validaciones.prev_page_url" @click="$emit('page',validaciones.current_page-1)">Anterior</button><span>Página {{validaciones.current_page}} de {{validaciones.last_page}}</span><button class="btn btn-secondary" :disabled="!validaciones.next_page_url" @click="$emit('page',validaciones.current_page+1)">Siguiente</button></nav>
  <Pagination v-else-if="!asyncPages && validaciones?.links" :data="validaciones"/>
 </section>
</template>
