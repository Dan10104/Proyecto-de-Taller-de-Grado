<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import Brand from '../Components/Brand.vue';
import Icon from '../Components/Icon.vue';
import FormErrors from '../Components/FormErrors.vue';
import { useAccess, finishLogout } from '../security';
import { watchTables } from '../tableLabels';
import { navigationGroups } from '../navigation';

const props = defineProps({ title: String, section: { type: String, default: '' } });
const page = usePage();
const { can, auth } = useAccess();
const drawer = ref(false), pending = ref(false), retrying = ref(false), logoutError = ref('');
const menuButton = ref(null);
const mainContent = ref(null);
let stopTables = () => {};

// La navegación se define una sola vez (navigation.js); aquí solo se marca la entrada actual.
const groups = computed(() => navigationGroups(can, auth.value?.no_leidas).map(group => ({
  ...group,
  items: group.items.map(item => ({ ...item, active: item.exact ? page.url === item.href : item.match.some(prefix => page.url.startsWith(prefix)) })),
})));

const current = computed(() => {
  for (const group of groups.value) {
    const item = group.items.find(entry => entry.active);
    if (item) return { group: group.label, item };
  }
  return null;
});

const initials = computed(() => `${auth.value?.usuario.nombres?.[0] || ''}${auth.value?.usuario.apellidos?.[0] || ''}`.toUpperCase());
const unread = computed(() => auth.value?.no_leidas || 0);
const today = new Intl.DateTimeFormat('es-BO', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'America/La_Paz' }).format(new Date());

async function logout() {
  window.dispatchEvent(new Event('espacio-u:logout'));
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

function closeDrawer() {
  if (!drawer.value) return;
  drawer.value = false;
  menuButton.value?.focus();
}

function keydown(e) {
  if (e.key === 'Escape') closeDrawer();
}

watch(() => page.url, () => { drawer.value = false; });
onMounted(() => { document.addEventListener('keydown', keydown); stopTables = watchTables(mainContent.value); });
onUnmounted(() => { document.removeEventListener('keydown', keydown); stopTables(); });
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
    <button v-if="drawer" class="drawer-backdrop" aria-label="Cerrar menú" @click="closeDrawer"></button>
    <aside id="main-navigation" class="sidebar" :class="{'sidebar-open':drawer}">
      <Link href="/inicio" class="sidebar-brand" aria-label="ESPACIO-U, ir al inicio">
        <Brand light/>
      </Link>
      <nav class="sidebar-nav" aria-label="Navegación principal">
        <div v-for="group in groups" :key="group.label" class="nav-group">
          <p class="nav-label">{{ group.label }}</p>
          <Link v-for="item in group.items" :key="item.href" :href="item.href" :class="{active:item.active}" :aria-current="item.active ? 'page' : undefined">
            <Icon :name="item.icon"/>
            <span class="nav-text">{{ item.label }}</span>
            <span v-if="item.count" class="nav-count" aria-hidden="true">{{ item.count > 99 ? '99+' : item.count }}</span>
          </Link>
        </div>
      </nav>
      <div class="sidebar-account">
        <span class="avatar" aria-hidden="true">{{ initials }}</span>
        <div class="account-text">
          <strong>{{ auth?.usuario.nombres }} {{ auth?.usuario.apellidos }}</strong>
          <small>{{ auth?.roles.join(', ')||'Sin rol asignado' }}</small>
        </div>
        <button class="logout-button" type="button" aria-label="Cerrar sesión" title="Cerrar sesión" @click="logout">
          <Icon name="logout"/>
        </button>
      </div>
    </aside>
    <div class="app-body">
      <header class="app-header">
        <div class="header-location">
          <button ref="menuButton" class="menu-button" aria-label="Abrir menú" aria-controls="main-navigation" :aria-expanded="drawer" @click="drawer=!drawer">
            <Icon name="menu"/>
          </button>
          <nav class="breadcrumb" aria-label="Ruta de navegación">
            <template v-if="current">
              <span class="breadcrumb-group">{{ current.group }}</span>
              <span class="breadcrumb-sep" aria-hidden="true">/</span>
              <Link v-if="title !== current.item.label && page.url !== current.item.href" :href="current.item.href">{{ current.item.label }}</Link>
              <span v-else aria-current="page">{{ current.item.label }}</span>
              <template v-if="title !== current.item.label && page.url !== current.item.href">
                <span class="breadcrumb-sep" aria-hidden="true">/</span>
                <span aria-current="page">{{ title }}</span>
              </template>
            </template>
            <span v-else aria-current="page">{{ title }}</span>
          </nav>
        </div>
        <div class="header-actions">
          <span class="header-date">{{ today }}</span>
          <Link v-if="can('notificaciones.ver_propias')" href="/notificaciones" class="notification-header" :aria-label="unread ? `Notificaciones: ${unread} sin leer` : 'Notificaciones: ninguna sin leer'">
            <Icon name="bell"/>
            <span v-if="unread" class="notification-badge">{{ unread > 99 ? '99+' : unread }}</span>
          </Link>
        </div>
      </header>
      <main id="main" ref="mainContent" tabindex="-1" class="page-container">
        <div v-if="page.props.flash?.success" class="alert alert-success" role="status">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.notice" class="alert" role="status">{{ page.props.flash.notice }}</div>
        <FormErrors v-if="page.props.errors?.administracion" :errors="{administracion:page.props.errors.administracion}"/>
        <FormErrors v-if="page.props.errors?.operacion && page.url.startsWith('/espacios')" :errors="{operacion:page.props.errors.operacion}"/>
        <slot/>
      </main>
    </div>
  </div>
</template>
