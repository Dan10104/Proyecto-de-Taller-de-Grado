<script setup>
import {computed,ref} from 'vue';
import {useForm,router} from '@inertiajs/vue3';
import QrScanner from './QrScanner.vue';
import {qrPost} from '../qr';
import InputField from './InputField.vue';
import FormErrors from './FormErrors.vue';
import OperationForm from './OperationForm.vue';
import {useAccess} from '../security';
import {options,person} from '../equipment';
import {focusError} from '../spaces';
const props=defineProps({prestamo:Object});
const {can}=useAccess();
const emit=defineEmits(['qr-result']);
const deliveryQr=ref(false),returnQr=ref(false),scanAction=ref(''),qrBusy=ref(false),qrMessage=ref('');
let operationData=null;
const approve=useForm({}),reject=useForm({motivo_rechazo:''});
const delivery=useForm({condicion_entrega:'',observacion:'',equipo_verificado:false,receptor_verificado:false});
const returned=useForm({condicion:'',descripcion_condicion:'',observacion:'',apto:false});
const conditions=computed(()=>options(['ADECUADA',...(can('incidencias.gestionar')?['CON_INCIDENCIA']:[]),...(!props.prestamo.acciones.revisar?['NO_DETERMINABLE']:[])]));
function send(form,action){if((action==='entregar'&&deliveryQr.value)||(action==='devolver'&&returnQr.value)){operationData={...form.data()};scanAction.value=action;qrMessage.value='';return;}form.post('/prestamos/'+props.prestamo.id+'/'+action,{onError:focusError,preserveScroll:true,onSuccess:()=>form.reset()});}

