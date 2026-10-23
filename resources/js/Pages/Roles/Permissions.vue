<script setup>
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ permisos: Array });
const groups = computed(() => Object.groupBy(props.permisos, p => p.codigo.split('.')[0]));
</script>

<template>
  <AppLayout title="Catálogo de permisos" section="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>Catálogo de permisos</h1>
        <p>Acciones disponibles para la gestión de usuarios y responsabilidades.</p>
      </div>
    </div>
    <section v-for="(items,group) in groups" :key="group" class="panel">
      <div class="panel-heading">
        <h2>{{ group==='usuarios'?'Usuarios':group==='roles'?'Roles y asignaciones':'Consulta de permisos' }}</h2>
      </div>
      <div class="table-scroll" tabindex="0">
        <table>
          <thead>
            <tr>
              <th>Permiso</th>
              <th>Código</th>
              <th>Alcance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>
                <strong>{{ item.nombre }}</strong>
              </td>
              <td>
                <code>{{ item.codigo }}</code>
              </td>
              <td>{{ item.descripcion }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AppLayout>
</template>
