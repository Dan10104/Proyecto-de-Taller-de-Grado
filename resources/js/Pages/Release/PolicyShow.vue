<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import Pagination from '../../Components/Pagination.vue';
import { useAccess } from '../../security';
const props = defineProps({ politica: Object, espacios: Object });
const { can } = useAccess();
const form = useForm({ activa: !props.politica.activa });
function state() { form.activa = !props.politica.activa; form.patch(`/liberacion/politicas/${props.politica.id}/estado`); }
</script>
<template>
  <AppLayout title="Detalle de política" section="Liberación">
    <div class="page-heading"><div><span class="eyebrow">VERSIÓN {{ politica.version }} · {{ politica.activa ? 'ACTIVA' : 'INACTIVA' }}</span><h1>{{ politica.nombre }}</h1><p>Las reglas de esta versión son inmutables y sus evaluaciones se conservan.</p></div><Link href="/liberacion/politicas" class="btn btn-secondary">Volver</Link></div>
    <FormErrors :errors="form.errors"/>
    <section class="panel form-panel"><h2>Reglas de evaluación</h2><dl class="detail-grid"><div><dt>Tolerancia</dt><dd>{{ politica.tolerancia_min }} minutos desde el inicio</dd></div><div><dt>Modo</dt><dd>{{ politica.modo === 'ALERTA' ? 'Alerta: conserva la reserva' : 'Automática: liberación condicionada' }}</dd></div><div><dt>QR obligatorio</dt><dd>{{ politica.requiere_qr ? 'Sí' : 'No' }}</dd></div><div><dt>IoT obligatorio</dt><dd>{{ politica.usa_iot ? 'Sí' : 'No' }}</dd></div><div><dt>Antigüedad máxima IoT</dt><dd>{{ politica.usa_iot ? `${politica.max_antiguedad_lectura_min} minutos` : 'No aplica' }}</dd></div></dl>
      <div class="form-actions"><Link v-if="can('liberacion.politicas.gestionar')" :href="`/liberacion/politicas/crear?desde=${politica.id}`" class="btn btn-primary">Crear nueva versión</Link><Link v-if="can('liberacion.evaluaciones.ver_administrativas')" :href="`/liberacion/evaluaciones?politica_id=${politica.id}&alcance=administrativas`" class="btn btn-secondary">Ver evaluaciones</Link><ConfirmAction v-if="can('liberacion.politicas.gestionar') && !form.processing" :label="politica.activa ? 'Desactivar política' : 'Activar política'" :message="politica.activa ? 'Se detendrán las decisiones futuras de esta versión. La historia se conserva.' : 'Solo se permite activar si los espacios asignados no tienen reservas aprobadas vigentes o futuras.'" @confirm="state"/></div>
    </section>
    <section class="panel form-panel"><h2>Espacios asignados</h2><p v-if="!espacios.data.length">Esta versión todavía no está asignada a un espacio.</p><ul v-else><li v-for="s in espacios.data" :key="s.recurso_id">{{ s.recurso.codigo }} · {{ s.recurso.nombre }}</li></ul><Pagination :data="espacios"/><Link v-if="can('liberacion.politicas.gestionar')" href="/liberacion/asignaciones" class="btn btn-secondary">Gestionar asignaciones</Link></section>
  </AppLayout>
</template>
