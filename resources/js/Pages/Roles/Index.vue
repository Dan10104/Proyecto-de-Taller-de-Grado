<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';
import { useAccess } from '../../security';

defineProps({ roles: Array, faltantes: Array });
const { can } = useAccess();
</script>

<template>
  <AppLayout title="Roles y permisos">
    <div class="page-heading">
      <div>
        <h1>Roles y permisos</h1>
        <p>Responsabilidades institucionales y accesos autorizados.</p>
      </div>
      <div class="button-group">
        <Link v-if="can('permisos.ver')" href="/permisos" class="btn btn-secondary">Catálogo de permisos</Link>
        <Link v-if="can('roles.crear')&&faltantes.length" href="/roles/crear" class="btn btn-primary">
          <Icon name="plus"/>
          Registrar rol
        </Link>
      </div>
    </div>
    <div class="role-grid">
      <article v-for="role in roles" :key="role.id" class="panel role-card">
        <span class="tile-icon">
          <Icon name="shield"/>
        </span>
        <h2>{{ role.nombre }}</h2>
        <p>{{ role.descripcion||'Sin descripción.' }}</p>
        <div class="role-meta">
          <span>{{ role.usuarios_activos_count }} asignaciones activas</span>
          <span>{{ role.permisos_count }} permisos</span>
        </div>
        <div class="role-actions">
          <Link :href="`/roles/${role.id}`" class="text-link">
            Consultar rol
            <Icon name="arrow"/>
          </Link>
          <Link v-if="can('roles.editar')" :href="`/roles/${role.id}/editar`">Editar</Link>
        </div>
      </article>
    </div>
    <p v-if="!faltantes.length" class="hint">Los cuatro roles institucionales están registrados.</p>
  </AppLayout>
</template>
