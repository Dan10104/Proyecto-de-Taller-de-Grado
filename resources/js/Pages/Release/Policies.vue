<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';
defineProps({ politicas: Object, filtros: Object });
const { can } = useAccess();
const search = useForm({ q: new URLSearchParams(window.location.search).get('q') || '' });
</script>
<template>
  <AppLayout title="Políticas de liberación">
    <div class="page-heading"><div><h1>Políticas de liberación</h1><p>Cuándo se conserva, se alerta o se libera una reserva sin uso.</p></div><Link v-if="can('liberacion.politicas.gestionar')" href="/liberacion/politicas/crear" class="btn btn-primary">Crear política</Link></div>
    <nav class="catalog-tabs" aria-label="Gestión de liberación"><Link href="/liberacion/politicas" class="active" aria-current="page">Versiones</Link><Link v-if="can('liberacion.politicas.gestionar')" href="/liberacion/asignaciones">Asignación a espacios</Link><Link v-if="can('liberacion.evaluaciones.ver_administrativas')" href="/liberacion/evaluaciones">Evaluaciones</Link></nav>
    <form class="panel space-filters" @submit.prevent="search.get('/liberacion/politicas')"><InputField id="q" label="Buscar por nombre" v-model="search.q" maxlength="120" :error="search.errors.q"/><button class="btn btn-secondary" :disabled="search.processing">Buscar</button></form>
    <section class="panel">
      <div v-if="politicas.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Versiones de políticas, tabla desplazable">
        <table><caption class="sr-only">Versiones de políticas de liberación</caption>
          <thead><tr><th>Política</th><th>Versión</th><th>Modo</th><th>Reglas</th><th>Espacios</th><th>Estado</th><th class="align-right">Acción</th></tr></thead>
          <tbody><tr v-for="p in politicas.data" :key="p.id">
            <td><strong>{{ p.nombre }}</strong></td>
            <td>v{{ p.version }}</td>
            <td>{{ p.modo === 'ALERTA' ? 'Alerta' : 'Automática' }}</td>
            <td><div class="feature-chips"><span>Tolerancia {{ p.tolerancia_min }} min</span><span v-if="p.requiere_qr">QR obligatorio</span><span v-if="p.usa_iot">IoT · lectura ≤ {{ p.max_antiguedad_lectura_min }} min</span></div></td>
            <td>{{ p.espacios_count }}</td>
            <td><StatusBadge :value="p.activa ? 'ACTIVO' : 'INACTIVO'" :label="p.activa ? 'Activa' : 'Inactiva'"/></td>
            <td><div class="row-actions"><Link :href="`/liberacion/politicas/${p.id}`">Ver</Link></div></td>
          </tr></tbody>
        </table>
      </div>
      <div v-else class="empty-state"><Icon name="shield"/><h3>No hay políticas para esta búsqueda</h3><p>Revise el nombre buscado.</p></div>
      <Pagination :data="politicas"/>
    </section>
  </AppLayout>
</template>
