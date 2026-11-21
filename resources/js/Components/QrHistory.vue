<script setup>
import {dateLabel} from '../security';
import Pagination from './Pagination.vue';
import StatusBadge from './StatusBadge.vue';
defineProps({validaciones:Object, asyncPages:Boolean});
defineEmits(['page']);
// Solo presentación: nombre legible del tipo de operación (valores exactos del enum).
const operations={USO_ESPACIO:'Uso del espacio',ENTREGA:'Entrega',DEVOLUCION:'Devolución'};
const operation=value=>operations[value]||value.replaceAll('_',' ');
</script>
<template>
 <section class="panel detail-panel qr-history">
  <div class="panel-heading"><h2>Historial de validaciones</h2><p v-if="validaciones?.total">{{validaciones.total}} {{validaciones.total===1?'intento':'intentos'}}</p></div>
  <ol v-if="validaciones?.data?.length" class="qr-history-list">
   <li v-for="v in validaciones.data" :key="v.id">
    <StatusBadge :value="v.resultado"/>
    <div class="qr-history-body">
     <strong>{{operation(v.tipo_operacion)}}</strong>
     <p v-if="v.motivo_rechazo">{{v.motivo_rechazo}}</p>
     <details v-if="v.entrega_id || v.devolucion_id" class="qr-history-tech"><summary>Ver detalles técnicos</summary><span v-if="v.entrega_id">Asociada a la entrega #{{v.entrega_id}}</span><span v-if="v.devolucion_id">Asociada a la devolución #{{v.devolucion_id}}</span></details>
    </div>
    <time :datetime="v.fecha_hora">{{dateLabel(v.fecha_hora)}}</time>
   </li>
  </ol>
  <p v-else class="hint">Todavía no se registraron validaciones QR para esta operación.</p>
  <nav v-if="asyncPages && validaciones?.last_page > 1" class="button-group" aria-label="Páginas de validaciones"><button class="btn btn-secondary" :disabled="!validaciones.prev_page_url" @click="$emit('page',validaciones.current_page-1)">Anterior</button><span class="hint">Página {{validaciones.current_page}} de {{validaciones.last_page}}</span><button class="btn btn-secondary" :disabled="!validaciones.next_page_url" @click="$emit('page',validaciones.current_page+1)">Siguiente</button></nav>
  <Pagination v-else-if="!asyncPages && validaciones?.links" :data="validaciones"/>
 </section>
</template>
