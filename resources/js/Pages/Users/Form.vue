<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import { useAccess } from '../../security';

const props = defineProps({ usuario: Object, roles: Array });
const { can } = useAccess();
const form = useForm({
  nombres: props.usuario?.nombres || '',
  apellidos: props.usuario?.apellidos || '',
  correo: props.usuario?.correo || '',
  password: '',
  password_confirmation: '',
  ...(!props.usuario && can('roles.asignar') ? { roles: [] } : {}),
});

function submit() {
  const options = {
    onFinish: () => form.reset('password', 'password_confirmation'),
  };
  props.usuario
    ? form.put(`/usuarios/${props.usuario.id}`, options)
    : form.post('/usuarios', options);
}
</script>

<template>
  <AppLayout :title="usuario?'Editar usuario':'Registrar usuario'" section="Usuarios">
    <div class="page-heading">
      <div>
        <h1>{{ usuario?'Editar usuario':'Registrar usuario' }}</h1>
        <p>Complete la información de la cuenta. Los campos con * son obligatorios.</p>
      </div>
    </div>
    <form @submit.prevent="submit" class="panel form-panel">
      <FormErrors :errors="form.errors"/>
      <div class="form-section-heading">
        <h2>Información personal</h2>
        <p>Datos de identificación del usuario.</p>
      </div>
      <div class="form-grid">
        <div class="field">
          <label for="nombres">Nombres *</label>
          <input id="nombres" v-model="form.nombres" required maxlength="120" autocomplete="given-name" :aria-invalid="!!form.errors.nombres">
        </div>
        <div class="field">
          <label for="apellidos">Apellidos *</label>
          <input id="apellidos" v-model="form.apellidos" required maxlength="120" autocomplete="family-name" :aria-invalid="!!form.errors.apellidos">
        </div>
        <div class="field span-2">
          <label for="correo">Correo *</label>
          <input id="correo" v-model="form.correo" required maxlength="190" type="email" autocomplete="email" :aria-invalid="!!form.errors.correo">
        </div>
      </div>
      <div class="form-section-heading">
        <h2>{{ usuario?'Actualizar contraseña':'Credenciales iniciales' }}</h2>
        <p>{{ usuario?'Opcional. Deje ambos campos vacíos para conservarla. Al cambiarla, se cerrarán las sesiones del usuario.':'Defina una contraseña inicial de al menos 12 caracteres.' }}</p>
      </div>
      <div class="form-grid">
        <div class="field">
          <label for="password">{{ usuario?'Nueva contraseña':'Contraseña *' }}</label>
          <input id="password" v-model="form.password" type="password" autocomplete="new-password" minlength="12" maxlength="128" :required="!usuario">
        </div>
        <div class="field">
          <label for="password_confirmation">Confirmar contraseña{{ usuario?'':' *' }}</label>
          <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="128" :required="!usuario||!!form.password">
        </div>
      </div>
      <fieldset v-if="!usuario&&can('roles.asignar')" class="role-selection">
        <legend>
          Roles iniciales
          <span class="muted">(opcional)</span>
        </legend>
        <label v-for="role in roles" :key="role.id" class="check-option">
          <input v-model="form.roles" type="checkbox" :value="role.id">
          {{ role.nombre }}
        </label>
        <p class="hint">La asignación quedará registrada con su usuario como responsable.</p>
      </fieldset>
      <div class="form-actions">
        <Link :href="can('usuarios.ver')?'/usuarios':'/inicio'" class="btn btn-secondary">Cancelar</Link>
        <button class="btn btn-primary" type="submit" :disabled="form.processing">{{ form.processing?'Guardando…':'Guardar usuario' }}</button>
      </div>
    </form>
  </AppLayout>
</template>
