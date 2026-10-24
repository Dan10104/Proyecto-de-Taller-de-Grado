<script setup>
import {ref} from 'vue';
import {Link,router} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import QrScanner from '../../Components/QrScanner.vue';
import QrHistory from '../../Components/QrHistory.vue';
import {dateLabel} from '../../security';
import {qrPost} from '../../qr';
const props=defineProps({reserva:Object,validaciones:Object});const busy=ref(false),result=ref('');
async function validate(token){busy.value=true;result.value='';let data={qr_token:token};token=null;try{const response=await qrPost(`/reservas/${props.reserva.id}/qr`,data);result.value=(response.validacion.resultado==='VALIDA'?'Uso validado. ':'Validación inválida. ')+response.message;router.reload({only:['validaciones','reserva']});}catch(e){result.value=e.message;}finally{data=null;busy.value=false;}}
</script>
<template>
 <AppLayout title="Validar uso mediante QR" section="Reservas">
  <div class="page-heading"><div><span class="eyebrow">VALIDACIÓN DE USO</span><h1>{{reserva.espacio}}</h1><p class="reservation-code">{{reserva.codigo}}</p></div><Link :href="`/reservas/${reserva.id}`" class="btn btn-secondary">Volver a la reserva</Link></div>
  <section class="panel detail-panel"><h2>Validar uso mediante QR</h2><p>{{dateLabel(reserva.inicio)}} — {{dateLabel(reserva.fin)}}</p><p>Estado de reserva: <strong>{{reserva.estado}}</strong>. Escanee la etiqueta del espacio o el código de esta reserva.</p><p class="hint">Solo una reserva aprobada dentro de su periodo permite validar el uso.</p><QrScanner :disabled="busy" @decoded="validate"/><p class="alert" role="status" aria-live="polite" v-if="result">{{result}}</p></section>
  <QrHistory :validaciones="validaciones"/>
 </AppLayout>
</template>
