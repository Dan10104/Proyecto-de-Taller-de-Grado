<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SpaceFilters from '../../Components/SpaceFilters.vue';
import SpaceTable from '../../Components/SpaceTable.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';
defineProps({ espacios: Object, filtros: Object, opciones: Object });
const { can } = useAccess();
</script>
<template>
  <AppLayout title="Espacios académicos">
    <div class="page-heading">
      <div>
        <h1>Espacios académicos</h1>
        <p>Ubicación, capacidad y estado operativo de cada ambiente.</p>
      </div>
      <div v-if="can('espacios.gestionar')" class="button-group">
        <Link href="/espacios/catalogos/pisos" class="btn btn-secondary">Catálogos auxiliares</Link>
        <Link href="/espacios/crear" class="btn btn-primary">
          <Icon name="plus"/>
          Registrar espacio
        </Link>
      </div>
    </div>
    <SpaceFilters :filtros="filtros" :opciones="opciones"/>
    <section class="panel space-list-panel">
      <div class="panel-heading">
        <h2>{{ espacios.total }} {{ espacios.total === 1 ? 'espacio' : 'espacios' }}</h2>
        <p>Los bloqueos temporales se reflejan al consultar la disponibilidad de un periodo.</p>
      </div>
      <SpaceTable v-if="espacios.data.length" :spaces="espacios.data"/>
      <div v-else class="empty-state">
        <Icon name="building"/>
        <h3>No hay espacios que coincidan</h3>
        <p>Ajuste los filtros{{ can('espacios.gestionar') ? ' o registre un espacio.' : '.' }}</p>
        <Link v-if="can('espacios.gestionar')" href="/espacios/crear" class="btn btn-secondary">Registrar espacio</Link>
      </div>
      <Pagination :data="espacios"/>
    </section>
  </AppLayout>
</template>
