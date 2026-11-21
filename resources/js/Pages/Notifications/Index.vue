<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { dateLabel } from '../../security';
import { notificationTypes } from '../../notifications';
const props = defineProps({ notificaciones: Object, filtros: Object });
const page = usePage();
const form = useForm({ lectura: props.filtros.lectura || '', tipo: props.filtros.tipo || '' });
// Solo presentación: pictograma de cada tipo de aviso.
const typeIcons = { SOLICITUD: 'checklist', DECISION: 'checkCircle', CANCELACION: 'xCircle', ENTREGA: 'exchange', DEVOLUCION: 'refresh', VENCIMIENTO: 'clock', INCIDENCIA: 'alert', LIBERACION: 'door' };
</script>
<template>
  <AppLayout title="Notificaciones">
    <div class="page-heading">
      <div>
        <div class="notification-title">
          <h1>Notificaciones</h1>
          <span class="notification-unread" :class="{ 'is-zero': !page.props.auth.no_leidas }"><strong>{{ page.props.auth.no_leidas }}</strong> sin leer</span>
        </div>
        <p>Novedades de sus solicitudes y actividades.</p>
      </div>
    </div>
    <form class="panel space-filters notification-filters" @submit.prevent="form.get('/notificaciones')">
      <InputField id="lectura" label="Lectura" v-model="form.lectura" placeholder="Todas" :options="[{ value: 'no_leidas', label: 'No leídas' }, { value: 'leidas', label: 'Leídas' }]" :error="form.errors.lectura"/>
      <InputField id="tipo" label="Tipo" v-model="form.tipo" placeholder="Todos" :options="Object.entries(notificationTypes).map(([value, label]) => ({ value, label }))" :error="form.errors.tipo"/>
      <div class="filter-actions">
        <div class="button-group">
          <Link class="text-link" href="/notificaciones">Limpiar filtros</Link>
          <button class="btn btn-secondary" :disabled="form.processing">Aplicar filtros</button>
        </div>
      </div>
    </form>
    <section class="panel notification-panel" aria-label="Avisos recibidos">
      <ul v-if="notificaciones.data.length" class="notification-list">
        <li v-for="n in notificaciones.data" :key="n.id" :class="{ unread: !n.leida_en }">
          <span class="notification-symbol" aria-hidden="true"><Icon :name="typeIcons[n.tipo] || 'bell'"/></span>
          <div class="notification-content">
            <h2><Link :href="`/notificaciones/${n.id}`">{{ n.titulo }}</Link></h2>
            <time :datetime="n.creada_en">{{ dateLabel(n.creada_en) }}</time>
          </div>
          <div class="notification-meta">
            <span class="feature-chips"><span>{{ notificationTypes[n.tipo] }}</span></span>
            <StatusBadge :value="!n.leida_en" domain="lectura"/>
          </div>
        </li>
      </ul>
      <div v-else class="notification-empty"><Icon name="bell"/><h2>No tiene notificaciones.</h2><p>No hay avisos para los filtros seleccionados.</p></div>
      <Pagination :data="notificaciones"/>
    </section>
  </AppLayout>
</template>
