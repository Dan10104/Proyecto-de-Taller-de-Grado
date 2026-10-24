<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import PolicyAssignment from '../../Components/PolicyAssignment.vue';
import { useAccess } from '../../security';
const props = defineProps({ espacios: Object, politicas: Array, filtros: Object });
const { can } = useAccess();
const search = useForm({ q: props.filtros.q || '' });
</script>
<template><AppLayout title="Asignación de políticas" section="Liberación"><div class="page-heading"><div><h1>Asignación a espacios</h1><p>Solo puede cambiar o retirar una política cuando no hay reservas aprobadas vigentes o futuras.</p></div><Link v-if="can('liberacion.politicas.ver')" href="/liberacion/politicas" class="btn btn-secondary">Ver políticas</Link></div><form class="panel space-filters" @submit.prevent="search.get('/liberacion/asignaciones')"><InputField id="q" label="Buscar espacio por código o nombre" v-model="search.q" maxlength="120" :error="search.errors.q"/><button class="btn btn-secondary">Buscar</button></form><p v-if="!espacios.data.length" class="panel form-panel">No se encontraron espacios.</p><PolicyAssignment v-for="s in espacios.data" :key="`${s.recurso_id}-${s.politica_liberacion_id}`" :space="s" :policies="politicas"/><Pagination :data="espacios"/></AppLayout></template>
