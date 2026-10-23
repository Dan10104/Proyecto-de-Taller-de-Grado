<script setup>
import {Link} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import OperationalState from '../../Components/OperationalState.vue';
import LoanActions from '../../Components/LoanActions.vue';
import {person,labels} from '../../equipment';
import {reservationDate} from '../../reservations';
import {useAccess} from '../../security';
defineProps({prestamo:Object});
const {can}=useAccess();
</script>
<template>
 <AppLayout title="Detalle de préstamo" section="Préstamos">
  <div class="page-heading"><div><span class="eyebrow">PRÉSTAMO DE EQUIPAMIENTO</span><h1>{{prestamo.equipamiento.nombre}}</h1><p class="reservation-code">{{prestamo.codigo}}</p></div><div class="button-group"><OperationalState :value="prestamo.estado"/><Link href="/prestamos" class="btn btn-secondary">Volver a préstamos</Link></div></div>
  <p v-if="prestamo.vencido" class="alert">{{prestamo.revision_pendiente?'El fin previsto ya pasó. La recepción está registrada y falta confirmar su revisión.':'El préstamo está vencido y continúa pendiente de devolución.'}}</p>
  <section class="panel detail-panel"><h2>Solicitud y equipo</h2><dl class="detail-grid">
   <div><dt>Equipo</dt><dd>{{prestamo.equipamiento.codigo}} · {{prestamo.equipamiento.nombre}}</dd></div><div><dt>Tipo y ubicación base</dt><dd>{{prestamo.equipamiento.tipo}} · {{prestamo.equipamiento.ubicacion}}</dd></div>
   <div><dt>Inicio previsto</dt><dd>{{reservationDate(prestamo.inicio_previsto)}}</dd></div><div><dt>Fin previsto</dt><dd>{{reservationDate(prestamo.fin_previsto)}}</dd></div><div v-if="prestamo.solicitante"><dt>Solicitante</dt><dd>{{person(prestamo.solicitante)}}</dd></div><div><dt>Estado físico del equipo</dt><dd><OperationalState :value="prestamo.equipamiento.estado"/></dd></div><div class="span-2"><dt>Motivo</dt><dd class="operational-text">{{prestamo.motivo}}</dd></div>
  </dl><p v-if="prestamo.estado==='PENDIENTE'" class="alert">Solicitud pendiente: el equipo aún no está comprometido.</p><p class="hint">Fechas y horas de Bolivia.</p>
   <div class="button-group"><Link v-if="can('equipamientos.ver')" :href="'/equipamientos/'+prestamo.equipamiento.id" class="text-link">Ver equipo</Link><Link v-if="can('incidencias.ver')" :href="'/incidencias?recurso_id='+prestamo.equipamiento.id" class="text-link">Ver incidencias del equipo</Link></div>
  </section>
  <LoanActions :key="prestamo.estado+'-'+prestamo.devolucion?.estado" :prestamo="prestamo"/>
  <section class="panel detail-panel"><h2>Seguimiento del préstamo</h2><ol class="reservation-history">
   <li><strong>Solicitud registrada</strong><time>{{reservationDate(prestamo.solicitado_en)}}</time></li>
   <li v-if="prestamo.resuelto_en"><strong>{{prestamo.estado==='RECHAZADO'?'Solicitud rechazada':'Préstamo aprobado'}}</strong><time>{{reservationDate(prestamo.resuelto_en)}}</time><span>{{person(prestamo.resuelto_por)}}</span><p v-if="prestamo.motivo_rechazo" class="operational-text">{{prestamo.motivo_rechazo}}</p></li>
   <li v-if="prestamo.entrega"><strong>Entrega registrada</strong><time>{{reservationDate(prestamo.entrega.fecha_hora)}}</time><span>Entregó: {{person(prestamo.entrega.entregado_por)}} · Receptor: {{person(prestamo.entrega.receptor)}}</span><p class="operational-text">{{prestamo.entrega.condicion_entrega}}</p><p v-if="prestamo.entrega.observacion" class="operational-text">{{prestamo.entrega.observacion}}</p></li>
   <li v-if="prestamo.devolucion"><strong>Recepción de devolución</strong><time>{{reservationDate(prestamo.devolucion.registrada_en)}}</time><span>Recibió: {{person(prestamo.devolucion.recibido_por)}}</span><div class="operational-badges"><OperationalState :value="prestamo.devolucion.estado"/><OperationalState :value="prestamo.devolucion.condicion"/></div><p class="operational-text">{{prestamo.devolucion.descripcion_condicion}}</p><p v-if="prestamo.devolucion.observacion" class="operational-text">{{prestamo.devolucion.observacion}}</p></li>
   <li v-if="prestamo.devolucion?.confirmada_en"><strong>Devolución confirmada</strong><time>{{reservationDate(prestamo.devolucion.confirmada_en)}}</time><span>Confirmó: {{person(prestamo.devolucion.confirmado_por)}}</span></li>
  </ol></section>
 </AppLayout>
</template>
