<script setup>
import {ref,computed,onMounted,watch} from 'vue';
import QrHistory from '../../Components/QrHistory.vue';
import {Link} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import OperationalState from '../../Components/OperationalState.vue';
import LoanActions from '../../Components/LoanActions.vue';
import Icon from '../../Components/Icon.vue';
import {person,labels} from '../../equipment';
import {reservationDate} from '../../reservations';
import {useAccess} from '../../security';
const props=defineProps({prestamo:Object});
const qrMessage=ref(''),qrHistory=ref(null);
async function loadHistory(page=1){if(!can('qr.validar_operacion')||!can('prestamos.ver_administrativos'))return;try{const r=await fetch(`/prestamos/${props.prestamo.id}/qr/validaciones?page=${page}`,{headers:{Accept:'application/json'},cache:'no-store'});if(r.ok)qrHistory.value=(await r.json()).validaciones;}catch{qrHistory.value=null;}}
onMounted(()=>loadHistory());watch(()=>props.prestamo,()=>loadHistory());
const {can}=useAccess();

// Solo presentación: la ruta del proceso y el paso actual se derivan de los datos recibidos.
const steps=computed(()=>{
 const p=props.prestamo,rejected=p.estado==='RECHAZADO',d=p.devolucion;
 const reviewed=!!d && d.estado!=='PENDIENTE_REVISION';
 return [
  {label:'Solicitud',state:'done',time:p.solicitado_en},
  {label:'Resolución',state:rejected?'rejected':p.resuelto_en?'done':'current',time:p.resuelto_en},
  {label:'Entrega',state:rejected?'skipped':p.entrega?'done':p.estado==='APROBADO'?'current':'pending',time:p.entrega?.fecha_hora},
  {label:'Devolución',state:rejected?'skipped':d?'done':p.estado==='ENTREGADO'?'current':'pending',time:d?.registrada_en},
  {label:'Revisión',state:rejected?'skipped':reviewed?'done':p.revision_pendiente?'current':'pending',time:d?.confirmada_en},
 ];
});
const stepText={done:'Completado',current:'En curso',pending:'Pendiente',skipped:'No aplica',rejected:'Rechazada'};
const now=computed(()=>{
 const p=props.prestamo;
 if(p.estado==='RECHAZADO')return {title:'Solicitud rechazada',text:'El proceso terminó con la resolución.'};
 if(p.revision_pendiente)return {title:'Revisión de la devolución pendiente',text:'La recepción está registrada y falta confirmar su revisión.'};
 if(p.estado==='PENDIENTE')return {title:'Esperando resolución',text:'Solicitud pendiente: el equipo aún no está comprometido.'};
 if(p.estado==='APROBADO')return {title:'Pendiente de entrega',text:'El equipo está comprometido para el periodo previsto. Falta registrar la entrega.'};
 if(p.estado==='ENTREGADO')return {title:'Equipo entregado',text:'El equipo está en custodia del solicitante. Falta registrar la devolución.'};
 if(p.estado==='DEVUELTO')return {title:'Préstamo concluido',text:'La devolución está registrada.'};
 return {title:labels[p.estado]||p.estado,text:''};
});
const yourAction=computed(()=>{const a=props.prestamo.acciones||{};return a.resolver?'Resolver la solicitud':a.entregar?'Registrar la entrega':a.revisar?'Confirmar la revisión de la devolución':a.devolver?'Registrar la devolución':'';});
</script>
<template>
 <AppLayout title="Detalle de préstamo" section="Préstamos">
  <div class="page-heading">
   <div><div class="equipment-title"><h1>{{prestamo.equipamiento.nombre}}</h1><div class="operational-badges"><OperationalState :value="prestamo.estado"/><span v-if="prestamo.vencido" class="badge badge-negative"><svg class="badge-mark" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3.4v3.2M6 8.6h.01M10.5 6a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>Vencido</span><OperationalState v-if="prestamo.revision_pendiente" value="PENDIENTE_REVISION"/></div></div><p><span class="reservation-code">{{prestamo.codigo}}</span> · {{prestamo.equipamiento.codigo}} · {{reservationDate(prestamo.inicio_previsto)}} – {{reservationDate(prestamo.fin_previsto)}}</p></div>
   <div class="button-group"><Link href="/prestamos" class="btn btn-secondary">Volver a préstamos</Link><Link v-if="can('qr.gestionar') && can('prestamos.ver_administrativos')" :href="'/qr/prestamo/'+prestamo.id" class="btn btn-secondary">Gestionar QR</Link></div>
  </div>
  <p v-if="prestamo.vencido" class="alert alert-error">{{prestamo.revision_pendiente?'El fin previsto ya pasó. La recepción está registrada y falta confirmar su revisión.':'El préstamo está vencido y continúa pendiente de devolución.'}}</p>

  <section class="panel loan-status" aria-labelledby="loan-now-title">
   <div class="loan-now">
    <div><h2 id="loan-now-title">{{now.title}}</h2><p v-if="now.text">{{now.text}}</p></div>
    <a v-if="yourAction" href="#accion-prestamo" class="btn btn-secondary">{{yourAction}}</a>
   </div>
   <ol class="loan-steps" aria-label="Etapas del préstamo">
    <li v-for="(s,i) in steps" :key="s.label" :class="'is-'+s.state" :aria-current="s.state==='current'?'step':undefined">
     <span class="loan-step-mark" aria-hidden="true"><Icon v-if="s.state==='done'" name="check"/><Icon v-else-if="s.state==='rejected'" name="close"/><template v-else>{{i+1}}</template></span>
     <span class="loan-step-text"><strong>{{s.label}}</strong><small>{{s.state==='done' && s.time ? reservationDate(s.time) : stepText[s.state]}}</small></span>
    </li>
   </ol>
  </section>

  <p v-if="qrMessage" class="alert" role="status">{{qrMessage}}</p>
  <LoanActions @qr-result="qrMessage=$event" :key="prestamo.estado+'-'+prestamo.devolucion?.estado" :prestamo="prestamo"/>

  <section class="panel detail-panel">
   <h2>Solicitud</h2>
   <dl class="detail-grid">
    <div><dt>Inicio previsto</dt><dd>{{reservationDate(prestamo.inicio_previsto)}}</dd></div>
    <div><dt>Fin previsto</dt><dd>{{reservationDate(prestamo.fin_previsto)}}</dd></div>
    <div v-if="prestamo.solicitante"><dt>Solicitante</dt><dd>{{person(prestamo.solicitante)}}</dd></div>
    <div><dt>Estado físico del equipo</dt><dd><OperationalState :value="prestamo.equipamiento.estado"/></dd></div>
    <div class="span-2"><dt>Motivo</dt><dd class="equipment-text">{{prestamo.motivo}}</dd></div>
   </dl>
   <details class="disclosure">
    <summary>Ver detalles</summary>
    <dl class="detail-grid"><div><dt>Equipo</dt><dd>{{prestamo.equipamiento.codigo}} · {{prestamo.equipamiento.nombre}}</dd></div><div><dt>Tipo y ubicación base</dt><dd>{{prestamo.equipamiento.tipo}} · {{prestamo.equipamiento.ubicacion}}</dd></div></dl>
   </details>
   <div class="filter-actions"><p class="hint">Fechas y horas de Bolivia.</p><div class="button-group"><Link v-if="can('equipamientos.ver')" :href="'/equipamientos/'+prestamo.equipamiento.id" class="text-link">Ver equipo</Link><Link v-if="can('incidencias.ver')" :href="'/incidencias?recurso_id='+prestamo.equipamiento.id" class="text-link">Ver incidencias del equipo</Link></div></div>
  </section>

  <section class="panel detail-panel">
   <h2>Historial</h2>
   <ol class="reservation-history">
    <li><strong>Solicitud registrada</strong><time>{{reservationDate(prestamo.solicitado_en)}}</time></li>
    <li v-if="prestamo.resuelto_en"><strong>{{prestamo.estado==='RECHAZADO'?'Solicitud rechazada':'Préstamo aprobado'}}</strong><time>{{reservationDate(prestamo.resuelto_en)}}</time><span>{{person(prestamo.resuelto_por)}}</span><p v-if="prestamo.motivo_rechazo" class="equipment-text">{{prestamo.motivo_rechazo}}</p></li>
    <li v-if="prestamo.entrega"><strong>Entrega registrada</strong><time>{{reservationDate(prestamo.entrega.fecha_hora)}}</time><span>Entregó: {{person(prestamo.entrega.entregado_por)}} · Receptor: {{person(prestamo.entrega.receptor)}}</span><p class="equipment-text">{{prestamo.entrega.condicion_entrega}}</p><p v-if="prestamo.entrega.observacion" class="equipment-text">{{prestamo.entrega.observacion}}</p></li>
    <li v-if="prestamo.devolucion"><strong>Recepción de devolución</strong><time>{{reservationDate(prestamo.devolucion.registrada_en)}}</time><span>Recibió: {{person(prestamo.devolucion.recibido_por)}}</span><div class="operational-badges"><OperationalState :value="prestamo.devolucion.estado"/><OperationalState :value="prestamo.devolucion.condicion"/></div><p class="equipment-text">{{prestamo.devolucion.descripcion_condicion}}</p><p v-if="prestamo.devolucion.observacion" class="equipment-text">{{prestamo.devolucion.observacion}}</p></li>
    <li v-if="prestamo.devolucion?.confirmada_en"><strong>Devolución confirmada</strong><time>{{reservationDate(prestamo.devolucion.confirmada_en)}}</time><span>Confirmó: {{person(prestamo.devolucion.confirmado_por)}}</span></li>
   </ol>
  </section>
  <QrHistory v-if="qrHistory" :validaciones="qrHistory" async-pages @page="loadHistory"/>
 </AppLayout>
</template>
