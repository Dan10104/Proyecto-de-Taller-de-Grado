<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';

const props = defineProps({ rol: Object, catalogo: Array, usuarios: Object });
const { can } = useAccess();
const form = useForm({ permisos: props.rol.permisos.map(p => p.id) });
const groups = computed(() => Object.groupBy(props.catalogo, p => p.codigo.split('.')[0]));
const search = ref(''), results = ref([]), searching = ref(false), searchError = ref(''), selected = ref('');
const assignForm = useForm({ usuario_id: '' });

async function find() {
  searching.value = true;
  searchError.value = '';
  try {
    const res = await fetch(`/asignaciones/candidatos?q=${encodeURIComponent(search.value)}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw new Error();
    results.value = await res.json();
  } catch {
    searchError.value = 'No se pudo consultar usuarios. Revise su sesión o inténtelo nuevamente.';
  } finally {
    searching.value = false;
  }
}

function assign() {
  assignForm.usuario_id = selected.value;
  assignForm.post(`/roles/${props.rol.id}/asignaciones`, {
    preserveScroll: true,
    onSuccess: () => {
      selected.value = '';
      results.value = [];
      search.value = '';
    },
  });
}

function withdraw(user) {
  router.delete(`/roles/${props.rol.id}/asignaciones`, {
    data: { usuario_id: user.id },
    preserveScroll: true,
  });
}

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
const checkedIn = permissions => permissions.filter(permission => form.permisos.includes(permission.id)).length;
</script>

<template>
  <AppLayout :title="rol.nombre" section="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>{{ rol.nombre }}</h1>
        <p v-if="rol.descripcion">{{ rol.descripcion }}</p>
      </div>
      <Link v-if="can('roles.editar')" :href="`/roles/${rol.id}/editar`" class="btn btn-secondary">
        <Icon name="edit"/>
        Editar descripción
      </Link>
    </div>

    <section class="numerals" aria-label="Resumen del rol">
      <div class="numeral">
        <span class="numeral-head"><Icon name="key"/></span>
        <strong class="numeral-value">{{ rol.permisos.length }}</strong>
        <span class="numeral-label">Permisos asignados</span>
      </div>
      <div class="numeral">
        <span class="numeral-head"><Icon name="users"/></span>
        <strong class="numeral-value">{{ usuarios.total }}</strong>
        <span class="numeral-label">Asignaciones activas</span>
      </div>
    </section>

    <section class="panel">
      <div class="panel-heading">
        <h2>Permisos del rol</h2>
        <p>Acciones admitidas para este rol institucional.</p>
      </div>
      <form v-if="can('permisos.ver')" @submit.prevent="form.put(`/roles/${rol.id}/permisos`,{preserveScroll:true})">
        <FormErrors :errors="form.errors"/>
        <p v-if="!groupList.length" class="hint">No hay permisos disponibles para este rol.</p>
        <div v-else class="permission-groups">
          <details v-for="([group, permissions], index) in groupList" :key="group" class="permission-group" :open="index===0">
            <summary>
              <span class="permission-group-title">{{ moduleLabel(group) }}</span>
              <span class="permission-group-count">{{ checkedIn(permissions) }} de {{ permissions.length }}</span>
            </summary>
            <fieldset>
              <legend class="sr-only">{{ moduleLabel(group) }}</legend>
              <label v-for="permission in permissions" :key="permission.id" class="check-option permission-option">
                <input v-model="form.permisos" type="checkbox" :value="permission.id" :disabled="!can('roles.gestionar_permisos')">
                <span>
                  {{ permission.nombre }}
                  <small class="cell-subline permission-code">{{ permission.codigo }}</small>
                </span>
              </label>
            </fieldset>
          </details>
        </div>
        <div v-if="can('roles.gestionar_permisos')" class="form-actions">
          <button class="btn btn-primary" :disabled="form.processing">Guardar permisos</button>
        </div>
      </form>
      <template v-else>
        <ul v-if="rol.permisos.length" class="permission-readonly">
          <li v-for="permission in rol.permisos" :key="permission.id">{{ permission.nombre }}</li>
        </ul>
        <p v-else class="hint">Sin permisos asignados.</p>
      </template>
    </section>

    <section class="panel">
      <div class="panel-heading">
        <h2>Usuarios con este rol</h2>
        <p>Retirar un rol conserva su historial.</p>
      </div>
      <div v-if="can('roles.asignar')" class="assign-search">
        <form @submit.prevent="find" class="inline-form">
          <div class="field">
            <label for="buscar-usuario">Buscar usuario para asignar</label>
            <input id="buscar-usuario" v-model="search" type="search" minlength="2" maxlength="120" required placeholder="Nombre, apellido o correo">
          </div>
          <button class="btn btn-secondary" :disabled="searching">
            <Icon name="search"/>
            Buscar
          </button>
        </form>
        <p v-if="searchError" class="field-error" role="alert">{{ searchError }}</p>
        <form v-if="results.length" @submit.prevent="assign" class="inline-form">
          <div class="field">
            <label for="candidato">Usuario</label>
            <select id="candidato" v-model="selected" required>
              <option value="">Seleccione un usuario</option>
              <option v-for="user in results" :key="user.id" :value="user.id">{{ user.nombres }} {{ user.apellidos }} · {{ user.correo }}</option>
            </select>
          </div>
          <button class="btn btn-secondary" :disabled="assignForm.processing">Asignar rol</button>
        </form>
        <FormErrors :errors="assignForm.errors"/>
      </div>
      <div class="table-scroll" tabindex="0" role="region" aria-label="Usuarios con este rol">
        <table>
          <caption class="sr-only">Usuarios con asignación activa</caption>
          <thead>
            <tr>
              <th scope="col">Usuario</th>
              <th scope="col">Estado</th>
              <th scope="col" class="align-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in usuarios.data" :key="user.id">
              <td>
                <strong>{{ user.nombres }} {{ user.apellidos }}</strong>
                <small class="cell-subline">{{ user.correo }}</small>
              </td>
              <td>
                <StatusBadge :value="user.estado"/>
              </td>
              <td>
                <div class="row-actions">
                  <ConfirmAction v-if="can('roles.retirar')" label="Retirar rol" :message="`¿Retirar este rol a ${user.nombres} ${user.apellidos}? Se conservará el historial.`" @confirm="withdraw(user)"/>
                  <span v-else>—</span>
                </div>
              </td>
            </tr>
            <tr v-if="!usuarios.data.length">
              <td colspan="3" class="empty-state">No hay usuarios con este rol activo.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :data="usuarios"/>
    </section>
  </AppLayout>
</template>
