<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Icon from '../Components/Icon.vue';
import { useAccess } from '../security';

const { auth, can } = useAccess();

// Cada rol ve primero lo que puede iniciar o le toca resolver; después, sus consultas.
const primary = computed(() => [
  { show: can('reservas.solicitar') && can('espacios.ver'), href: '/disponibilidad', icon: 'calendar', label: 'Reservar un espacio', note: 'Elija fecha, horario y ambiente' },
  { show: can('prestamos.solicitar') && can('equipamientos.ver'), href: '/equipamientos/disponibilidad', icon: 'monitor', label: 'Solicitar un préstamo', note: 'Consulte equipos disponibles' },
  { show: can('reservas.ver_administrativas') && can('reservas.resolver'), href: '/reservas?alcance=administrativas&estado=PENDIENTE', icon: 'checklist', label: 'Resolver reservas', note: 'Solicitudes pendientes de revisión' },
  { show: can('prestamos.ver_administrativos'), href: '/prestamos?alcance=administrativos&estado=PENDIENTE', icon: 'exchange', label: 'Atender préstamos', note: 'Solicitudes, entregas y devoluciones' },
  { show: can('incidencias.gestionar'), href: '/incidencias', icon: 'tool', label: 'Incidencias y mantenimiento', note: 'Registre y cierre incidencias' },
].filter(item => item.show));

// Seguimiento personal: solo lo propio del usuario; el resto de módulos ya está en el menú lateral.
const unread = computed(() => auth.value?.no_leidas || 0);
const follow = computed(() => [
  { show: can('reservas.ver_propias'), href: '/reservas?alcance=propias', icon: 'calendar', label: 'Mis reservas', note: 'Estado de sus solicitudes' },
  { show: can('prestamos.ver_propios'), href: '/prestamos?alcance=propios', icon: 'exchange', label: 'Mis préstamos', note: 'Entregas y devoluciones pendientes' },
  { show: can('notificaciones.ver_propias'), href: '/notificaciones', icon: 'bell', label: 'Notificaciones', note: unread.value ? `${unread.value} sin leer` : 'Sin avisos pendientes', count: unread.value },
].filter(item => item.show));

const empty = computed(() => !primary.value.length && !follow.value.length);
const roles = computed(() => auth.value?.roles.join(', ') || 'Sin rol asignado');
</script>

<template>
  <AppLayout title="Inicio">
    <div class="home-greeting">
      <div>
        <h1>Hola, {{ auth?.usuario.nombres }}</h1>
        <p>{{ roles }}</p>
      </div>
    </div>
    <div v-if="!empty" class="home-layout">
      <section v-if="primary.length" class="panel home-sheet" aria-labelledby="home-tasks">
        <h2 id="home-tasks">¿Qué necesita hacer?</h2>
        <ul class="home-rows">
          <li v-for="item in primary" :key="item.href">
            <Link :href="item.href" class="home-row">
              <span class="task-icon is-primary"><Icon :name="item.icon"/></span>
              <span class="task-text"><strong>{{ item.label }}</strong><small>{{ item.note }}</small></span>
              <Icon name="arrow" class="home-row-go"/>
            </Link>
          </li>
        </ul>
      </section>
      <section v-if="follow.length" class="panel home-sheet" aria-labelledby="home-follow">
        <h2 id="home-follow">Seguimiento</h2>
        <ul class="home-rows">
          <li v-for="item in follow" :key="item.href">
            <Link :href="item.href" class="home-row">
              <span class="task-icon"><Icon :name="item.icon"/></span>
              <span class="task-text"><strong>{{ item.label }}</strong><small>{{ item.note }}</small></span>
              <strong v-if="item.count" class="home-row-count" aria-hidden="true">{{ item.count > 99 ? '99+' : item.count }}</strong>
              <Icon v-else name="arrow" class="home-row-go"/>
            </Link>
          </li>
        </ul>
      </section>
    </div>
    <div v-else class="panel empty-state">
      <Icon name="shield"/>
      <h2>Su cuenta todavía no tiene accesos</h2>
      <p>Solicite a la administración de la Facultad que le asigne un rol.</p>
    </div>
  </AppLayout>
</template>
