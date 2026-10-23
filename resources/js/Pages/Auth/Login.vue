<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Brand from '../../Components/Brand.vue';
import Icon from '../../Components/Icon.vue';

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
    <section class="login-story">
      <Brand light/>
      <div class="login-story-main">
        <span class="eyebrow">TECNOLOGÍA AL SERVICIO DE LA FACULTAD</span>
        <h1>
          Un espacio para
          <br>
          conectar posibilidades.
        </h1>
        <p>Gestión inteligente de espacios académicos y equipamiento tecnológico</p>
        <div class="campus-art" aria-hidden="true">
          <div class="campus-grid"></div>
          <div class="campus-building building-back"></div>
          <div class="campus-building building-front">
            <i v-for="n in 12" :key="n"></i>
          </div>
          <div class="campus-label">
            <Icon name="building"/>
            <span>
              FICCT
              <br>
              <strong>Conocimiento que conecta.</strong>
            </span>
          </div>
        </div>
      </div>
      <footer>Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones</footer>
    </section>
    <main class="login-form-side">
      <div class="login-mobile-brand">
        <Brand/>
      </div>
      <div class="login-form-wrap">
        <span class="form-kicker">BIENVENIDO A ESPACIO-U</span>
        <h2>Iniciar sesión</h2>
        <p class="lead">Ingrese sus credenciales para continuar.</p>
        <div v-if="page.props.flash?.notice" class="alert" role="status">{{ page.props.flash.notice }}</div>
        <form @submit.prevent="submit" class="form-stack">
          <div class="field">
            <label for="correo">Correo</label>
            <input id="correo" v-model="form.correo" type="email" autocomplete="username" maxlength="190" required autofocus placeholder="Su correo institucional" :aria-invalid="!!form.errors.correo" aria-describedby="correo-error">
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
          <button class="btn btn-primary login-submit" :disabled="form.processing" type="submit">
            {{ form.processing?'Verificando…':'Iniciar sesión' }}
            <Icon name="arrow"/>
          </button>
        </form>
        <p class="login-access">
          <Icon name="shield"/>
          Acceso autorizado para usuarios de la FICCT
        </p>
        <p class="login-help">Si necesita ayuda con su acceso, comuníquese con la administración de la Facultad.</p>
      </div>
      <footer class="login-footer">ESPACIO-U · Plataforma institucional</footer>
    </main>
  </div>
</template>
