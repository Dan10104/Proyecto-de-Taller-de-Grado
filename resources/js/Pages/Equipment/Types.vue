<script setup>
import {ref} from 'vue';
import {Link,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import {focusError} from '../../spaces';
defineProps({tipos:Object});
const editing=ref(null),form=useForm({codigo:'',nombre:''});
function edit(t){editing.value=t.id;form.codigo=t.codigo;form.nombre=t.nombre;form.clearErrors();document.getElementById('codigo')?.focus();}
function reset(){editing.value=null;form.reset();form.clearErrors();}
function save(){const opts={onSuccess:reset,onError:focusError,preserveScroll:true};editing.value?form.put('/equipamientos/tipos/'+editing.value,opts):form.post('/equipamientos/tipos',opts);}
</script>
<template>
 <AppLayout title="Tipos de equipamiento" section="Equipamiento">
  <Link href="/equipamientos" class="back-link">Volver al inventario</Link>
  <div class="page-heading"><div><h1>Tipos de equipamiento</h1><p>Clasificación del inventario.</p></div></div>
  <div class="equipment-types">
   <form class="panel form-panel" @submit.prevent="save">
    <h2>{{editing?'Editar tipo':'Registrar tipo'}}</h2>
    <FormErrors :errors="form.errors"/>
    <div class="form-grid"><InputField id="codigo" label="Código" v-model="form.codigo" required maxlength="60" :error="form.errors.codigo"/><InputField id="nombre" label="Nombre" v-model="form.nombre" required maxlength="120" :error="form.errors.nombre"/></div>
    <div class="form-actions"><button v-if="editing" type="button" class="btn btn-secondary" @click="reset">Cancelar edición</button><button class="btn btn-primary" :disabled="form.processing">Guardar tipo</button></div>
   </form>
   <section class="panel">
    <div class="panel-heading"><h2>Catálogo de tipos</h2><p>{{tipos.total}} {{tipos.total===1?'tipo':'tipos'}}</p></div>
    <div v-if="tipos.data.length" class="table-scroll" role="region" aria-label="Tipos de equipamiento desplazables" tabindex="0">
     <table class="equipment-table"><caption class="sr-only">Tipos de equipamiento</caption><thead><tr><th>Código</th><th>Nombre</th><th class="align-right">Acción</th></tr></thead>
      <tbody><tr v-for="t in tipos.data" :key="t.id" :class="{'is-editing':editing===t.id}"><td class="reservation-code">{{t.codigo}}</td><td>{{t.nombre}}</td><td><div class="row-actions"><button class="text-button" type="button" @click="edit(t)">Editar<span class="sr-only"> {{t.nombre}}</span></button></div></td></tr></tbody>
     </table>
    </div>
    <div v-else class="empty-state"><Icon name="layers"/><h3>No hay tipos de equipo registrados.</h3><p>Agregue el primero con el formulario.</p></div>
   </section>
  </div>
  <Pagination :data="tipos"/>
 </AppLayout>
</template>
