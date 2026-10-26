<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import Icon from '../../Components/Icon.vue';
import { dateLabel } from '../../security';
import { notificationTypes } from '../../notifications';
const props = defineProps({ notificaciones: Object, filtros: Object });
const page = usePage();
const form = useForm({ lectura: props.filtros.lectura || '', tipo: props.filtros.tipo || '' });
</script>
<template>
  <AppLayout title="Notificaciones">
    <div class="page-heading"><div><span class="eyebrow">AVISOS PERSONALES</span><h1>Notificaciones</h1><p>Consulte las novedades de sus solicitudes y actividades académicas.</p></div><span class="notification-total">{{ page.props.auth.no_leidas }} sin leer</span></div>
    <form class="panel space-filters notification-filters" @submit.prevent="form.get('/notificaciones')">
      <InputField id="lectura" label="Estado de lectura" v-model="form.lectura" placeholder="Todas" :options="[{ value: 'no_leidas', label: 'No leídas' }, { value: 'leidas', label: 'Leídas' }]" :error="form.errors.lectura"/>
      <InputField id="tipo" label="Tipo de aviso" v-model="form.tipo" placeholder="Todos" :options="Object.entries(notificationTypes).map(([value, label]) => ({ value, label }))" :error="form.errors.tipo"/>
      <button class="btn btn-primary" :disabled="form.processing">Aplicar filtros</button><Link class="btn btn-secondary" href="/notificaciones">Limpiar filtros</Link>
    </form>
    <section class="panel notification-panel" aria-label="Avisos recibidos">
      <ul v-if="notificaciones.data.length" class="notification-list">
        <li v-for="n in notificaciones.data" :key="n.id" :class="{ unread: !n.leida_en }">
          <span class="notification-symbol"><Icon name="bell"/></span>
          <div class="notification-content"><div class="notification-meta"><span>{{ notificationTypes[n.tipo] }}</span><span class="notification-state">{{ n.leida_en ? 'Leída' : 'No leída' }}</span></div><h2><Link :href="`/notificaciones/${n.id}`">{{ n.titulo }}</Link></h2><time :datetime="n.creada_en">{{ dateLabel(n.creada_en) }}</time></div>
          <Link class="btn btn-secondary notification-open" :href="`/notificaciones/${n.id}`" :aria-label="`Abrir: ${n.titulo}`">Abrir<Icon name="arrow"/></Link>
        </li>
      </ul>
      <div v-else class="notification-empty"><Icon name="bell"/><h2>No hay notificaciones</h2><p>No tiene avisos para los filtros seleccionados.</p></div>
      <Pagination :data="notificaciones"/>
    </section>
  </AppLayout>
</template>
