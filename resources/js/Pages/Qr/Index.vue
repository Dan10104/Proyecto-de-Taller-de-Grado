<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import {useAccess} from '../../security';
const props=defineProps({tipo:String,q:String,objetivos:Object});
const filters=reactive({tipo:props.tipo,q:props.q}); const {can}=useAccess();
function search(){router.get('/qr',filters);}
// Solo presentación: nombre del tipo de objetivo listado.
const kinds={recurso:'Recurso físico',reserva:'Reserva',prestamo:'Préstamo'};
</script>
<template>
 <AppLayout title="Códigos QR" section="QR">
  <div class="page-heading"><div><h1>Códigos QR</h1><p>Elija un objetivo para emitir, rotar o desactivar su código.</p></div></div>
  <form class="panel space-filters" @submit.prevent="search">
   <div class="field"><label for="qr-tipo">Objetivo del código</label><select id="qr-tipo" v-model="filters.tipo"><option value="recurso">Recurso físico · espacio o equipo</option><option v-if="can('reservas.ver_administrativas')" value="reserva">Reserva específica</option><option v-if="can('prestamos.ver_administrativos')" value="prestamo">Préstamo específico</option></select></div>
   <InputField id="qr-search" label="Buscar por código o nombre" v-model="filters.q" maxlength="120"/>
   <button class="btn btn-primary">Buscar</button>
  </form>
  <section class="panel">
   <div class="panel-heading"><h2>{{tipo==='recurso'?'Recursos físicos':tipo==='reserva'?'Reservas':'Préstamos'}}</h2><p>Los códigos de reserva y préstamo son opcionales; se generan solo cuando se necesitan.</p></div>
   <div v-if="objetivos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Objetivos de código QR, tabla desplazable">
    <table><caption class="sr-only">Objetivos disponibles para gestionar su código QR</caption>
     <thead><tr><th>Objetivo</th><th>Tipo</th><th class="align-right">Acción</th></tr></thead>
     <tbody><tr v-for="o in objetivos.data" :key="o.id"><td><strong>{{o.nombre}}</strong><small class="cell-subline">{{o.codigo}}</small></td><td>{{kinds[tipo]||tipo}}</td><td><div class="row-actions"><Link :href="`/qr/${tipo}/${o.id}`">Gestionar</Link></div></td></tr></tbody>
    </table>
   </div>
   <div v-else class="empty-state"><Icon name="qr"/><h3>No hay objetivos para mostrar</h3><p>Revise la búsqueda o el tipo de objetivo seleccionado.</p></div>
   <Pagination :data="objetivos"/>
  </section>
 </AppLayout>
</template>
