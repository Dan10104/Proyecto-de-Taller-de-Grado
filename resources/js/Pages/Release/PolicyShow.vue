<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useAccess } from '../../security';
const props = defineProps({ politica: Object, espacios: Object });
const { can } = useAccess();
const form = useForm({ activa: !props.politica.activa });
function state() { form.activa = !props.politica.activa; form.patch(`/liberacion/politicas/${props.politica.id}/estado`); }
</script>
<template>
  <AppLayout title="Detalle de política" section="Liberación">
    <div class="page-heading">
      <div><h1>{{ politica.nombre }}</h1><p class="release-heading-state"><StatusBadge :value="politica.activa ? 'ACTIVO' : 'INACTIVO'" :label="politica.activa ? 'Activa' : 'Inactiva'"/><span>Versión {{ politica.version }} · Reglas inmutables; sus evaluaciones se conservan.</span></p></div>
      <div class="button-group"><Link href="/liberacion/politicas" class="btn btn-secondary">Volver</Link><Link v-if="can('liberacion.politicas.gestionar')" :href="`/liberacion/politicas/crear?desde=${politica.id}`" class="btn btn-primary">Crear nueva versión</Link></div>
    </div>
    <FormErrors :errors="form.errors"/>

    <section class="panel">
      <div class="panel-heading"><h2>Reglas de evaluación</h2><p>{{ politica.modo === 'ALERTA' ? 'Modo alerta' : 'Modo automático' }}</p></div>
      <dl class="detail-grid release-rules">
        <div><dt>Tolerancia</dt><dd>{{ politica.tolerancia_min }} minutos desde el inicio<small>Espera tras el inicio antes de evaluar la reserva.</small></dd></div>
        <div><dt>Modo</dt><dd>{{ politica.modo === 'ALERTA' ? 'Alerta: conserva la reserva' : 'Automática: liberación condicionada' }}<small>{{ politica.modo === 'ALERTA' ? 'Avisa sin liberar la reserva.' : 'Puede liberar la reserva si no hay uso.' }}</small></dd></div>
        <div><dt>QR obligatorio</dt><dd>{{ politica.requiere_qr ? 'Sí' : 'No' }}<small>Validación del uso escaneando el código del espacio.</small></dd></div>
        <div><dt>IoT obligatorio</dt><dd>{{ politica.usa_iot ? 'Sí' : 'No' }}<small>Lecturas de los sensores del espacio.</small></dd></div>
        <div><dt>Antigüedad máxima IoT</dt><dd>{{ politica.usa_iot ? `${politica.max_antiguedad_lectura_min} minutos` : 'No aplica' }}<small>Solo cuentan lecturas dentro de este margen.</small></dd></div>
      </dl>
      <div class="form-actions"><Link v-if="can('liberacion.evaluaciones.ver_administrativas')" :href="`/liberacion/evaluaciones?politica_id=${politica.id}&alcance=administrativas`" class="btn btn-secondary">Ver evaluaciones</Link><ConfirmAction v-if="can('liberacion.politicas.gestionar') && !form.processing" :label="politica.activa ? 'Desactivar política' : 'Activar política'" :message="politica.activa ? 'Se detendrán las decisiones futuras de esta versión. La historia se conserva.' : 'Solo se permite activar si los espacios asignados no tienen reservas aprobadas vigentes o futuras.'" @confirm="state"/></div>
    </section>

    <details class="disclosure release-spaces" :open="espacios.current_page > 1">
      <summary>Espacios asignados ({{ espacios.total ?? espacios.data.length }})</summary>
      <p v-if="!espacios.data.length">Esta versión todavía no está asignada a un espacio.</p>
      <ul v-else class="release-space-list"><li v-for="s in espacios.data" :key="s.recurso_id"><strong>{{ s.recurso.nombre }}</strong><span class="reservation-code">{{ s.recurso.codigo }}</span></li></ul>
      <Pagination :data="espacios"/>
      <div v-if="can('liberacion.politicas.gestionar')"><Link href="/liberacion/asignaciones" class="btn btn-secondary">Gestionar asignaciones</Link></div>
    </details>
  </AppLayout>
</template>
