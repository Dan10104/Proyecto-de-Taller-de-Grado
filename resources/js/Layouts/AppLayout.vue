<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import Brand from '../Components/Brand.vue';
import Icon from '../Components/Icon.vue';
import FormErrors from '../Components/FormErrors.vue';
import { useAccess, finishLogout } from '../security';

defineProps({ title: String, section: { type: String, default: '' } });
const page = usePage();
const { can, auth } = useAccess();
const drawer = ref(false), pending = ref(false), retrying = ref(false), logoutError = ref('');
const initials = computed(() => `${auth.value?.usuario.nombres?.[0] || ''}${auth.value?.usuario.apellidos?.[0] || ''}`);

async function logout() {
  pending.value = true;
  retrying.value = true;
  sessionStorage.setItem('espacio-u.logout-pending', '1');
  try {
    await finishLogout();
  } catch {
    logoutError.value = 'No se pudo confirmar el cierre. Compruebe su conexión e inténtelo nuevamente.';
  } finally {
    retrying.value = false;
  }
}

function keydown(e) {
  if (e.key === 'Escape') drawer.value = false;
}

onMounted(() => document.addEventListener('keydown', keydown));
onUnmounted(() => document.removeEventListener('keydown', keydown));
</script>

<template>
  <Head :title="`${title} · ESPACIO-U`"/>
  <div v-if="pending" class="session-screen">
    <Brand/>
    <h1>Cerrando sesión</h1>
    <p role="status">{{ logoutError||'Confirmando el cierre seguro…' }}</p>
    <button v-if="logoutError" class="btn btn-primary" :disabled="retrying" @click="logout">Reintentar cierre</button>
  </div>
  <div v-else class="app-shell">
    <a class="skip-link" href="#main">Ir al contenido</a>
    <button v-if="drawer" class="drawer-backdrop" aria-label="Cerrar menú" @click="drawer=false"></button>
    <aside class="sidebar" :class="{'sidebar-open':drawer}">
      <Brand/>
      <p class="nav-label">PLATAFORMA</p>
      <nav aria-label="Navegación principal">
        <Link href="/inicio" :class="{active:page.url==='/inicio'}" @click="drawer=false">
          <Icon name="home"/>
          Inicio
        </Link>
        <Link v-if="can('espacios.ver')" href="/disponibilidad" :class="{active:page.url.startsWith('/disponibilidad')}" @click="drawer=false">
          <Icon name="search"/>
          Disponibilidad
        </Link>
        <Link v-if="can('espacios.ver')" href="/espacios" :class="{active:page.url.startsWith('/espacios')}" @click="drawer=false">
          <Icon name="building"/>
          Espacios académicos
        </Link>
        <Link v-if="can('reservas.ver_propias') || can('reservas.ver_administrativas')" href="/reservas" :class="{active:page.url.startsWith('/reservas')}" @click="drawer=false">
          <Icon name="building"/>Reservas
        </Link>
        <Link v-if="can('equipamientos.ver')" href="/equipamientos" :class="{active:page.url.startsWith('/equipamientos')}" @click="drawer=false"><Icon name="building"/>Equipamiento</Link>
        <Link v-if="can('prestamos.ver_propios') || can('prestamos.ver_administrativos')" href="/prestamos" :class="{active:page.url.startsWith('/prestamos')}" @click="drawer=false"><Icon name="users"/>Préstamos</Link>
        <Link v-if="can('incidencias.ver')" href="/incidencias" :class="{active:page.url.startsWith('/incidencias')}" @click="drawer=false"><Icon name="shield"/>Incidencias</Link>
        <Link v-if="can('qr.gestionar')" href="/qr" :class="{active:page.url.startsWith('/qr')}" @click="drawer=false"><Icon name="shield"/>Códigos QR</Link>
        <Link v-if="can('usuarios.ver')" href="/usuarios" :class="{active:page.url.startsWith('/usuarios')}" @click="drawer=false">
          <Icon name="users"/>
          Usuarios
        </Link>
        <Link v-if="can('roles.ver')" href="/roles" :class="{active:page.url.startsWith('/roles')||page.url.startsWith('/permisos')}" @click="drawer=false">
          <Icon name="shield"/>
          Roles y permisos
        </Link>
      </nav>
      <div class="sidebar-bottom">
        <span class="institution-tag">FICCT</span>
        <p>
          Facultad de Ingeniería en Ciencias
          <br>
          de la Computación y Telecomunicaciones
        </p>
        <div class="sidebar-rule"></div>
        <span>Acceso institucional</span>
      </div>
    </aside>
    <div class="app-body">
      <header class="app-header">
        <div class="header-location">
          <button class="menu-button" aria-label="Abrir menú" :aria-expanded="drawer" @click="drawer=!drawer">
            <Icon name="menu"/>
          </button>
          <span>Plataforma de gestión</span>
        </div>
        <div class="account">
          <span class="avatar">{{ initials }}</span>
          <div class="account-text">
            <strong>{{ auth?.usuario.nombres }} {{ auth?.usuario.apellidos }}</strong>
            <small>{{ auth?.roles.join(' · ')||'Usuario registrado' }}</small>
          </div>
          <button class="logout-button" @click="logout" aria-label="Cerrar sesión">
            <Icon name="logout"/>
            <span>Salir</span>
          </button>
        </div>
      </header>
      <main id="main" tabindex="-1" class="page-container">
        <nav class="breadcrumb" aria-label="Ruta de navegación">
          <Link href="/inicio">Inicio</Link>
          <span v-if="section||title!=='Inicio'">/</span>
          <span v-if="section">{{ section }} /</span>
          <span v-if="title!=='Inicio'" aria-current="page">{{ title }}</span>
        </nav>
        <div v-if="page.props.flash?.success" class="alert alert-success" role="status">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.notice" class="alert" role="status">{{ page.props.flash.notice }}</div>
        <FormErrors v-if="page.props.errors?.administracion" :errors="{administracion:page.props.errors.administracion}"/>
        <FormErrors v-if="page.props.errors?.operacion && page.url.startsWith('/espacios')" :errors="{operacion:page.props.errors.operacion}"/>
        <slot/>
      </main>
      <footer class="app-footer">
        ESPACIO-U
        <span>FICCT · Gestión institucional</span>
      </footer>
    </div>
  </div>
</template>
