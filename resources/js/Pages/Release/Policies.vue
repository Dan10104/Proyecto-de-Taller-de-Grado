<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import { useAccess } from '../../security';
defineProps({ politicas: Object, filtros: Object });
const { can } = useAccess();
const search = useForm({ q: new URLSearchParams(window.location.search).get('q') || '' });
</script>
<template>
  <AppLayout title="Políticas de liberación">
    <div class="page-heading"><div><h1>Políticas de liberación</h1><p>Versiones que determinan cuándo conservar, alertar o liberar una reserva.</p></div><Link v-if="can('liberacion.politicas.gestionar')" href="/liberacion/politicas/crear" class="btn btn-primary">Crear política</Link></div>
    <nav class="catalog-tabs" aria-label="Gestión de liberación"><Link href="/liberacion/politicas" class="active" aria-current="page">Versiones</Link><Link v-if="can('liberacion.politicas.gestionar')" href="/liberacion/asignaciones">Asignación a espacios</Link><Link v-if="can('liberacion.evaluaciones.ver_administrativas')" href="/liberacion/evaluaciones">Evaluaciones</Link></nav>
    <form class="panel space-filters" @submit.prevent="search.get('/liberacion/politicas')"><InputField id="q" label="Buscar por nombre" v-model="search.q" maxlength="120" :error="search.errors.q"/><button class="btn btn-secondary" :disabled="search.processing">Buscar</button></form>
    <div class="panel table-panel"><div class="table-scroll" tabindex="0" role="region" aria-label="Versiones de políticas, tabla desplazable"><table><caption class="sr-only">Versiones de políticas de liberación</caption><thead><tr><th>Política / versión</th><th>Tolerancia</th><th>Fuentes obligatorias</th><th>Antigüedad IoT</th><th>Modo</th><th>Estado</th><th>Espacios</th></tr></thead><tbody><tr v-for="p in politicas.data" :key="p.id"><td><Link :href="`/liberacion/politicas/${p.id}`">{{ p.nombre }} · v{{ p.version }}</Link></td><td>{{ p.tolerancia_min }} min</td><td>{{ [p.requiere_qr ? 'QR' : '', p.usa_iot ? 'IoT' : ''].filter(Boolean).join(' + ') }}</td><td>{{ p.usa_iot ? `${p.max_antiguedad_lectura_min} min` : 'No aplica' }}</td><td>{{ p.modo === 'ALERTA' ? 'Alerta' : 'Automática' }}</td><td>{{ p.activa ? 'Activa' : 'Inactiva' }}</td><td>{{ p.espacios_count }}</td></tr><tr v-if="!politicas.data.length"><td colspan="7" class="empty-cell">No hay políticas para esta búsqueda.</td></tr></tbody></table></div><Pagination :data="politicas"/></div>
  </AppLayout>
</template>
