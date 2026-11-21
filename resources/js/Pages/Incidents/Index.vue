<script setup>
import {Link,router,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import OperationalState from '../../Components/OperationalState.vue';
import Icon from '../../Components/Icon.vue';
import {useAccess} from '../../security';
import {options,clean,labels} from '../../equipment';
import {reservationDate} from '../../reservations';
import {focusError} from '../../spaces';
const props=defineProps({incidencias:Object,filtros:Object});
const {can}=useAccess();
const form=useForm({q:props.filtros.q||'',tipo:props.filtros.tipo||'',estado:props.filtros.estado||'',recurso_id:props.filtros.recurso_id||''});
function search(){form.transform(clean).get('/incidencias',{preserveState:true,preserveScroll:true,onError:focusError});}
// Solo presentación: distingue «sin registros» de «sin resultados para los filtros».
const filtered=computed(()=>['q','tipo','estado','recurso_id'].some(k=>!!props.filtros[k]));
</script>
<template>
 <AppLayout title="Incidencias y mantenimiento">
  <div class="page-heading">
   <div><h1>Incidencias y mantenimiento</h1><p>Restricciones de espacios y equipos, y su cierre.</p></div>
   <Link v-if="can('incidencias.gestionar')" href="/incidencias/crear" class="btn btn-primary"><Icon name="plus"/>Registrar incidencia o mantenimiento</Link>
  </div>
  <form class="panel space-filters" @submit.prevent="search"><FormErrors :errors="form.errors"/>
   <div class="equipment-filter-grid"><InputField id="q" label="Código o nombre del recurso" v-model="form.q" maxlength="160"/><InputField id="tipo" label="Tipo de registro" v-model="form.tipo" :options="options(['INCIDENCIA','MANTENIMIENTO'])" placeholder="Todos los tipos"/><InputField id="estado" label="Estado" v-model="form.estado" :options="options(['ABIERTA','CERRADA'])" placeholder="Todos los estados"/></div>
   <div class="filter-actions"><p v-if="form.recurso_id" class="hint">Consulta limitada al recurso seleccionado desde su detalle.</p><div class="button-group"><button type="button" class="text-button" @click="router.get('/incidencias')">Limpiar filtros</button><button class="btn btn-secondary" :disabled="form.processing">Aplicar filtros</button></div></div>
  </form>
  <section class="panel">
   <div class="panel-heading"><h2>{{incidencias.total}} {{incidencias.total===1?'registro':'registros'}}</h2><p>Un registro abierto restringe el recurso hasta su cierre.</p></div>
   <div v-if="incidencias.data.length" class="table-scroll" role="region" aria-label="Incidencias y mantenimiento desplazables" tabindex="0">
    <table class="equipment-table"><caption class="sr-only">Incidencias y mantenimiento</caption>
     <thead><tr><th>Recurso</th><th>Tipo</th><th>Inicio</th><th>Estado</th><th class="align-right">Acción</th></tr></thead>
     <tbody><tr v-for="i in incidencias.data" :key="i.id">
      <td><Link class="equipment-name" :href="'/incidencias/'+i.id">{{i.recurso.nombre}}</Link><small class="cell-subline reservation-code">{{i.recurso.codigo}}</small></td>
      <td>{{labels[i.tipo]}}<small class="cell-subline equipment-clamp">{{i.descripcion}}</small></td>
      <td class="date-cell">{{reservationDate(i.inicio)}}<small class="cell-subline">{{i.fin_previsto?'Fin previsto: '+reservationDate(i.fin_previsto):'Sin fin previsto'}}</small></td>
      <td><OperationalState :value="i.estado"/></td>
      <td><div class="row-actions"><Link :href="'/incidencias/'+i.id" class="text-link">{{i.estado==='ABIERTA' && can('incidencias.cerrar')?'Resolver':'Ver'}}<span class="sr-only"> {{i.recurso.nombre}}</span></Link></div></td>
     </tr></tbody>
    </table>
   </div>
   <div v-else-if="!filtered" class="empty-state"><Icon name="tool"/><h3>No hay incidencias registradas.</h3><Link v-if="can('incidencias.gestionar')" href="/incidencias/crear" class="btn btn-secondary">Registrar incidencia o mantenimiento</Link></div>
   <div v-else class="empty-state"><Icon name="search"/><h3>No hay registros con estos criterios</h3><p>Revise los filtros.</p></div>
  </section>
  <Pagination :data="incidencias"/>
 </AppLayout>
</template>
