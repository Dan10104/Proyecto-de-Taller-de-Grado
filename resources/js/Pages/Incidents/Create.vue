<script setup>
import {computed} from 'vue';
import {Link,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import OperationForm from '../../Components/OperationForm.vue';
import {options} from '../../equipment';
import {focusError} from '../../spaces';
const props=defineProps({recursos:Array,seleccion:Object,ahora:String});
const form=useForm({recurso_id:props.seleccion.recurso_id||'',tipo:'INCIDENCIA',descripcion:'',inicio:props.ahora,fin_previsto:'',estado_espacio:'EN_MANTENIMIENTO'});
const selected=computed(()=>props.recursos.find(r=>String(r.id)===String(form.recurso_id)));
function save(){form.transform(d=>selected.value?.equipamiento_exists?Object.fromEntries(Object.entries(d).filter(([k])=>k!=='estado_espacio')):d).post('/incidencias',{onError:focusError});}
</script>
<template>
 <AppLayout title="Registrar incidencia o mantenimiento" section="Incidencias"><div class="page-heading"><div><h1>Registrar incidencia o mantenimiento</h1><p>Describa la condición observada y el recurso afectado.</p></div><Link href="/incidencias" class="btn btn-secondary">Volver al listado</Link></div>
  <section class="panel form-panel"><OperationForm label="Registrar restricción" message="El registro abierto restringirá inmediatamente el recurso. Los préstamos y reservas existentes conservarán su estado y deberán revisarse." :disabled="form.processing" @confirm="save"><FormErrors :errors="form.errors"/><div class="form-grid">
   <InputField id="recurso" label="Recurso afectado" v-model="form.recurso_id" required :options="recursos.map(r=>({value:r.id,label:r.codigo+' · '+r.nombre}))" :error="form.errors.recurso_id"/>
   <InputField id="tipo" label="Tipo de registro" v-model="form.tipo" required :options="options(['INCIDENCIA','MANTENIMIENTO'])" :error="form.errors.tipo"/>
   <InputField id="inicio" label="Inicio" type="datetime-local" v-model="form.inicio" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.inicio"/>
   <InputField id="fin" label="Fin previsto (opcional)" type="datetime-local" v-model="form.fin_previsto" min="1000-01-01T00:00" max="9999-12-31T23:59" :error="form.errors.fin_previsto"/>
   <InputField v-if="selected && !selected.equipamiento_exists" id="estado-espacio" label="Condición operativa del espacio" v-model="form.estado_espacio" required :options="options(['EN_MANTENIMIENTO','BLOQUEADO'])" :error="form.errors.estado_espacio"/>
   <InputField class="span-2" id="descripcion" label="Descripción" type="textarea" v-model="form.descripcion" required maxlength="16000" :error="form.errors.descripcion"/>
  </div><p class="alert">Toda incidencia o mantenimiento abierto impide nuevas asignaciones, incluso si su inicio está previsto para más adelante. El fin previsto no cierra el registro automáticamente.</p><p class="hint">Fechas y horas de Bolivia. Complete el cierre después de verificar la condición del recurso.</p></OperationForm></section>
 </AppLayout>
</template>
