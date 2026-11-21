<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { dateLabel } from '../../security';
import { notificationTypes } from '../../notifications';
const props = defineProps({ notificacion: Object, enlace: Object });
const page = usePage();
const readAt = ref(props.notificacion.leida_en), pending = ref(false), error = ref('');
async function markRead() {
  if (readAt.value || pending.value || document.visibilityState !== 'visible') return;
  const id = props.notificacion.id;
  pending.value = true;
  error.value = '';
  try {
    const cookie = document.cookie.split('; ').find(item => item.startsWith('XSRF-TOKEN='));
    const response = await fetch(`/notificaciones/${id}/leer`, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-XSRF-TOKEN': cookie ? decodeURIComponent(cookie.slice(11)) : '' } });
    if (!response.ok) throw new Error('Lectura no confirmada');
    const data = await response.json();
    if (props.notificacion.id === id) {
      readAt.value = data.leida_en;
      page.props.auth.no_leidas = data.no_leidas;
    }
  } catch {
    if (props.notificacion.id === id) error.value = 'No se pudo confirmar la lectura. Compruebe su conexión y sus permisos antes de reintentar.';
  } finally { pending.value = false; }
}
watch(() => props.notificacion.id, () => { readAt.value = props.notificacion.leida_en; error.value = ''; markRead(); }, { flush: 'post' });
onMounted(() => { markRead(); document.addEventListener('visibilitychange', markRead); });
onUnmounted(() => document.removeEventListener('visibilitychange', markRead));
</script>
<template>
  <AppLayout title="Detalle del aviso" section="Notificaciones">
    <Link class="back-link" href="/notificaciones">Volver a notificaciones</Link>
    <article class="panel notification-detail">
      <h1>{{ notificacion.titulo }}</h1>
      <p class="notification-message">{{ notificacion.mensaje }}</p>
      <div class="notification-context">
        <Link v-if="enlace" class="btn btn-primary" :href="enlace.url">{{ enlace.label }}</Link>
        <p v-else class="hint">El detalle relacionado no está disponible con sus permisos actuales.</p>
      </div>
      <div v-if="error" class="alert alert-error notification-retry" role="alert"><p>{{ error }}</p><button class="btn btn-secondary" :disabled="pending" @click="markRead">Reintentar lectura</button></div>
      <dl class="detail-grid notification-facts">
        <div><dt>Tipo</dt><dd><span class="feature-chips"><span>{{ notificationTypes[notificacion.tipo] }}</span></span></dd></div>
        <div><dt>Recibida</dt><dd><time :datetime="notificacion.creada_en">{{ dateLabel(notificacion.creada_en) }}</time></dd></div>
        <div>
          <dt>Lectura</dt>
          <dd>
            <StatusBadge :value="!readAt" domain="lectura"/>
            <small v-if="readAt" class="cell-subline">{{ dateLabel(readAt) }}</small>
            <small v-else-if="pending" class="cell-subline" role="status">Confirmando lectura…</small>
          </dd>
        </div>
      </dl>
    </article>
  </AppLayout>
</template>
