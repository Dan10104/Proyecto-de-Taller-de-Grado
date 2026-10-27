<script setup>
import {Link,router,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import OperationalState from '../../Components/OperationalState.vue';
import {useAccess} from '../../security';
import {options,clean,labels} from '../../equipment';
import {reservationDate} from '../../reservations';
import {focusError} from '../../spaces';
const props=defineProps({incidencias:Object,filtros:Object});
const {can}=useAccess();
const form=useForm({q:props.filtros.q||'',tipo:props.filtros.tipo||'',estado:props.filtros.estado||'',recurso_id:props.filtros.recurso_id||''});
function search(){form.transform(clean).get('/incidencias',{preserveState:true,preserveScroll:true,onError:focusError});}
</script>
<template>
 <AppLayout title="Incidencias y mantenimiento"><div class="page-heading"><div><h1>Incidencias y mantenimiento</h1><p>Registre restricciones, revise los compromisos afectados y documente su solución.</p></div><Link v-if="can('incidencias.gestionar')" href="/incidencias/crear" class="btn btn-primary">Registrar incidencia o mantenimiento</Link></div>
  <form class="panel space-filters" @submit.prevent="search"><FormErrors :errors="form.errors"/><div class="equipment-filter-grid"><InputField id="q" label="Código o nombre del recurso" v-model="form.q" maxlength="160"/><InputField id="tipo" label="Tipo de registro" v-model="form.tipo" :options="options(['INCIDENCIA','MANTENIMIENTO'])" placeholder="Todos los tipos"/><InputField id="estado" label="Estado" v-model="form.estado" :options="options(['ABIERTA','CERRADA'])" placeholder="Todos los estados"/></div><p v-if="form.recurso_id" class="hint">Consulta limitada al recurso seleccionado desde su detalle.</p><div class="form-actions"><button type="button" class="btn btn-secondary" @click="router.get('/incidencias')">Limpiar</button><button class="btn btn-primary" :disabled="form.processing">Aplicar filtros</button></div></form>
  <section class="panel"><div class="panel-heading"><h2>{{incidencias.total}} registros</h2><p>Los registros abiertos restringen el recurso hasta su cierre y revisión operativa.</p></div><div v-if="incidencias.data.length" class="table-scroll" role="region" aria-label="Incidencias y mantenimiento desplazables" tabindex="0"><table class="equipment-table"><caption class="sr-only">Incidencias y mantenimiento</caption><thead><tr><th>Recurso</th><th>Tipo</th><th>Descripción</th><th>Periodo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><tr v-for="i in incidencias.data" :key="i.id"><td><strong>{{i.recurso.nombre}}</strong><small class="cell-subline">{{i.recurso.codigo}}</small></td><td>{{labels[i.tipo]}}</td><td><span class="reservation-purpose">{{i.descripcion}}</span></td><td>{{reservationDate(i.inicio)}}<small class="cell-subline">{{i.fin_previsto?reservationDate(i.fin_previsto):'Sin fin previsto'}}</small></td><td><OperationalState :value="i.estado"/></td><td><Link :href="'/incidencias/'+i.id" class="text-link">Ver detalle<span class="sr-only"> de {{i.recurso.nombre}}</span></Link></td></tr></tbody></table></div><div v-else class="empty-state"><h3>No hay registros con estos criterios</h3><p>Revise los filtros o registre una condición observada.</p></div></section><Pagination :data="incidencias"/>
 </AppLayout>
</template>
