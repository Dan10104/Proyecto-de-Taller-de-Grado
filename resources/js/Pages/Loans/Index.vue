<script setup>
import {ref} from 'vue';
import {Link,router,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import OperationalState from '../../Components/OperationalState.vue';
import Icon from '../../Components/Icon.vue';
import {useAccess} from '../../security';
import {options,person,clean} from '../../equipment';
import {reservationDate} from '../../reservations';
import {focusError} from '../../spaces';
const props=defineProps({prestamos:Object,alcance:String,filtros:Object,opciones:Object});
const {can}=useAccess();
const filters=useForm({q:props.filtros.q||'',estado:props.filtros.estado||'',fecha:props.filtros.fecha||'',equipamiento_id:props.filtros.equipamiento_id||'',vencidos:!!Number(props.filtros.vencidos),revision:!!Number(props.filtros.revision),...(props.alcance==='administrativos'?{solicitante_id:props.filtros.solicitante_id||''}:{})});
function search(){filters.transform(d=>clean({...d,alcance:props.alcance,vencidos:d.vencidos?1:'',revision:d.revision?1:''})).get('/prestamos',{preserveState:true,preserveScroll:true,onError:focusError});}

// Solo presentación: filtros secundarios plegados (abiertos si ya tienen valor).
const moreOpen=ref(!!(props.filtros.equipamiento_id||props.filtros.solicitante_id));
const pendingTab=props.alcance==='administrativos' && props.filtros.estado==='PENDIENTE';
const filtered=['q','estado','fecha','equipamiento_id','solicitante_id','vencidos','revision'].some(k=>!!props.filtros[k] && props.filtros[k]!=='0');
</script>
<template>
 <AppLayout title="Préstamos de equipamiento">
  <div class="page-heading">
   <div><h1>Préstamos de equipamiento</h1><p>{{alcance==='propios'?'Sus solicitudes y su seguimiento.':'Solicitudes, entregas y devoluciones.'}}</p></div>
   <Link v-if="can('prestamos.solicitar') && can('equipamientos.ver')" href="/equipamientos/disponibilidad" class="btn btn-primary"><Icon name="plus"/>Solicitar préstamo</Link>
  </div>
  <nav class="catalog-tabs" aria-label="Alcance de préstamos"><Link v-if="can('prestamos.ver_propios')" href="/prestamos?alcance=propios" :class="{active:alcance==='propios'}">Mis préstamos</Link><Link v-if="can('prestamos.ver_administrativos')" href="/prestamos?alcance=administrativos" :class="{active:alcance==='administrativos' && !pendingTab}">Préstamos administrativos</Link><Link v-if="can('prestamos.ver_administrativos')" href="/prestamos?alcance=administrativos&estado=PENDIENTE" :class="{active:pendingTab}">Solicitudes pendientes</Link></nav>

  <form class="panel space-filters" @submit.prevent="search"><FormErrors :errors="filters.errors"/>
   <div class="equipment-filter-grid">
    <InputField id="q" label="Buscar código o equipo" v-model="filters.q" maxlength="160"/>
    <InputField id="estado" label="Estado" v-model="filters.estado" :options="options(['PENDIENTE','APROBADO','RECHAZADO','ENTREGADO','DEVUELTO'])" placeholder="Todos los estados"/>
    <InputField id="fecha" label="Fecha de uso" type="date" v-model="filters.fecha" min="1000-01-01" max="9999-12-31"/>
   </div>
   <div class="equipment-checks"><label class="check-option"><input type="checkbox" v-model="filters.vencidos">Solo vencidos</label><label class="check-option"><input type="checkbox" v-model="filters.revision">Devolución pendiente de revisión</label></div>
   <details class="disclosure equipment-more" :open="moreOpen" @toggle="moreOpen=$event.target.open">
    <summary>Más filtros</summary>
    <div class="equipment-filter-grid">
     <InputField id="equipo" label="Equipo" v-model="filters.equipamiento_id" :options="opciones.equipamientos.map(e=>({value:e.id,label:e.nombre}))" placeholder="Todos los equipos"/>
     <InputField v-if="alcance==='administrativos'" id="solicitante" label="Solicitante" v-model="filters.solicitante_id" :options="opciones.solicitantes.map(u=>({value:u.id,label:person(u)}))" placeholder="Todos los solicitantes"/>
    </div>
   </details>
   <div class="filter-actions"><p class="hint">Vencido: entregado y con el fin previsto cumplido. Una revisión pendiente sigue abierta hasta su cierre.</p><div class="button-group"><button class="text-button" type="button" @click="router.get('/prestamos',{alcance})">Limpiar filtros</button><button class="btn btn-secondary" :disabled="filters.processing">Aplicar filtros</button></div></div>
  </form>

  <section class="panel">
   <div class="panel-heading"><h2>{{prestamos.total}} {{prestamos.total===1?'préstamo':'préstamos'}}</h2></div>
   <div v-if="prestamos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Préstamos de equipamiento desplazables">
    <table class="equipment-table"><caption class="sr-only">Listado de préstamos {{alcance}}</caption>
     <thead><tr><th>Equipo</th><th v-if="alcance==='administrativos'">Solicitante</th><th>Periodo previsto</th><th>Estado</th><th class="align-right">Acción</th></tr></thead>
     <tbody><tr v-for="p in prestamos.data" :key="p.id">
      <td><Link class="equipment-name" :href="'/prestamos/'+p.id">{{p.equipamiento.nombre}}</Link><small class="cell-subline reservation-code">{{p.codigo}}</small></td>
      <td v-if="alcance==='administrativos'">{{person(p.solicitante)}}</td>
      <td class="date-cell">{{reservationDate(p.inicio_previsto)}}<small class="cell-subline">hasta {{reservationDate(p.fin_previsto)}}</small></td>
      <td><div class="operational-badges"><OperationalState :value="p.estado"/><span v-if="p.vencido" class="badge badge-negative"><svg class="badge-mark" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3.4v3.2M6 8.6h.01M10.5 6a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>Vencido</span><OperationalState v-if="p.revision_pendiente" value="PENDIENTE_REVISION"/></div></td>
      <td><div class="row-actions"><Link :href="'/prestamos/'+p.id" class="text-link">{{p.acciones.resolver?'Resolver':'Ver'}}<span class="sr-only"> {{p.codigo}}</span></Link></div></td>
     </tr></tbody>
    </table>
   </div>
   <div v-else-if="!filtered" class="empty-state"><Icon name="exchange"/><h3>No hay préstamos registrados.</h3><Link v-if="can('prestamos.solicitar') && can('equipamientos.ver')" href="/equipamientos/disponibilidad" class="btn btn-secondary">Solicitar préstamo</Link></div>
   <div v-else class="empty-state"><Icon name="search"/><h3>No hay préstamos con estos criterios</h3><p>Revise los filtros o consulte los equipos disponibles.</p></div>
  </section>
  <Pagination :data="prestamos"/>
 </AppLayout>
</template>
