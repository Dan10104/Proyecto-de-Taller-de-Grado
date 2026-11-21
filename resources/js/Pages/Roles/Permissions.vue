<script setup>
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ permisos: Array });
const groups = computed(() => Object.groupBy(props.permisos, p => p.codigo.split('.')[0]));

// Presentación: nombre legible y orden de cada módulo del catálogo de permisos.
const moduleLabels = {
  usuarios: 'Usuarios', roles: 'Roles y asignaciones', permisos: 'Permisos', espacios: 'Espacios académicos',
  reservas: 'Reservas', equipamientos: 'Equipamiento tecnológico', prestamos: 'Préstamos',
  incidencias: 'Incidencias y mantenimiento', qr: 'Códigos QR', iot: 'Sensores IoT y monitoreo',
  liberacion: 'Políticas de liberación', trazabilidad: 'Evidencias de trazabilidad', notificaciones: 'Notificaciones',
  dashboard: 'Dashboard', reportes: 'Reportes', chatbot: 'Asistente', actividad: 'Bitácora de actividad',
};
const moduleOrder = Object.keys(moduleLabels);
const moduleLabel = key => moduleLabels[key] || key.charAt(0).toUpperCase() + key.slice(1);
const rank = key => (moduleOrder.includes(key) ? moduleOrder.indexOf(key) : moduleOrder.length);
const groupList = computed(() => Object.entries(groups.value).sort(([a], [b]) => rank(a) - rank(b)));
</script>

<template>
  <AppLayout title="Catálogo de permisos" section="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>Catálogo de permisos</h1>
        <p>{{ permisos.length }} permisos en {{ groupList.length }} módulos.</p>
      </div>
    </div>
    <section class="panel">
      <div v-if="!groupList.length" class="empty-state">
        <Icon name="key"/>
        <h2>No hay permisos registrados</h2>
      </div>
      <div v-else class="permission-groups">
        <details v-for="([group, items], index) in groupList" :key="group" class="permission-group" :open="index===0">
          <summary>
            <span class="permission-group-title">{{ moduleLabel(group) }}</span>
            <span class="permission-group-count">{{ items.length }} {{ items.length === 1 ? 'permiso' : 'permisos' }}</span>
          </summary>
          <div class="table-scroll" tabindex="0" role="region" :aria-label="`Permisos de ${moduleLabel(group)}`">
            <table>
              <caption class="sr-only">Permisos de {{ moduleLabel(group) }}</caption>
              <thead>
                <tr>
                  <th scope="col">Permiso</th>
                  <th scope="col">Alcance</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in items" :key="item.id">
                  <td>
                    <strong>{{ item.nombre }}</strong>
                    <small class="cell-subline permission-code">{{ item.codigo }}</small>
                  </td>
                  <td class="wrap-cell">{{ item.descripcion || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
      </div>
    </section>
  </AppLayout>
</template>
