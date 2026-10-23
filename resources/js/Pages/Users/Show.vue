<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Pagination from '../../Components/Pagination.vue';
import { useAccess, dateLabel } from '../../security';

const props = defineProps({ usuario: Object, roles: Array, historial: Object });
const { can } = useAccess();
const selected = ref('');
const form = useForm({ usuario_id: props.usuario.id });

function assign() {
  if (selected.value) form.post(`/roles/${selected.value}/asignaciones`, { preserveScroll: true });
}

function withdraw(role) {
  router.delete(`/roles/${role.id}/asignaciones`, {
    data: { usuario_id: props.usuario.id },
    preserveScroll: true,
  });
}
</script>

<template>
  <AppLayout title="Detalle de usuario" section="Usuarios">
    <div class="page-heading">
      <div>
        <h1>{{ usuario.nombres }} {{ usuario.apellidos }}</h1>
        <p>Información de la cuenta y sus responsabilidades.</p>
      </div>
      <Link v-if="can('usuarios.editar')" :href="`/usuarios/${usuario.id}/editar`" class="btn btn-primary">Editar usuario</Link>
    </div>
    <section class="panel detail-panel">
      <div class="detail-grid">
        <div>
          <span class="detail-label">Correo</span>
          <strong>{{ usuario.correo }}</strong>
        </div>
        <div>
          <span class="detail-label">Estado</span>
          <StatusBadge :value="usuario.estado"/>
        </div>
        <div>
          <span class="detail-label">Fecha de registro</span>
          <strong>{{ dateLabel(usuario.fecha_registro) }}</strong>
        </div>
      </div>
    </section>
    <section class="panel detail-panel">
      <h2>Roles activos</h2>
      <div class="assigned-list">
        <div v-for="role in usuario.roles_activos" :key="role.id">
          <strong>{{ role.nombre }}</strong>
          <ConfirmAction v-if="can('roles.retirar')" label="Retirar rol" :message="`¿Retirar el rol ${role.nombre}? Se conservará el historial.`" @confirm="withdraw(role)"/>
        </div>
        <p v-if="!usuario.roles_activos.length" class="muted">No tiene roles activos.</p>
      </div>
      <form v-if="can('roles.asignar')" @submit.prevent="assign" class="inline-form">
        <div class="field">
          <label for="asignar-rol">Asignar un rol</label>
          <select id="asignar-rol" v-model="selected" required>
            <option value="">Seleccione un rol</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nombre }}</option>
          </select>
        </div>
        <button class="btn btn-primary" :disabled="form.processing">Asignar rol</button>
      </form>
      <FormErrors :errors="form.errors"/>
    </section>
    <section v-if="historial" class="panel">
      <div class="panel-heading">
        <h2>Historial de asignaciones</h2>
        <p>Responsables y fechas de cada asignación y retiro.</p>
      </div>
      <div class="table-scroll" tabindex="0">
        <table>
          <caption class="sr-only">Historial de roles</caption>
          <thead>
            <tr>
              <th>Rol</th>
              <th>Asignado por</th>
              <th>Asignación</th>
              <th>Retirado por</th>
              <th>Retiro</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in historial.data" :key="entry.id">
              <td>{{ entry.rol.nombre }}</td>
              <td>{{ entry.asignador.nombres }} {{ entry.asignador.apellidos }}</td>
              <td>{{ dateLabel(entry.fecha_asignacion) }}</td>
              <td>{{ entry.retirador?`${entry.retirador.nombres} ${entry.retirador.apellidos}`:'—' }}</td>
              <td>{{ entry.fecha_retiro?dateLabel(entry.fecha_retiro):'Asignación activa' }}</td>
            </tr>
            <tr v-if="!historial.data.length">
              <td colspan="5" class="empty-state">Todavía no hay asignaciones.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :data="historial"/>
    </section>
  </AppLayout>
</template>
