<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import {Link,router} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import OperationForm from '../../Components/OperationForm.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import {dateLabel} from '../../security';
import {qrPost} from '../../qr';
const props=defineProps({objetivo:Object,codigos:Object});
const expiry=ref(''), image=ref(''), busy=ref(false), message=ref(''), imageId=ref(null);
function clear(){if(image.value)URL.revokeObjectURL(image.value);image.value='';imageId.value=null;}
async function issue(){
 busy.value=true;message.value='';clear();
 try {const result=await qrPost(`/qr/${props.objetivo.tipo}/${props.objetivo.id}`,{vence_en:expiry.value||null});image.value=URL.createObjectURL(new Blob([result.svg],{type:'image/svg+xml'}));imageId.value=result.id;result.svg=null;message.value='Código generado. El código anterior quedó desactivado.';router.reload({only:['codigos']});}
 catch(e){message.value=e.message;} finally{busy.value=false;}
}
async function deactivate(id){busy.value=true;try{const result=await qrPost(`/qr/${props.objetivo.tipo}/${props.objetivo.id}/${id}/desactivar`,{});if(imageId.value===id)clear();message.value=result.message;router.reload({only:['codigos']});}catch(e){message.value=e.message;}finally{busy.value=false;}}
function print(){window.print();}
function hidden(){if(document.hidden)clear();}
onMounted(()=>{window.addEventListener('pagehide',clear);document.addEventListener('visibilitychange',hidden);});
onBeforeUnmount(()=>{clear();window.removeEventListener('pagehide',clear);document.removeEventListener('visibilitychange',hidden);});
// Solo presentación: el código activo más reciente (la emisión desactiva los anteriores).
const kinds={recurso:'Recurso físico',reserva:'Reserva',prestamo:'Préstamo'};
const firstPage=computed(()=>!props.codigos.current_page||props.codigos.current_page===1);
const current=computed(()=>props.codigos.data.find(c=>c.activo)||null);
const stateLabel=c=>!c.activo?'Inactivo':c.vigente?'Activo y vigente':'Activo fuera de vigencia';
const stateValue=c=>!c.activo?'INACTIVO':c.vigente?'ACTIVO':'FUERA_DE_VIGENCIA';
</script>
<template>
 <AppLayout title="Gestionar QR" section="QR">
  <div class="page-heading"><div><h1>{{objetivo.nombre}}</h1><p>{{kinds[objetivo.tipo]||objetivo.tipo}} · <span class="reservation-code">{{objetivo.codigo}}</span></p></div><Link href="/qr" class="btn btn-secondary">Elegir otro objetivo</Link></div>
  <p role="status" aria-live="polite" class="alert" v-if="message">{{message}}</p>

  <section class="panel qr-current" aria-labelledby="qr-current-title">
   <div class="qr-current-state">
    <div class="panel-heading"><h2 id="qr-current-title">Código actual</h2><template v-if="firstPage"><StatusBadge v-if="current" :value="stateValue(current)" :label="stateLabel(current)"/><StatusBadge v-else value="INACTIVO" label="Sin código activo"/></template></div>
    <dl v-if="firstPage && current" class="detail-grid"><div><dt>Generado</dt><dd>{{dateLabel(current.generada_en)}}</dd></div><div><dt>Vencimiento</dt><dd>{{current.vence_en?dateLabel(current.vence_en):'Sin vencimiento'}}</dd></div></dl>
    <p v-else-if="firstPage" class="hint">Este objetivo no tiene un código activo. Genere uno para imprimir su etiqueta.</p>
    <p class="alert alert-warning">El código solo se muestra al generarlo. Si sale o cierra la visualización, deberá rotarlo para volver a imprimirlo. La etiqueta física existente sigue funcionando mientras esté activa y vigente.</p>
   </div>
   <div class="qr-issue">
    <h2>Emitir o rotar código</h2>
    <p class="hint">El nuevo código reemplaza a los activos de este objetivo. El historial se conserva.</p>
    <OperationForm label="Generar nuevo QR" message="Se desactivarán los códigos anteriores de este objetivo. Imprima la nueva etiqueta antes de abandonar esta vista." :disabled="busy" @confirm="issue">
     <InputField id="qr-expiry" label="Vencimiento opcional · hora de Bolivia" type="datetime-local" v-model="expiry" min="1000-01-01T00:00" max="9999-12-31T23:59"/>
     <p class="hint">Déjelo vacío para que siga vigente hasta desactivarlo o rotarlo.</p>
    </OperationForm>
   </div>
  </section>

  <section v-if="image" class="panel qr-print" aria-labelledby="qr-print-title">
   <h2 id="qr-print-title">Etiqueta recién generada</h2>
   <p>{{objetivo.nombre}} · {{objetivo.codigo}}</p>
   <img :src="image" width="320" height="320" alt="Código QR recién generado para este objetivo" class="qr-image"/>
   <div class="button-group no-print"><button class="btn btn-primary" type="button" @click="print"><Icon name="print"/>Imprimir etiqueta</button><button class="btn btn-secondary" type="button" @click="clear">Cerrar visualización</button></div>
  </section>

  <section class="panel qr-codes">
   <div class="panel-heading"><h2>Historial de códigos</h2><p v-if="codigos.total">{{codigos.total}} {{codigos.total===1?'código':'códigos'}}</p></div>
   <div v-if="codigos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Historial de códigos desplazable">
    <table><caption class="sr-only">Códigos del objetivo</caption>
     <thead><tr><th>Generación</th><th>Vencimiento</th><th>Estado</th><th class="align-right">Acción</th></tr></thead>
     <tbody><tr v-for="c in codigos.data" :key="c.id"><td class="date-cell">{{dateLabel(c.generada_en)}}<small class="cell-subline">Referencia #{{c.id}}</small></td><td class="date-cell">{{c.vence_en?dateLabel(c.vence_en):'Sin vencimiento'}}</td><td><StatusBadge :value="stateValue(c)" :label="stateLabel(c)"/></td><td><div class="row-actions"><OperationForm v-if="c.activo" label="Desactivar QR" message="El código dejará de admitirse. Su historial se conservará." :disabled="busy" danger @confirm="deactivate(c.id)"/><span v-else class="hint">Historial conservado</span></div></td></tr></tbody>
    </table>
   </div>
   <div v-else class="empty-state"><Icon name="qr"/><h3>Sin códigos emitidos</h3><p>Genere el primero cuando necesite identificar este objetivo.</p></div>
   <Pagination :data="codigos"/>
  </section>
 </AppLayout>
</template>
