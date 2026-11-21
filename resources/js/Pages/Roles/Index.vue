<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';

defineProps({ roles: Array, faltantes: Array });
const { can } = useAccess();
</script>

<template>
  <AppLayout title="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>Roles y permisos</h1>
        <p>Roles institucionales y sus accesos.</p>
      </div>
      <div class="button-group">
        <Link v-if="can('permisos.ver')" href="/permisos" class="btn btn-secondary">
          <Icon name="key"/>
          Catálogo de permisos
        </Link>
        <Link v-if="can('roles.crear')&&faltantes.length" href="/roles/crear" class="btn btn-primary">
          <Icon name="plus"/>
          Registrar rol
        </Link>
      </div>
    </div>
    <section class="panel">
      <div class="table-scroll" tabindex="0" role="region" aria-label="Roles institucionales">
        <table class="roles-table">
          <caption class="sr-only">Roles institucionales</caption>
          <thead>
            <tr>
              <th scope="col">Rol</th>
              <th scope="col" class="align-right">Asignaciones activas</th>
              <th scope="col" class="align-right">Permisos</th>
              <th scope="col" class="align-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in roles" :key="role.id">
              <td>
                <Link :href="`/roles/${role.id}`" class="role-name"><strong>{{ role.nombre }}</strong></Link>
                <small class="cell-subline role-summary" :title="role.descripcion||undefined">{{ role.descripcion||'Sin descripción.' }}</small>
              </td>
              <td class="align-right role-count">{{ role.usuarios_activos_count }}</td>
              <td class="align-right role-count">{{ role.permisos_count }}</td>
              <td>
                <div class="row-actions">
                  <Link :href="`/roles/${role.id}`">Ver</Link>
                  <Link v-if="can('roles.editar')" :href="`/roles/${role.id}/editar`">Editar</Link>
                </div>
              </td>
            </tr>
            <tr v-if="!roles.length">
              <td colspan="4" class="empty-state">No hay roles registrados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
    <p v-if="!faltantes.length" class="hint">Los cuatro roles institucionales están registrados.</p>
  </AppLayout>
</template>
