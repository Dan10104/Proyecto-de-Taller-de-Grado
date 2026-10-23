<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
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
</script>

<template>
  <AppLayout :title="rol.nombre" section="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>{{ rol.nombre }}</h1>
        <p>{{ rol.descripcion }}</p>
      </div>
      <Link v-if="can('roles.editar')" :href="`/roles/${rol.id}/editar`" class="btn btn-secondary">Editar descripción</Link>
    </div>
    <section class="panel detail-panel">
      <h2>Permisos del rol</h2>
      <p class="muted">Cada permiso habilita una acción y conserva las restricciones del rol.</p>
      <form v-if="can('permisos.ver')" @submit.prevent="form.put(`/roles/${rol.id}/permisos`,{preserveScroll:true})">
        <FormErrors :errors="form.errors"/>
        <div v-if="rol.nombre!=='Administrador'" class="alert">Los permisos de administración disponibles en esta etapa corresponden al rol Administrador.</div>
        <div class="permission-groups">
          <fieldset v-for="(permissions,group) in groups" :key="group">
            <legend>{{ group==='usuarios'?'Usuarios':group==='roles'?'Roles y asignaciones':'Catálogo de permisos' }}</legend>
            <label v-for="permission in permissions" :key="permission.id" class="permission-option">
              <input v-model="form.permisos" type="checkbox" :value="permission.id" :disabled="!can('roles.gestionar_permisos')||rol.nombre!=='Administrador'">
              <span>
                <strong>{{ permission.nombre }}</strong>
                <small>{{ permission.codigo }}</small>
              </span>
            </label>
          </fieldset>
        </div>
        <div v-if="can('roles.gestionar_permisos')&&rol.nombre==='Administrador'" class="form-actions">
          <button class="btn btn-primary" :disabled="form.processing">Guardar permisos</button>
        </div>
      </form>
      <div v-else class="assigned-list">
        <p v-for="permission in rol.permisos" :key="permission.id">{{ permission.nombre }}</p>
        <p v-if="!rol.permisos.length">Sin permisos de administración.</p>
      </div>
    </section>
    <section class="panel">
      <div class="panel-heading">
        <h2>Usuarios con este rol</h2>
        <p>Asignaciones activas; retirar un rol conserva su historia.</p>
      </div>
      <div v-if="can('roles.asignar')" class="assign-search">
        <form @submit.prevent="find" class="inline-form">
          <div class="field">
            <label for="buscar-usuario">Buscar usuario para asignar</label>
            <input id="buscar-usuario" v-model="search" type="search" minlength="2" maxlength="120" required placeholder="Nombre, apellido o correo">
          </div>
          <button class="btn btn-secondary" :disabled="searching">Buscar</button>
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
          <button class="btn btn-primary" :disabled="assignForm.processing">Asignar rol</button>
        </form>
        <FormErrors :errors="assignForm.errors"/>
      </div>
      <div class="table-scroll" tabindex="0">
        <table>
          <caption class="sr-only">Usuarios con asignación activa</caption>
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in usuarios.data" :key="user.id">
              <td>{{ user.nombres }} {{ user.apellidos }}</td>
              <td>{{ user.correo }}</td>
              <td>
                <StatusBadge :value="user.estado"/>
              </td>
              <td>
                <ConfirmAction v-if="can('roles.retirar')" label="Retirar rol" :message="`¿Retirar este rol a ${user.nombres} ${user.apellidos}? Se conservará el historial.`" @confirm="withdraw(user)"/>
                <span v-else>—</span>
              </td>
            </tr>
            <tr v-if="!usuarios.data.length">
              <td colspan="4" class="empty-state">No hay usuarios con este rol activo.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :data="usuarios"/>
    </section>
  </AppLayout>
</template>
