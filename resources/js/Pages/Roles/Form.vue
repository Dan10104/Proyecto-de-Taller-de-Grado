<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import { useAccess } from '../../security';

const props = defineProps({ rol: Object, nombres: Array });
const { can } = useAccess();
const form = useForm({
  nombre: props.rol?.nombre || '',
  descripcion: props.rol?.descripcion || '',
});

function submit() {
  props.rol ? form.put(`/roles/${props.rol.id}`) : form.post('/roles');
}
</script>

<template>
  <AppLayout :title="rol?'Editar rol':'Registrar rol'" section="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>{{ rol?'Editar rol':'Registrar rol institucional' }}</h1>
        <p>Conserve la identidad y responsabilidad del rol.</p>
      </div>
    </div>
    <form class="panel form-panel" @submit.prevent="submit">
      <FormErrors :errors="form.errors"/>
      <div v-if="!rol&&!nombres.length" class="alert">Todos los roles institucionales ya están registrados.</div>
      <div class="field">
        <label for="nombre">Nombre del rol *</label>
        <select id="nombre" v-model="form.nombre" required :disabled="!!rol">
          <option value="">Seleccione un rol institucional</option>
          <option v-for="name in nombres" :key="name" :value="name">{{ name }}</option>
        </select>
        <p v-if="rol" class="hint">El nombre identifica el rol institucional y se conserva junto con su historial.</p>
      </div>
      <div class="field">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" v-model="form.descripcion" maxlength="255" rows="4"></textarea>
        <p class="hint">Hasta 255 caracteres.</p>
      </div>
      <div class="form-actions">
        <Link :href="can('roles.ver')?'/roles':'/inicio'" class="btn btn-secondary">Cancelar</Link>
        <button class="btn btn-primary" :disabled="form.processing||(!rol&&!nombres.length)">Guardar rol</button>
      </div>
    </form>
  </AppLayout>
</template>
