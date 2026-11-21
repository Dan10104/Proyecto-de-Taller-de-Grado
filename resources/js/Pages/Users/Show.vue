<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
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

// Presentación: el historial se divide en asignaciones vigentes y retiradas, sin perder ninguna fila.
const currentEntries = computed(() => (props.historial?.data || []).filter(entry => !entry.fecha_retiro));
const pastEntries = computed(() => (props.historial?.data || []).filter(entry => entry.fecha_retiro));
const person = user => (user ? `${user.nombres} ${user.apellidos}` : '—');
</script>

<template>
  <AppLayout title="Detalle de usuario" section="Usuarios">
    <div class="page-heading">
      <div class="user-identity">
        <span class="avatar" aria-hidden="true">{{ usuario.nombres[0] }}{{ usuario.apellidos[0] }}</span>
        <div>
          <h1>{{ usuario.nombres }} {{ usuario.apellidos }}</h1>
          <p>{{ usuario.correo }}</p>
        </div>
      </div>
      <Link v-if="can('usuarios.editar')" :href="`/usuarios/${usuario.id}/editar`" class="btn btn-primary">
        <Icon name="edit"/>
        Editar usuario
      </Link>
    </div>

    <section class="panel detail-panel">
      <dl class="detail-grid">
        <div>
          <dt>Estado</dt>
          <dd><StatusBadge :value="usuario.estado"/></dd>
        </div>
        <div>
          <dt>Correo</dt>
          <dd>{{ usuario.correo }}</dd>
        </div>
        <div>
          <dt>Fecha de registro</dt>
          <dd>{{ dateLabel(usuario.fecha_registro) }}</dd>
        </div>
      </dl>
    </section>

    <section class="panel">
      <div class="panel-heading">
        <h2>Roles activos</h2>
        <p>{{ usuario.roles_activos.length }} {{ usuario.roles_activos.length === 1 ? 'rol' : 'roles' }}</p>
      </div>
      <ul v-if="usuario.roles_activos.length" class="active-roles">
        <li v-for="role in usuario.roles_activos" :key="role.id">
          <span class="active-role-name"><Icon name="shield"/>{{ role.nombre }}</span>
          <ConfirmAction v-if="can('roles.retirar')" label="Retirar rol" :message="`¿Retirar el rol ${role.nombre}? Se conservará el historial.`" @confirm="withdraw(role)"/>
        </li>
      </ul>
      <p v-else class="hint">No tiene roles activos.</p>
      <form v-if="can('roles.asignar')" @submit.prevent="assign" class="inline-form">
        <div class="field">
          <label for="asignar-rol">Asignar un rol</label>
          <select id="asignar-rol" v-model="selected" required>
            <option value="">Seleccione un rol</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nombre }}</option>
          </select>
        </div>
        <button class="btn btn-secondary" :disabled="form.processing">Asignar rol</button>
      </form>
      <FormErrors :errors="form.errors"/>
    </section>

    <section v-if="historial" class="panel">
      <div class="panel-heading">
        <h2>Historial de asignaciones</h2>
        <p>Quién asignó o retiró cada rol y cuándo.</p>
      </div>
      <div v-if="currentEntries.length" class="table-scroll" tabindex="0" role="region" aria-label="Asignaciones vigentes">
        <table>
          <caption class="sr-only">Asignaciones vigentes</caption>
          <thead>
            <tr>
              <th scope="col">Rol</th>
              <th scope="col">Asignado por</th>
              <th scope="col">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in currentEntries" :key="entry.id">
              <td><strong>{{ entry.rol.nombre }}</strong></td>
              <td>{{ person(entry.asignador) }}<small class="cell-subline">{{ dateLabel(entry.fecha_asignacion) }}</small></td>
              <td><span class="hint">Asignación activa</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <details v-if="pastEntries.length" class="history-past" :open="!currentEntries.length">
        <summary>Asignaciones retiradas ({{ pastEntries.length }})</summary>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Asignaciones retiradas">
          <table>
            <caption class="sr-only">Asignaciones retiradas</caption>
            <thead>
              <tr>
                <th scope="col">Rol</th>
                <th scope="col">Asignado por</th>
                <th scope="col">Retirado por</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in pastEntries" :key="entry.id">
                <td><strong>{{ entry.rol.nombre }}</strong></td>
                <td>{{ person(entry.asignador) }}<small class="cell-subline">{{ dateLabel(entry.fecha_asignacion) }}</small></td>
                <td>{{ person(entry.retirador) }}<small class="cell-subline">{{ dateLabel(entry.fecha_retiro) }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>
      <p v-if="!historial.data.length" class="hint">Todavía no hay asignaciones.</p>
      <Pagination :data="historial"/>
    </section>
  </AppLayout>
</template>
