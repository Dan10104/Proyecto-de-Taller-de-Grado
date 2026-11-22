<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Brand from '../../Components/Brand.vue';
import Icon from '../../Components/Icon.vue';
import building from '../../../images/faculty-building.svg';

const page = usePage();
const visible = ref(false);
const form = useForm({ correo: '', password: '' });

function submit() {
  form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
  <Head title="Iniciar sesión · ESPACIO-U"/>
  <div class="login-page">
    <section class="login-story" aria-label="ESPACIO-U">
      <div class="login-story-inner">
        <Brand light/>
        <div class="login-story-main">
          <h2>Reservas de espacios y préstamos de equipamiento</h2>
          <!-- Carga diferida: donde la hoja de estilos la retira (una columna con poca altura), el navegador no la descarga. -->
          <img class="login-illustration" :src="building" alt="" width="1078" height="538" loading="lazy" decoding="async">
          <ul class="login-points">
            <li><Icon name="calendar"/>Espacios académicos</li>
            <li><Icon name="monitor"/>Equipamiento tecnológico</li>
            <li><Icon name="signal"/>Monitoreo de ocupación</li>
            <li><Icon name="network"/>Trazabilidad verificable</li>
          </ul>
        </div>
        <p class="login-faculty">Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones</p>
      </div>
    </section>
    <main class="login-form-side">
      <div class="login-form-wrap">
        <h1>Iniciar sesión</h1>
        <p class="lead">Ingrese con su cuenta de ESPACIO-U.</p>
        <div v-if="page.props.flash?.notice" class="alert" role="status">{{ page.props.flash.notice }}</div>
        <form @submit.prevent="submit" class="form-stack">
          <div class="field">
            <label for="correo">Correo</label>
            <input id="correo" v-model="form.correo" type="email" autocomplete="username" maxlength="190" required autofocus :aria-invalid="!!form.errors.correo" aria-describedby="correo-error">
            <p id="correo-error" class="field-error" role="alert">{{ form.errors.correo }}</p>
          </div>
          <div class="field">
            <label for="password">Contraseña</label>
            <div class="password-field">
              <input id="password" v-model="form.password" :type="visible?'text':'password'" autocomplete="current-password" maxlength="128" required :aria-invalid="!!form.errors.password" aria-describedby="password-error">
              <button type="button" @click="visible=!visible" :aria-pressed="visible" :aria-label="visible?'Ocultar contraseña':'Mostrar contraseña'">{{ visible?'Ocultar':'Mostrar' }}</button>
            </div>
            <p id="password-error" class="field-error" role="alert">{{ form.errors.password }}</p>
          </div>
          <button class="btn btn-primary login-submit" :disabled="form.processing" type="submit">{{ form.processing?'Verificando…':'Iniciar sesión' }}</button>
        </form>
        <p class="login-help">¿No puede ingresar? Solicite ayuda a la administración de la Facultad.</p>
      </div>
    </main>
  </div>
</template>