function cancelScan(){scanAction.value='';operationData=null;}
async function submitQr(token){
 const action=scanAction.value;let data={...operationData,qr_token:token};token=null;qrBusy.value=true;
 try{const result=await qrPost(`/prestamos/${props.prestamo.id}/qr/${action}`,data);qrMessage.value=(result.validacion.resultado==='VALIDA'?'Operación confirmada. ':'Operación detenida. ')+result.message;emit('qr-result',qrMessage.value);cancelScan();router.reload({only:['prestamo']});}
 catch(e){qrMessage.value=e.message;emit('qr-result',qrMessage.value);cancelScan();}
 finally{data=null;qrBusy.value=false;}
}
</script>
<template>
 <section v-if="prestamo.acciones.resolver" id="accion-prestamo" class="panel form-panel loan-action" aria-labelledby="loan-resolve-title">
  <header class="loan-action-head"><h2 id="loan-resolve-title">Resolver solicitud</h2><p>La aprobación comprueba otra vez el estado del equipo y los compromisos del periodo.</p></header>
  <OperationForm label="Aprobar préstamo" message="La aprobación comprometerá la disponibilidad del equipo durante el periodo previsto." :disabled="approve.processing" @confirm="send(approve,'aprobar')"><FormErrors :errors="approve.errors"/></OperationForm>
  <details class="disclosure loan-secondary" :open="!!Object.keys(reject.errors).length || undefined">
   <summary>Rechazar solicitud</summary>
   <OperationForm label="Rechazar solicitud" message="La solicitud quedará rechazada con el motivo indicado. Esta decisión se conserva." :disabled="reject.processing" danger @confirm="send(reject,'rechazar')"><FormErrors :errors="reject.errors"/><InputField id="rechazo" label="Motivo del rechazo" type="textarea" v-model="reject.motivo_rechazo" required maxlength="500" :error="reject.errors.motivo_rechazo"/></OperationForm>
  </details>
 </section>

 <section v-if="prestamo.acciones.entregar" id="accion-prestamo" class="panel form-panel loan-action" aria-labelledby="loan-deliver-title">
  <header class="loan-action-head"><h2 id="loan-deliver-title">Registrar entrega</h2><p>Entregue el equipo al solicitante: <strong>{{person(prestamo.solicitante)}}</strong>.</p></header>
  <details v-if="can('qr.validar_operacion')" class="disclosure loan-secondary">
   <summary>Validar con código QR (opcional)</summary>
   <label class="check-option equipment-check"><input type="checkbox" v-model="deliveryQr" :disabled="!!scanAction||qrBusy">Validar mediante QR</label>
  </details>
  <OperationForm :label="deliveryQr?'Escanear y confirmar entrega':'Registrar entrega'" message="Confirme que la entrega física se realiza al solicitante y corresponde al equipo indicado. El préstamo quedará entregado y el equipo prestado." :disabled="delivery.processing||!!scanAction||qrBusy" @confirm="send(delivery,'entregar')"><fieldset :disabled="!!scanAction||qrBusy" class="loan-fields"><FormErrors :errors="delivery.errors"/>
   <InputField id="condicion-entrega" label="Condición observada al entregar" type="textarea" v-model="delivery.condicion_entrega" required maxlength="255" :error="delivery.errors.condicion_entrega"/>
   <InputField id="observacion-entrega" label="Observación adicional" type="textarea" v-model="delivery.observacion" maxlength="500" :error="delivery.errors.observacion"/>
   <div class="loan-checks">
    <label class="check-option equipment-check"><input type="checkbox" v-model="delivery.equipo_verificado" required>Verifiqué el equipo {{prestamo.equipamiento.codigo}} · {{prestamo.equipamiento.nombre}}.</label>
    <label class="check-option equipment-check"><input type="checkbox" v-model="delivery.receptor_verificado" required>Verifiqué que el receptor es el solicitante del préstamo.</label>
   </div></fieldset>
  </OperationForm>
 </section>

 <section v-if="prestamo.acciones.devolver || prestamo.acciones.revisar" id="accion-prestamo" class="panel form-panel loan-action" aria-labelledby="loan-return-title">
  <header class="loan-action-head"><h2 id="loan-return-title">{{prestamo.acciones.revisar?'Confirmar revisión de devolución':'Registrar devolución'}}</h2>
   <p>{{prestamo.acciones.revisar?'Complete la revisión de la recepción ya registrada; se conservan su responsable y fecha.':'Registre la recepción física y la condición verificada del equipo.'}}</p></header>
  <details v-if="!prestamo.acciones.revisar && can('qr.validar_operacion')" class="disclosure loan-secondary">
   <summary>Validar con código QR (opcional)</summary>
   <label class="check-option equipment-check"><input type="checkbox" v-model="returnQr" :disabled="!!scanAction||qrBusy">Validar mediante QR</label>
  </details>
  <OperationForm :label="returnQr && !prestamo.acciones.revisar?'Escanear y confirmar devolución':prestamo.acciones.revisar?'Confirmar revisión':'Registrar devolución'" message="Confirme la condición inspeccionada. La disponibilidad se recuperará solamente si no quedan restricciones y se cumplen las autorizaciones correspondientes." :disabled="returned.processing||!!scanAction||qrBusy" @confirm="send(returned,prestamo.acciones.revisar?'revisar':'devolver')">
   <fieldset :disabled="!!scanAction||qrBusy" class="loan-fields"><FormErrors :errors="returned.errors"/>
   <InputField id="condicion-devolucion" label="Condición de devolución" v-model="returned.condicion" :options="conditions" required :error="returned.errors.condicion"/>
   <p v-if="returned.condicion==='NO_DETERMINABLE'" class="alert alert-warning">La devolución quedará pendiente de revisión y el préstamo continuará entregado. El equipo quedará restringido.</p>
   <p v-if="returned.condicion==='CON_INCIDENCIA'" class="alert alert-warning">Se registrará una incidencia vinculada a esta devolución y se restringirá el equipo.</p>
   <InputField id="descripcion-condicion" label="Descripción de la condición" type="textarea" v-model="returned.descripcion_condicion" :required="returned.condicion==='CON_INCIDENCIA'||returned.condicion==='NO_DETERMINABLE'" maxlength="500" :error="returned.errors.descripcion_condicion"/>
   <InputField id="observacion-devolucion" label="Observación adicional" type="textarea" v-model="returned.observacion" maxlength="500" :error="returned.errors.observacion"/>
   <label v-if="returned.condicion==='ADECUADA' && prestamo.equipamiento.estado==='EN_MANTENIMIENTO' && can('equipamientos.cambiar_estado')" class="check-option equipment-check"><input type="checkbox" v-model="returned.apto">Confirmo aptitud operativa y solicito habilitar el equipo si no conserva restricciones.</label>
   <p v-if="prestamo.equipamiento.estado==='BAJA'" class="alert">El equipo conservará su baja después de registrar la devolución.</p></fieldset>
  </OperationForm>
 </section>

 <section v-if="scanAction" class="panel detail-panel loan-qr-scan" aria-labelledby="loan-qr-title"><h2 id="loan-qr-title">{{scanAction==='entregar'?'Confirmar entrega mediante QR':'Confirmar devolución mediante QR'}}</h2><p>Los datos y verificaciones ya están confirmados. Al leer el código se enviará la operación; solo continuará si la validación es correcta.</p><QrScanner :disabled="qrBusy" label="Escanear y confirmar" @decoded="submitQr"/><button class="btn btn-secondary" type="button" :disabled="qrBusy" @click="cancelScan">Cancelar flujo QR</button></section>
 <p v-if="qrMessage" class="alert" role="status" aria-live="polite">{{qrMessage}}</p>
</template>
