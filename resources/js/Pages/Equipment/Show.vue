<script setup>
import {Link,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import OperationForm from '../../Components/OperationForm.vue';
import OperationalState from '../../Components/OperationalState.vue';
import {useAccess} from '../../security';
import {options} from '../../equipment';
import {focusError} from '../../spaces';
const props=defineProps({equipamiento:Object,incidenciasAbiertas:Number});
const {can}=useAccess();
const form=useForm({estado:props.equipamiento.estado==='PRESTADO'?'EN_MANTENIMIENTO':props.equipamiento.estado,apto:false});
function save(){form.patch('/equipamientos/'+props.equipamiento.id+'/estado',{onError:focusError,preserveScroll:true});}
</script>
<template>
 <AppLayout title="Detalle de equipamiento" section="Equipamiento">
  <div class="page-heading">
   <div><div class="equipment-title"><h1>{{equipamiento.nombre}}</h1><OperationalState :value="equipamiento.estado"/></div><p><span class="reservation-code">{{equipamiento.codigo}}</span> · {{equipamiento.tipo}}</p></div>
   <div class="button-group"><Link href="/equipamientos" class="btn btn-secondary">Volver al inventario</Link><Link v-if="can('qr.gestionar')" :href="'/qr/recurso/'+equipamiento.id" class="btn btn-secondary">Gestionar QR</Link><Link v-if="can('equipamientos.gestionar')" :href="'/equipamientos/'+equipamiento.id+'/editar'" class="btn btn-secondary">Editar equipo</Link><Link href="/equipamientos/disponibilidad" class="btn btn-primary">Consultar disponibilidad</Link></div>
  </div>
  <p v-if="incidenciasAbiertas" class="alert alert-warning">Este recurso tiene {{incidenciasAbiertas}} restricciones abiertas y no está disponible para nuevos préstamos.</p>

  <section class="panel detail-panel">
   <h2>Información del recurso</h2>
   <dl class="detail-grid">
    <div><dt>Tipo</dt><dd>{{equipamiento.tipo}}</dd></div>
    <div><dt>Marca / modelo</dt><dd>{{[equipamiento.marca,equipamiento.modelo].filter(Boolean).join(' · ')||'Sin especificar'}}</dd></div>
    <div><dt>Ubicación base</dt><dd>{{equipamiento.ubicacion}}</dd></div>
    <div class="span-2"><dt>Descripción</dt><dd class="equipment-text">{{equipamiento.descripcion||'Sin descripción adicional'}}</dd></div>
   </dl>
   <details class="disclosure">
    <summary>Ver datos técnicos</summary>
    <dl class="detail-grid"><div><dt>Número de serie</dt><dd>{{equipamiento.numero_serie||'Sin registrar'}}</dd></div><div><dt>Referencia de ubicación</dt><dd>{{equipamiento.referencia_ubicacion||'Sin referencia adicional'}}</dd></div></dl>
   </details>
   <div class="button-group equipment-links"><Link v-if="can('prestamos.ver_administrativos')" :href="'/prestamos?alcance=administrativos&equipamiento_id='+equipamiento.id" class="btn btn-secondary">Ver préstamos</Link><Link v-if="can('incidencias.ver')" :href="'/incidencias?recurso_id='+equipamiento.id" class="btn btn-secondary">Ver incidencias</Link><Link v-if="can('incidencias.gestionar')" :href="'/incidencias/crear?recurso_id='+equipamiento.id" class="text-link">Registrar incidencia o mantenimiento</Link></div>
  </section>

  <section v-if="can('equipamientos.cambiar_estado')" class="panel form-panel equipment-flow">
   <h2>Estado operativo</h2><p>La baja impide nuevos préstamos y entregas; las devoluciones pendientes siguen disponibles.</p>
   <OperationForm label="Actualizar estado" message="Confirme el estado físico inspeccionado. La operación volverá a comprobar las entregas, devoluciones e incidencias pendientes." :disabled="form.processing" @confirm="save">
    <FormErrors :errors="form.errors"/>
    <div class="equipment-state-field"><InputField id="estado-operativo" label="Nuevo estado" v-model="form.estado" required :options="options(['DISPONIBLE','EN_MANTENIMIENTO','BAJA'])" :error="form.errors.estado"/></div>
    <label v-if="form.estado==='DISPONIBLE'" class="check-option equipment-check"><input type="checkbox" v-model="form.apto" required>Confirmo que el equipo fue inspeccionado y está apto para su uso.</label>
   </OperationForm>
  </section>
 </AppLayout>
</template>
