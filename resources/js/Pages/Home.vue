<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import Icon from '../Components/Icon.vue';
import { useAccess } from '../security';

const { auth, can } = useAccess();
</script>

<template>
  <AppLayout title="Inicio">
    <section class="welcome-panel">
      <div>

        <h1>Bienvenido, {{ auth?.usuario.nombres }}.</h1>
        <p>Desde aquí puede acceder a las funciones habilitadas para su cuenta.</p>
        <div class="role-chips">
          <span v-for="role in auth?.roles" :key="role">{{ role }}</span>
          <span v-if="!auth?.roles.length">Usuario registrado</span>
        </div>
      </div>
      <div class="welcome-icon">
        <Icon name="building"/>
      </div>
    </section>
    <section class="section-heading">
      <h2>Accesos disponibles</h2>
      <p>Consultas y herramientas habilitadas para su cuenta.</p>
    </section>
    <div class="access-grid">
      <Link v-if="can('espacios.ver')" href="/disponibilidad" class="access-card">
        <span class="tile-icon">
          <Icon name="search"/>
        </span>
        <h3>Disponibilidad de espacios</h3>
        <p>Encuentre espacios por horario, capacidad, ubicación y características.</p>
        <span class="text-link">
          Consultar disponibilidad
          <Icon name="arrow"/>
        </span>
      </Link>
      <Link v-if="can('espacios.ver')" href="/espacios" class="access-card">
        <span class="tile-icon">
          <Icon name="building"/>
        </span>
        <h3>Espacios académicos</h3>
        <p>Consulte los ambientes y sus condiciones de uso.</p>
        <span class="text-link">
          Ver espacios
          <Icon name="arrow"/>
        </span>
      </Link>
      <Link v-if="can('reservas.ver_propias') || can('reservas.ver_administrativas')" href="/reservas" class="access-card">
        <span class="tile-icon"><Icon name="building"/></span><h3>Reservas de espacios</h3><p>Consulte sus solicitudes y las decisiones registradas.</p><span class="text-link">Ver reservas<Icon name="arrow"/></span>
      </Link>
      <Link v-if="can('usuarios.ver')" href="/usuarios" class="access-card">
        <span class="tile-icon">
          <Icon name="users"/>
        </span>
        <h3>Usuarios</h3>
        <p>Consulte cuentas, actualice datos y gestione accesos autorizados.</p>
        <span class="text-link">
          Gestionar usuarios
          <Icon name="arrow"/>
        </span>
      </Link>
      <Link v-if="can('roles.ver')" href="/roles" class="access-card">
        <span class="tile-icon">
          <Icon name="shield"/>
        </span>
        <h3>Roles y permisos</h3>
        <p>Configure responsabilidades y conserve el historial de asignaciones.</p>
        <span class="text-link">
          Gestionar roles
          <Icon name="arrow"/>
        </span>
      </Link>
      <div v-if="!can('usuarios.ver')&&!can('roles.ver')&&!can('espacios.ver')&&!can('reservas.ver_propias')&&!can('reservas.ver_administrativas')" class="panel empty-state">
        <Icon name="shield"/>
        <h3>Su sesión está activa</h3>
        <p>Actualmente su cuenta no tiene funciones de administración habilitadas. Consulte a la administración si necesita un cambio de acceso.</p>
      </div>
    </div>
    <div class="institution-note">
      <Icon name="building"/>
      <div>
        <strong>Gestión inteligente, desde un solo lugar.</strong>
        <p>ESPACIO-U · Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones.</p>
      </div>
    </div>
  </AppLayout>
</template>
