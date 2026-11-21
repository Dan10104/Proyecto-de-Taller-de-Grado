<script setup>
import {Link,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import OperationForm from '../../Components/OperationForm.vue';
import OperationalState from '../../Components/OperationalState.vue';
import Pagination from '../../Components/Pagination.vue';
import {person,labels} from '../../equipment';
import {reservationDate} from '../../reservations';
import {useAccess} from '../../security';
import {focusError} from '../../spaces';
const props=defineProps({incidencia:Object,prestamos:Object,reservas:Object,compromisos:Number});
const {can}=useAccess(),form=useForm({solucion:'',apto:false});
function close(){form.post('/incidencias/'+props.incidencia.id+'/cerrar',{onError:focusError,preserveScroll:true});}
</script>
<template>
 <AppLayout title="Detalle de incidencia" section="Incidencias">
  <Link href="/incidencias" class="back-link">Volver al listado</Link>
  <div class="page-heading">
   <div><div class="equipment-title"><h1>{{incidencia.recurso.nombre}}</h1><OperationalState :value="incidencia.estado"/></div><p>{{labels[incidencia.tipo]}} · <span class="reservation-code">{{incidencia.recurso.codigo}}</span></p></div>
  </div>

  <section class="panel detail-panel">
   <h2>Condición registrada</h2>
   <dl class="detail-grid">
    <div><dt>Inicio</dt><dd>{{reservationDate(incidencia.inicio)}}</dd></div>
    <div><dt>Fin previsto</dt><dd>{{incidencia.fin_previsto?reservationDate(incidencia.fin_previsto):'Sin fin previsto'}}</dd></div>
    <div><dt>Reportó</dt><dd>{{person(incidencia.reportado_por)}}</dd></div>
    <div><dt>Estado operativo del recurso</dt><dd><OperationalState :value="incidencia.recurso.estado"/></dd></div>
    <div class="span-2"><dt>Descripción</dt><dd class="equipment-text">{{incidencia.descripcion}}</dd></div>
   </dl>
   <Link v-if="incidencia.prestamo_asociado" :href="'/prestamos/'+incidencia.prestamo_asociado.id" class="text-link">Devolución asociada · {{incidencia.prestamo_asociado.codigo}}</Link>
  </section>

  <section v-if="incidencia.estado==='ABIERTA' && can('incidencias.cerrar')" class="panel form-panel loan-action" aria-labelledby="incident-close-title">
   <header class="loan-action-head"><h2 id="incident-close-title">Cerrar incidencia o mantenimiento</h2></header>
   <OperationForm label="Registrar cierre" message="El cierre conservará la solución y el responsable. La habilitación solo se realizará si la solicita y no quedan restricciones." :disabled="form.processing" @confirm="close"><FormErrors :errors="form.errors"/>
    <InputField id="solucion" label="Solución o justificación del cierre" type="textarea" v-model="form.solucion" required maxlength="16000" :error="form.errors.solucion"/>
    <label v-if="incidencia.recurso.estado!=='BAJA' && can(incidencia.recurso.equipamiento?'equipamientos.cambiar_estado':'espacios.cambiar_estado')" class="check-option equipment-check"><input type="checkbox" v-model="form.apto">Verifiqué la aptitud operativa y solicito habilitar el recurso si no quedan restricciones.</label>
    <p class="hint">Puede cerrar conservando el mantenimiento o bloqueo actual. Un recurso dado de baja necesita una reactivación administrativa aparte.</p>
   </OperationForm>
  </section>

  <section v-if="incidencia.estado==='CERRADA'" class="panel detail-panel">
   <h2>Cierre registrado</h2>
   <dl class="detail-grid"><div><dt>Responsable</dt><dd>{{person(incidencia.cerrado_por)}}</dd></div><div><dt>Fecha de cierre</dt><dd>{{reservationDate(incidencia.cerrada_en)}}</dd></div><div class="span-2"><dt>Solución</dt><dd class="equipment-text">{{incidencia.solucion}}</dd></div></dl>
  </section>

  <section class="panel detail-panel">
   <div class="panel-heading"><h2>Compromisos vigentes o futuros</h2><p>{{compromisos}}</p></div>
   <p class="hint">La incidencia no cancela ni rechaza los compromisos automáticamente. Revise cada caso con los responsables.</p>
   <div v-if="prestamos?.data.length"><h3 class="incident-list-title">Préstamos</h3><ul class="incident-commitments"><li v-for="p in prestamos.data" :key="p.id"><Link :href="'/prestamos/'+p.id" class="text-link reservation-code">{{p.codigo}}</Link><span>{{reservationDate(p.inicio_previsto)}} — {{reservationDate(p.fin_previsto)}}</span><OperationalState :value="p.estado"/></li></ul><Pagination :data="prestamos"/></div>
   <div v-if="reservas?.data.length"><h3 class="incident-list-title">Reservas</h3><ul class="incident-commitments"><li v-for="r in reservas.data" :key="r.id"><Link :href="'/reservas/'+r.id" class="text-link reservation-code">{{r.codigo}}</Link><span>{{reservationDate(r.inicio)}} — {{reservationDate(r.fin)}}</span></li></ul><Pagination :data="reservas"/></div>
   <p v-if="!compromisos" class="hint">No se encontraron compromisos vigentes o futuros.</p>
  </section>
 </AppLayout>
</template>
