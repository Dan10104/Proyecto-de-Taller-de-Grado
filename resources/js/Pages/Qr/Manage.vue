<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import {Link,router} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import OperationForm from '../../Components/OperationForm.vue';
import Pagination from '../../Components/Pagination.vue';
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
</script>
<template>
 <AppLayout title="Gestionar QR" section="QR">
  <div class="page-heading"><div><span class="eyebrow">QR DE {{objetivo.tipo.toUpperCase()}}</span><h1>{{objetivo.nombre}}</h1><p class="reservation-code">{{objetivo.codigo}}</p></div><Link href="/qr" class="btn btn-secondary">Elegir otro objetivo</Link></div>
  <p role="status" aria-live="polite" class="alert" v-if="message">{{message}}</p>
  <section class="panel form-panel"><h2>Emitir o rotar código</h2><p>El nuevo código reemplazará los códigos activos de este objetivo. El historial se conserva.</p>
   <OperationForm label="Generar nuevo QR" message="Se desactivarán los códigos anteriores de este objetivo. Imprima la nueva etiqueta antes de abandonar esta vista." :disabled="busy" @confirm="issue">
    <InputField id="qr-expiry" label="Vencimiento opcional · hora de Bolivia" type="datetime-local" v-model="expiry" min="1000-01-01T00:00" max="9999-12-31T23:59"/>
    <p class="hint">Deje vacío para conservarlo vigente hasta que se desactive o rote.</p>
   </OperationForm>
  </section>
  <section v-if="image" class="panel detail-panel qr-print"><h2>Etiqueta recién generada</h2><p>{{objetivo.nombre}} · {{objetivo.codigo}}</p><img :src="image" width="320" height="320" alt="Código QR recién generado para este objetivo" class="qr-image"/><div class="button-group no-print"><button class="btn btn-primary" type="button" @click="print">Imprimir etiqueta</button><button class="btn btn-secondary" type="button" @click="clear">Cerrar visualización</button></div></section>
  <p class="alert">El código solo se muestra durante esta generación. Después de salir o cerrar la visualización deberá rotarlo para volver a imprimirlo. La etiqueta física existente sigue funcionando mientras esté activa y vigente.</p>
  <section class="panel"><div class="panel-heading"><h2>Historial de códigos</h2></div><div v-if="codigos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Historial de códigos desplazable"><table><caption class="sr-only">Códigos del objetivo</caption><thead><tr><th>Referencia</th><th>Generación</th><th>Vencimiento</th><th>Estado</th><th>Acción</th></tr></thead><tbody><tr v-for="c in codigos.data" :key="c.id"><td>#{{c.id}}</td><td>{{dateLabel(c.generada_en)}}</td><td>{{c.vence_en?dateLabel(c.vence_en):'Sin vencimiento'}}</td><td>{{!c.activo?'Inactivo':c.vigente?'Activo y vigente':'Activo fuera de vigencia'}}</td><td><OperationForm v-if="c.activo" label="Desactivar QR" message="El código dejará de admitirse. Su historial se conservará." :disabled="busy" danger @confirm="deactivate(c.id)"/><span v-else>Historial conservado</span></td></tr></tbody></table></div><div v-else class="empty-state"><h3>Sin códigos emitidos</h3><p>Genere el primero cuando necesite identificar este objetivo.</p></div><Pagination :data="codigos"/></section>
 </AppLayout>
</template>
