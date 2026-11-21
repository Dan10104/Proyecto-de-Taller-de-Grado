<script setup>
import {computed} from 'vue';
import {Link,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import OperationForm from '../../Components/OperationForm.vue';
import {options} from '../../equipment';
import {focusError} from '../../spaces';
import {useAccess} from '../../security';
const {can}=useAccess();
const props=defineProps({recursos:Array,seleccion:Object,ahora:String});
const form=useForm({recurso_id:props.seleccion.recurso_id||'',tipo:'INCIDENCIA',descripcion:'',inicio:props.ahora,fin_previsto:'',estado_espacio:'EN_MANTENIMIENTO'});
const selected=computed(()=>props.recursos.find(r=>String(r.id)===String(form.recurso_id)));
function save(){form.transform(d=>selected.value?.equipamiento_exists?Object.fromEntries(Object.entries(d).filter(([k])=>k!=='estado_espacio')):d).post('/incidencias',{onError:focusError});}
</script>
<template>
 <AppLayout title="Registrar incidencia o mantenimiento" section="Incidencias">
  <div class="page-heading"><div><h1>Registrar incidencia o mantenimiento</h1><p>Recurso afectado y condición observada.</p></div><Link :href="can('incidencias.ver') ? '/incidencias' : '/inicio'" class="btn btn-secondary">{{ can('incidencias.ver') ? 'Volver al listado' : 'Volver al inicio' }}</Link></div>
  <section class="panel form-panel equipment-flow">
   <OperationForm label="Registrar restricción" message="El registro abierto restringirá inmediatamente el recurso. Los préstamos y reservas existentes conservarán su estado y deberán revisarse." :disabled="form.processing" @confirm="save"><FormErrors :errors="form.errors"/>
    <header class="form-section-heading"><h2>Recurso</h2></header>
    <div class="form-grid">
     <InputField id="recurso" label="Recurso afectado" v-model="form.recurso_id" required :options="recursos.map(r=>({value:r.id,label:r.codigo+' · '+r.nombre}))" :error="form.errors.recurso_id"/>
     <InputField id="tipo" label="Tipo de registro" v-model="form.tipo" required :options="options(['INCIDENCIA','MANTENIMIENTO'])" :error="form.errors.tipo"/>
     <InputField v-if="selected && !selected.equipamiento_exists" id="estado-espacio" label="Condición operativa del espacio" v-model="form.estado_espacio" required :options="options(['EN_MANTENIMIENTO','BLOQUEADO'])" :error="form.errors.estado_espacio"/>
    </div>
    <header class="form-section-heading"><h2>Periodo</h2><p>Fechas y horas de Bolivia.</p></header>
    <div class="form-grid">
     <InputField id="inicio" label="Inicio" type="datetime-local" v-model="form.inicio" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.inicio"/>
     <InputField id="fin" label="Fin previsto (opcional)" type="datetime-local" v-model="form.fin_previsto" min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.fin_previsto"/>
    </div>
    <header class="form-section-heading"><h2>Descripción</h2></header>
    <InputField id="descripcion" label="Descripción" type="textarea" v-model="form.descripcion" required maxlength="16000" :error="form.errors.descripcion"/>
    <p class="alert alert-warning">Un registro abierto impide nuevas asignaciones desde ahora, aunque su inicio sea posterior. El fin previsto no lo cierra automáticamente.</p>
    <p class="hint">Registre el cierre después de verificar la condición del recurso.</p>
   </OperationForm>
  </section>
 </AppLayout>
</template>
