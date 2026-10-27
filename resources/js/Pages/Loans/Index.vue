<script setup>
import {Link,router,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import OperationalState from '../../Components/OperationalState.vue';
import {useAccess} from '../../security';
import {options,person,clean} from '../../equipment';
import {reservationDate} from '../../reservations';
import {focusError} from '../../spaces';
const props=defineProps({prestamos:Object,alcance:String,filtros:Object,opciones:Object});
const {can}=useAccess();
const filters=useForm({q:props.filtros.q||'',estado:props.filtros.estado||'',fecha:props.filtros.fecha||'',equipamiento_id:props.filtros.equipamiento_id||'',vencidos:!!Number(props.filtros.vencidos),revision:!!Number(props.filtros.revision),...(props.alcance==='administrativos'?{solicitante_id:props.filtros.solicitante_id||''}:{})});
function search(){filters.transform(d=>clean({...d,alcance:props.alcance,vencidos:d.vencidos?1:'',revision:d.revision?1:''})).get('/prestamos',{preserveState:true,preserveScroll:true,onError:focusError});}
</script>
<template>
 <AppLayout title="Préstamos de equipamiento"><div class="page-heading"><div><h1>Préstamos de equipamiento</h1><p>{{alcance==='propios'?'Consulte sus solicitudes y su seguimiento.':'Revise solicitudes, registre entregas y complete devoluciones.'}}</p></div><Link v-if="can('prestamos.solicitar') && can('equipamientos.ver')" href="/equipamientos/disponibilidad" class="btn btn-primary">Solicitar préstamo</Link></div>
  <nav class="catalog-tabs" aria-label="Alcance de préstamos"><Link v-if="can('prestamos.ver_propios')" href="/prestamos?alcance=propios" :class="{active:alcance==='propios'}">Mis préstamos</Link><Link v-if="can('prestamos.ver_administrativos')" href="/prestamos?alcance=administrativos" :class="{active:alcance==='administrativos'}">Préstamos administrativos</Link><Link v-if="can('prestamos.ver_administrativos')" href="/prestamos?alcance=administrativos&estado=PENDIENTE">Solicitudes pendientes</Link></nav>
  <form class="panel space-filters" @submit.prevent="search"><FormErrors :errors="filters.errors"/><div class="equipment-filter-grid">
   <InputField id="q" label="Buscar código o equipo" v-model="filters.q" maxlength="160"/>
   <InputField id="estado" label="Estado" v-model="filters.estado" :options="options(['PENDIENTE','APROBADO','RECHAZADO','ENTREGADO','DEVUELTO'])" placeholder="Todos los estados"/>
   <InputField id="fecha" label="Fecha de uso" type="date" v-model="filters.fecha" min="1000-01-01" max="9999-12-31"/>
   <InputField id="equipo" label="Equipo" v-model="filters.equipamiento_id" :options="opciones.equipamientos.map(e=>({value:e.id,label:e.nombre}))" placeholder="Todos los equipos"/>
   <InputField v-if="alcance==='administrativos'" id="solicitante" label="Solicitante" v-model="filters.solicitante_id" :options="opciones.solicitantes.map(u=>({value:u.id,label:person(u)}))" placeholder="Todos los solicitantes"/>
  </div><div class="operational-links button-group"><label class="check-option"><input type="checkbox" v-model="filters.vencidos">Solo vencidos</label><label class="check-option"><input type="checkbox" v-model="filters.revision">Devolución pendiente de revisión</label></div><div class="filter-actions"><p class="hint">Vencido: entregado cuyo fin previsto ya pasó. Una revisión pendiente continúa pendiente de cierre.</p><div class="button-group"><button class="btn btn-secondary" type="button" @click="router.get('/prestamos',{alcance})">Limpiar</button><button class="btn btn-primary" :disabled="filters.processing">Aplicar filtros</button></div></div></form>
  <section class="panel"><div class="panel-heading"><h2>{{prestamos.total}} préstamos</h2></div><div v-if="prestamos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Préstamos de equipamiento desplazables">
   <table class="equipment-table"><caption class="sr-only">Listado de préstamos {{alcance}}</caption><thead><tr><th>Equipo / código</th><th v-if="alcance==='administrativos'">Solicitante</th><th>Periodo previsto</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><tr v-for="p in prestamos.data" :key="p.id"><td><strong>{{p.equipamiento.nombre}}</strong><small class="cell-subline reservation-code">{{p.codigo}}</small></td><td v-if="alcance==='administrativos'">{{person(p.solicitante)}}</td><td>{{reservationDate(p.inicio_previsto)}}<small class="cell-subline">{{reservationDate(p.fin_previsto)}}</small></td><td><span class="reservation-purpose">{{p.motivo}}</span></td><td><div class="operational-badges"><OperationalState :value="p.estado"/><span v-if="p.vencido" class="status-badge operational-baja">Vencido</span><OperationalState v-if="p.revision_pendiente" value="PENDIENTE_REVISION"/></div></td><td><Link :href="'/prestamos/'+p.id" class="text-link">Ver detalle<span class="sr-only"> de {{p.codigo}}</span></Link></td></tr></tbody></table>
  </div><div v-else class="empty-state"><h3>No hay préstamos con estos criterios</h3><p>Revise los filtros o consulte el catálogo de equipos disponibles.</p></div></section><Pagination :data="prestamos"/>
 </AppLayout>
</template>
