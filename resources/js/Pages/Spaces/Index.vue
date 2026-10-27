<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SpaceFilters from '../../Components/SpaceFilters.vue';
import SpaceState from '../../Components/SpaceState.vue';
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
        <p>Ubicación, características y estado operativo de cada ambiente.</p>
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
    <section class="panel">
      <div class="panel-heading">
        <h2>Espacios registrados</h2>
        <p>El estado operativo se complementa con los bloqueos del periodo consultado.</p>
      </div>
      <div v-if="espacios.data.length" class="table-scroll" role="region" tabindex="0" aria-label="Listado de espacios desplazable">
        <table>
          <caption class="sr-only">Espacios académicos registrados</caption>
          <thead>
            <tr>
              <th>Espacio</th>
              <th>Ubicación</th>
              <th>Tipo</th>
              <th>Capacidad</th>
              <th>Estado</th>
              <th>Características</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="space in espacios.data" :key="space.recurso_id">
              <td>
                <Link :href="`/espacios/${space.recurso_id}`" class="text-link">{{ space.recurso.nombre }}</Link>
                <small class="cell-subtitle">{{ space.recurso.codigo }}</small>
              </td>
              <td>{{ space.piso.nombre }}</td>
              <td>{{ space.tipo.nombre }}</td>
              <td>{{ space.capacidad }} personas</td>
              <td>
                <SpaceState :value="space.estado"/>
              </td>
              <td>
                <div class="feature-chips">
                  <span v-for="feature in space.caracteristicas" :key="feature.id">{{ feature.nombre }}</span>
                  <span v-if="!space.caracteristicas.length">Sin características</span>
                </div>
              </td>
              <td>
                <Link :href="`/espacios/${space.recurso_id}`" class="text-link">Ver detalle</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="building"/>
        <h3>No hay espacios que coincidan</h3>
        <p>Ajuste los filtros o registre el primer espacio si tiene autorización.</p>
      </div>
      <Pagination :data="espacios"/>
    </section>
  </AppLayout>
</template>
