<script setup>
import {ref,computed} from 'vue';
import {Link,router} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import QrScanner from '../../Components/QrScanner.vue';
import QrHistory from '../../Components/QrHistory.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import ReservationState from '../../Components/ReservationState.vue';
import {dateLabel} from '../../security';
import {qrPost} from '../../qr';
const props=defineProps({reserva:Object,validaciones:Object});const busy=ref(false),result=ref('');
async function validate(token){busy.value=true;result.value='';let data={qr_token:token};token=null;try{const response=await qrPost(`/reservas/${props.reserva.id}/qr`,data);result.value=(response.validacion.resultado==='VALIDA'?'Uso validado. ':'Validación inválida. ')+response.message;router.reload({only:['validaciones','reserva']});}catch(e){result.value=e.message;}finally{data=null;busy.value=false;}}
// Solo presentación: separa el veredicto del motivo ya devuelto por el servidor.
const verdict=computed(()=>result.value.startsWith('Uso validado. ')?'VALIDA':result.value.startsWith('Validación inválida. ')?'INVALIDA':null);
const reason=computed(()=>verdict.value?result.value.replace(/^(Uso validado|Validación inválida)\. /,''):result.value);
</script>
<template>
 <AppLayout title="Validar uso mediante QR" section="Reservas">
  <div class="page-heading">
   <div><h1>{{reserva.espacio}}</h1><p><span class="reservation-code">{{reserva.codigo}}</span> · {{dateLabel(reserva.inicio)}} — {{dateLabel(reserva.fin)}}</p></div>
   <Link :href="`/reservas/${reserva.id}`" class="btn btn-secondary">Volver a la reserva</Link>
  </div>
  <section class="panel qr-validate" aria-labelledby="qr-validate-title">
   <div class="qr-validate-scan">
    <div class="panel-heading"><h2 id="qr-validate-title">Escanear código</h2><span><span class="sr-only">Estado de la reserva: </span><ReservationState :value="reserva.estado"/></span></div>
    <p class="hint">Escanee la etiqueta del espacio o el código de esta reserva. Solo una reserva aprobada y dentro de su periodo permite validar el uso.</p>
    <QrScanner :disabled="busy" @decoded="validate"/>
   </div>
   <div class="qr-validate-result">
    <div v-if="result" class="qr-verdict" :class="verdict==='VALIDA'?'is-valid':verdict==='INVALIDA'?'is-invalid':'is-error'" role="status" aria-live="polite">
     <StatusBadge v-if="verdict" :value="verdict"/>
     <strong class="qr-verdict-title">{{verdict==='VALIDA'?'Uso validado':verdict==='INVALIDA'?'Validación rechazada':'No se pudo validar'}}</strong>
     <p>{{reason}}</p>
    </div>
    <div v-else class="qr-verdict is-idle">
     <strong class="qr-verdict-title">Sin lectura todavía</strong>
     <p>Inicie la cámara y apunte al código. El resultado aparecerá aquí.</p>
    </div>
   </div>
  </section>
  <QrHistory :validaciones="validaciones"/>
 </AppLayout>
</template>
