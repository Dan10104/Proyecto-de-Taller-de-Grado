<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import OperationalState from '../../Components/OperationalState.vue';
import { useAccess } from '../../security';
import { options, clean } from '../../equipment';
import { focusError } from '../../spaces';
const props=defineProps({equipamientos:Object,filtros:Object,opciones:Object,disponibilidad:Boolean,periodoCompleto:Boolean});
const {can}=useAccess();
const filters=useForm(Object.fromEntries(['q','tipo_equipamiento_id','estado','espacio_base_id','piso_id','marca','modelo','inicio_previsto','fin_previsto'].map(k=>[k,props.filtros[k]||''])));
const path=props.disponibilidad ? '/equipamientos/disponibilidad' : '/equipamientos';
function search(){filters.transform(clean).get(path,{preserveState:true,preserveScroll:true,onError:focusError});}
function requestUrl(e){return '/prestamos/crear?'+new URLSearchParams({equipamiento_id:e.id,inicio_previsto:props.filtros.inicio_previsto,fin_previsto:props.filtros.fin_previsto});}
</script>
<template>
 <AppLayout :title="disponibilidad ? 'Disponibilidad de equipos' : 'Equipamiento tecnológico'">
  <div class="page-heading"><div><h1>{{ disponibilidad ? 'Encuentre un equipo disponible' : 'Equipamiento tecnológico' }}</h1><p>{{ disponibilidad ? 'Consulte un periodo antes de preparar su solicitud de préstamo.' : 'Consulte el inventario, su ubicación base y su estado operativo.' }}</p></div>
   <div class="button-group"><Link v-if="can('equipamientos.gestionar')" href="/equipamientos/tipos" class="btn btn-secondary">Tipos de equipo</Link><Link v-if="can('equipamientos.gestionar')" href="/equipamientos/crear" class="btn btn-primary">Registrar equipo</Link></div>
  </div>
  <nav class="catalog-tabs" aria-label="Consulta de equipamiento"><Link href="/equipamientos" :class="{active:!disponibilidad}">Inventario</Link><Link href="/equipamientos/disponibilidad" :class="{active:disponibilidad}">Disponibilidad por periodo</Link></nav>
  <form class="panel space-filters" @submit.prevent="search">
   <FormErrors :errors="filters.errors"/>
   <div class="equipment-filter-grid">
    <InputField id="q" label="Buscar por código o nombre" v-model="filters.q" maxlength="160"/>
    <InputField id="tipo" label="Tipo de equipo" v-model="filters.tipo_equipamiento_id" :options="opciones.tipos.map(t=>({value:t.id,label:t.nombre}))" placeholder="Todos los tipos"/>
    <InputField id="piso" label="Piso de ubicación base" v-model="filters.piso_id" :options="opciones.pisos.map(t=>({value:t.id,label:t.nombre}))" placeholder="Todos los pisos"/>
    <InputField id="base" label="Espacio base" v-model="filters.espacio_base_id" :options="opciones.espacios.map(t=>({value:t.id,label:t.nombre}))" placeholder="Todos los espacios"/>
    <InputField v-if="!disponibilidad" id="estado" label="Estado operativo" v-model="filters.estado" :options="options(['DISPONIBLE','PRESTADO','EN_MANTENIMIENTO','BAJA'])" placeholder="Todos los estados"/>
    <InputField id="marca" label="Marca" v-model="filters.marca" maxlength="120"/>
    <InputField id="modelo" label="Modelo" v-model="filters.modelo" maxlength="120"/>
    <InputField id="inicio" label="Inicio previsto" type="datetime-local" v-model="filters.inicio_previsto" :required="disponibilidad || !!filters.fin_previsto" min="1000-01-01T00:00" max="9999-12-31T23:59" :error="filters.errors.inicio_previsto"/>
    <InputField id="fin" label="Fin previsto" type="datetime-local" v-model="filters.fin_previsto" :required="disponibilidad || !!filters.inicio_previsto" min="1000-01-01T00:00" max="9999-12-31T23:59" :error="filters.errors.fin_previsto"/>
   </div>
   <div class="filter-actions"><p class="hint">Hora de Bolivia. La disponibilidad se comprueba nuevamente al solicitar y aprobar.</p><div class="button-group"><button type="button" class="btn btn-secondary" @click="router.get(path)">Limpiar</button><button class="btn btn-primary" :disabled="filters.processing">Consultar</button></div></div>
  </form>
  <section v-if="equipamientos" class="panel">
   <div class="panel-heading"><h2>{{ equipamientos.total }} {{ periodoCompleto ? (equipamientos.total===1 ? 'equipo disponible para el periodo' : 'equipos disponibles para el periodo') : (equipamientos.total===1 ? 'equipo en inventario' : 'equipos en inventario') }}</h2><p v-if="!periodoCompleto">El estado operativo por sí solo no confirma un horario disponible.</p></div>
   <div v-if="equipamientos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Inventario de equipos desplazable">
    <table class="equipment-table"><caption class="sr-only">Equipamiento tecnológico</caption><thead><tr><th>Equipo</th><th>Tipo / modelo</th><th>Ubicación base</th><th>Estado</th><th>Acciones</th></tr></thead>
     <tbody><tr v-for="e in equipamientos.data" :key="e.id"><td><div class="equipment-identity"><Icon name="monitor"/><div><Link class="text-link" :href="'/equipamientos/'+e.id">{{ e.nombre }}</Link><small class="cell-subline">{{ e.codigo }}</small></div></div></td><td>{{ e.tipo }}<small class="cell-subline">{{ [e.marca,e.modelo].filter(Boolean).join(' · ') || 'Sin marca o modelo' }}</small></td><td>{{ e.ubicacion }}</td><td><OperationalState :value="e.estado"/></td><td><div class="button-group"><Link :href="'/equipamientos/'+e.id" class="text-link">Ver detalle<span class="sr-only"> de {{ e.nombre }}</span></Link><Link v-if="periodoCompleto && can('prestamos.solicitar')" :href="requestUrl(e)" class="btn btn-secondary">Solicitar préstamo<span class="sr-only"> de {{ e.nombre }}</span></Link></div></td></tr></tbody>
    </table>
   </div><div v-else class="empty-state"><h3>No hay equipos con estos criterios</h3><p>Revise los filtros o consulte otro periodo.</p></div>
  </section>
  <section v-else class="panel empty-state"><h2>Seleccione el periodo de uso</h2><p>Los resultados aparecerán cuando indique inicio y fin.</p></section>
  <Pagination v-if="equipamientos" :data="equipamientos"/>
 </AppLayout>
</template>
