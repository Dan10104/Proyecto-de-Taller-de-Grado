<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import {useAccess} from '../../security';
import {options} from '../../equipment';
import {focusError} from '../../spaces';
const props=defineProps({equipamiento:Object,opciones:Object});
const {can}=useAccess();
const fields=['tipo_equipamiento_id','espacio_base_id','descripcion','marca','modelo','numero_serie','referencia_ubicacion'];
const form=useForm({codigo:props.equipamiento?.recurso.codigo||'',nombre:props.equipamiento?.recurso.nombre||'',...Object.fromEntries(fields.map(k=>[k,props.equipamiento?.[k]||''])),...(!props.equipamiento && can('equipamientos.cambiar_estado') ? {estado:'DISPONIBLE'} : {})});
function save(){const opts={onError:focusError};props.equipamiento ? form.put('/equipamientos/'+props.equipamiento.recurso_id,opts) : form.post('/equipamientos',opts);}
</script>
<template>
 <AppLayout :title="equipamiento ? 'Editar equipamiento' : 'Registrar equipamiento'" section="Equipamiento">
  <Link href="/equipamientos" class="back-link">Volver al inventario</Link>
  <div class="page-heading"><div><h1>{{ equipamiento ? 'Editar equipamiento' : 'Registrar equipamiento' }}</h1><p><template v-if="equipamiento"><span class="reservation-code">{{equipamiento.recurso.codigo}}</span> · </template>Los campos con * son obligatorios.</p></div></div>
  <p v-if="!opciones.tipos.length" class="alert alert-warning">Primero registre un tipo de equipo. <Link class="text-link" href="/equipamientos/tipos">Administrar tipos</Link></p>
  <form class="panel form-panel" @submit.prevent="save"><FormErrors :errors="form.errors"/>
   <header class="form-section-heading"><h2>Identificación</h2></header>
   <div class="form-grid">
    <InputField id="codigo" label="Código institucional" v-model="form.codigo" required maxlength="60" :error="form.errors.codigo"/>
    <InputField id="nombre" label="Nombre" v-model="form.nombre" required maxlength="160" :error="form.errors.nombre"/>
    <InputField id="tipo" label="Tipo de equipo" v-model="form.tipo_equipamiento_id" required :options="opciones.tipos.map(t=>({value:t.id,label:t.nombre}))" :error="form.errors.tipo_equipamiento_id"/>
    <InputField v-if="!equipamiento && can('equipamientos.cambiar_estado')" id="estado" label="Estado inicial" v-model="form.estado" :options="options(['DISPONIBLE','EN_MANTENIMIENTO','BAJA'])" required :error="form.errors.estado"/>
    <InputField class="span-2" id="descripcion" label="Descripción" type="textarea" v-model="form.descripcion" maxlength="16000" :error="form.errors.descripcion"/>
   </div>
   <header class="form-section-heading"><h2>Ubicación base</h2><p>Dónde se guarda habitualmente el equipo. El préstamo registra su custodia.</p></header>
   <div class="form-grid">
    <InputField id="base" label="Espacio base (opcional)" v-model="form.espacio_base_id" :options="opciones.espacios.map(t=>({value:t.id,label:t.nombre}))" placeholder="Sin espacio base" :error="form.errors.espacio_base_id"/>
    <InputField id="referencia" label="Referencia de ubicación" v-model="form.referencia_ubicacion" maxlength="255" :error="form.errors.referencia_ubicacion"/>
   </div>
   <header class="form-section-heading"><h2>Datos técnicos</h2><p>Opcionales.</p></header>
   <div class="form-grid">
    <InputField id="marca" label="Marca" v-model="form.marca" maxlength="120" :error="form.errors.marca"/>
    <InputField id="modelo" label="Modelo" v-model="form.modelo" maxlength="120" :error="form.errors.modelo"/>
    <InputField id="serie" label="Número de serie" v-model="form.numero_serie" maxlength="160" :error="form.errors.numero_serie"/>
   </div>
   <div class="form-actions"><Link href="/equipamientos" class="btn btn-secondary">Cancelar</Link><button class="btn btn-primary" :disabled="form.processing || !opciones.tipos.length">{{form.processing ? 'Guardando…' : 'Guardar equipamiento'}}</button></div>
  </form>
 </AppLayout>
</template>
