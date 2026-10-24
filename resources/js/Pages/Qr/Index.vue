<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import {useAccess} from '../../security';
const props=defineProps({tipo:String,q:String,objetivos:Object});
const filters=reactive({tipo:props.tipo,q:props.q}); const {can}=useAccess();
function search(){router.get('/qr',filters);}
</script>
<template>
 <AppLayout title="Códigos QR" section="QR">
  <div class="page-heading"><div><span class="eyebrow">IDENTIFICACIÓN DE RECURSOS</span><h1>Códigos QR</h1><p>Seleccione el objetivo para emitir, rotar o desactivar su código.</p></div></div>
  <form class="panel form-panel" @submit.prevent="search"><div class="form-grid">
   <div class="field"><label for="qr-tipo">Objetivo del código</label><select id="qr-tipo" v-model="filters.tipo"><option value="recurso">Recurso físico · espacio o equipo</option><option v-if="can('reservas.ver_administrativas')" value="reserva">Reserva específica</option><option v-if="can('prestamos.ver_administrativos')" value="prestamo">Préstamo específico</option></select></div>
   <InputField id="qr-search" label="Buscar por código o nombre del recurso" v-model="filters.q" maxlength="120"/>
  </div><div class="form-actions"><button class="btn btn-primary">Buscar objetivos</button></div></form>
  <section class="panel"><div class="panel-heading"><h2>{{tipo==='recurso'?'Recursos físicos':tipo==='reserva'?'Reservas':'Préstamos'}}</h2><p>Los códigos de reserva y préstamo son opcionales y se generan solo cuando se necesitan.</p></div>
   <div v-if="objetivos.data.length" class="qr-targets"><Link v-for="o in objetivos.data" :key="o.id" :href="`/qr/${tipo}/${o.id}`" class="qr-target"><strong>{{o.nombre}}</strong><span>{{o.codigo}}</span><span class="text-link">Gestionar QR →</span></Link></div>
   <div v-else class="empty-state"><h3>Sin objetivos para mostrar</h3><p>Revise los filtros y los permisos de consulta.</p></div><Pagination :data="objetivos"/>
  </section>
 </AppLayout>
</template>
