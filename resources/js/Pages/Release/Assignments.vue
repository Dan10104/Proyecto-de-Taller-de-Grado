<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import PolicyAssignment from '../../Components/PolicyAssignment.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';
const props = defineProps({ espacios: Object, politicas: Array, filtros: Object });
const { can } = useAccess();
const search = useForm({ q: props.filtros.q || '' });
</script>
<template>
  <AppLayout title="Asignación de políticas" section="Liberación">
    <div class="page-heading"><div><h1>Asignación a espacios</h1><p>Solo se puede cambiar o retirar sin reservas aprobadas vigentes o futuras.</p></div><Link v-if="can('liberacion.politicas.ver')" href="/liberacion/politicas" class="btn btn-secondary">Ver políticas</Link><Link v-else href="/liberacion/politicas/crear" class="btn btn-secondary">Crear política</Link></div>
    <form class="panel space-filters" @submit.prevent="search.get('/liberacion/asignaciones')"><InputField id="q" label="Buscar espacio por código o nombre" v-model="search.q" maxlength="120" :error="search.errors.q"/><button class="btn btn-secondary">Buscar</button></form>
    <section class="panel release-assignments" aria-labelledby="release-assignments-title">
      <div class="panel-heading"><h2 id="release-assignments-title">Espacios académicos</h2><p v-if="espacios.total">{{ espacios.total }} {{ espacios.total === 1 ? 'espacio' : 'espacios' }}</p></div>
      <div v-if="!espacios.data.length" class="empty-state"><Icon name="building"/><h3>No se encontraron espacios</h3><p>Revise el código o el nombre buscado.</p></div>
      <div v-else class="release-assignment-list"><PolicyAssignment v-for="s in espacios.data" :key="`${s.recurso_id}-${s.politica_liberacion_id}`" :space="s" :policies="politicas"/></div>
      <Pagination :data="espacios"/>
    </section>
  </AppLayout>
</template>
