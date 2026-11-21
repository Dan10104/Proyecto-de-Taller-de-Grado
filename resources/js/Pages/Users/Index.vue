<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useAccess, dateLabel } from '../../security';

const props = defineProps({ usuarios: Object, filtros: Object, roles: Array });
const { can } = useAccess();
const filters = reactive({
  q: props.filtros.q || '',
  estado: props.filtros.estado || '',
  rol: props.filtros.rol || '',
});

function search() {
  router.get('/usuarios', filters, { preserveState: true, replace: true });
}

function disable(user) {
  router.patch(`/usuarios/${user.id}/deshabilitar`, {}, { preserveScroll: true });
}
</script>

<template>
  <AppLayout title="Usuarios">
    <div class="page-heading">
      <div>
        <h1>Usuarios</h1>
        <p>Cuentas y acceso a la plataforma.</p>
      </div>
      <Link v-if="can('usuarios.crear')" href="/usuarios/crear" class="btn btn-primary">
        <Icon name="plus"/>
        Registrar usuario
      </Link>
    </div>
    <form class="panel filter-bar" @submit.prevent="search">
      <div class="field">
        <label for="buscar">Buscar usuario</label>
        <input id="buscar" v-model="filters.q" placeholder="Nombre, apellido o correo" maxlength="120" type="search">
      </div>
      <div class="field">
        <label for="estado">Estado</label>
        <select id="estado" v-model="filters.estado">
          <option value="">Todos los estados</option>
          <option value="ACTIVO">Activo</option>
          <option value="INACTIVO">Inactivo</option>
        </select>
      </div>
      <div class="field">
        <label for="rol">Rol</label>
        <select id="rol" v-model="filters.rol">
          <option value="">Todos los roles</option>
          <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nombre }}</option>
        </select>
      </div>
      <div class="filter-actions">
        <div class="button-group">
          <button v-if="filters.q||filters.estado||filters.rol" class="text-button" type="button" @click="Object.assign(filters,{q:'',estado:'',rol:''});search()">Limpiar filtros</button>
          <button class="btn btn-secondary" type="submit">Aplicar filtros</button>
        </div>
      </div>
    </form>
    <section class="panel">
      <div class="table-scroll" tabindex="0" role="region" aria-label="Listado de usuarios desplazable">
        <table>
          <caption class="sr-only">Usuarios registrados</caption>
          <thead>
            <tr>
              <th scope="col">Usuario</th>
              <th scope="col">Correo</th>
              <th scope="col">Roles activos</th>
              <th scope="col">Estado</th>
              <th scope="col" class="align-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in usuarios.data" :key="user.id">
              <td>
                <Link :href="`/usuarios/${user.id}`" class="person-cell">
                  <span class="avatar avatar-small" aria-hidden="true">{{ user.nombres[0] }}{{ user.apellidos[0] }}</span>
                  <span>
                    <strong>{{ user.nombres }} {{ user.apellidos }}</strong>
                    <small class="cell-subline">Registro: {{ dateLabel(user.fecha_registro) }}</small>
                  </span>
                </Link>
              </td>
              <td>{{ user.correo }}</td>
              <td>
                <div v-if="user.roles_activos.length" class="role-chips">
                  <span v-for="role in user.roles_activos" :key="role.id">{{ role.nombre }}</span>
                </div>
                <span v-else class="hint">Sin rol</span>
              </td>
              <td>
                <StatusBadge :value="user.estado"/>
              </td>
              <td>
                <div class="row-actions">
                  <Link :href="`/usuarios/${user.id}`">Ver</Link>
                  <Link v-if="can('usuarios.editar')" :href="`/usuarios/${user.id}/editar`">Editar</Link>
                  <ConfirmAction v-if="can('usuarios.deshabilitar')&&user.estado==='ACTIVO'" label="Deshabilitar" :message="`¿Deshabilitar a ${user.nombres} ${user.apellidos}? Sus sesiones se cerrarán y su historial se conservará.`" @confirm="disable(user)"/>
                </div>
              </td>
            </tr>
            <tr v-if="!usuarios.data.length">
              <td colspan="5" class="empty-state">No se encontraron usuarios con estos filtros.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :data="usuarios"/>
    </section>
  </AppLayout>
</template>
