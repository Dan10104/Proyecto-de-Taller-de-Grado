<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ReservationState from '../../Components/ReservationState.vue';
import ReservationActions from '../../Components/ReservationActions.vue';
import { reservationDate } from '../../reservations';
import {useAccess} from '../../security';
const {can}=useAccess();
const props = defineProps({ reserva: Object });
function person(value) { return value ? `${value.nombres} ${value.apellidos}` : ''; }
</script>
<template>
  <AppLayout title="Detalle de reserva" section="Reservas">
    <div class="page-heading">
      <div><span class="eyebrow">RESERVA DE ESPACIO</span><h1 tabindex="-1">{{ reserva.espacio.nombre }}</h1><p class="reservation-code">{{ reserva.codigo }}</p></div>
      <div class="button-group"><Link v-if="can('qr.gestionar') && can('reservas.ver_administrativas')" :href="'/qr/reserva/'+reserva.id" class="btn btn-secondary">Gestionar QR</Link><Link v-if="can('qr.validar_uso_espacio')" :href="'/reservas/'+reserva.id+'/qr'" class="btn btn-primary">Validar uso mediante QR</Link><ReservationState :value="reserva.estado"/><Link href="/reservas" class="btn btn-secondary">Volver a reservas</Link></div>
    </div>
    <section class="panel detail-panel">
      <h2>Datos de la reserva</h2>
      <dl class="detail-grid reservation-details">
        <div><dt>Espacio</dt><dd>{{ reserva.espacio.codigo }} · {{ reserva.espacio.nombre }}</dd></div>
        <div><dt>Ubicación y tipo</dt><dd>{{ reserva.espacio.piso }} · {{ reserva.espacio.tipo }}</dd></div>
        <div><dt>Inicio</dt><dd>{{ reservationDate(reserva.inicio) }}</dd></div>
        <div><dt>Fin</dt><dd>{{ reservationDate(reserva.fin) }}</dd></div>
        <div v-if="reserva.solicitante"><dt>Solicitante</dt><dd>{{ person(reserva.solicitante) }}</dd></div>
        <div><dt>Solicitud registrada</dt><dd>{{ reservationDate(reserva.solicitada_en) }}</dd></div>
        <div class="span-2"><dt>Propósito</dt><dd class="reservation-full-purpose">{{ reserva.proposito }}</dd></div>
      </dl>
      <p class="hint">Fechas y horas de Bolivia.</p>
      <p v-if="reserva.estado === 'PENDIENTE'" class="alert">La solicitud está pendiente de revisión. El horario todavía no está confirmado.</p>
    </section>
    <ReservationActions :reserva="reserva"/>
    <section class="panel detail-panel" aria-labelledby="historial-reserva">
      <h2 id="historial-reserva">Seguimiento de la reserva</h2>
      <ol class="reservation-history">
        <li><strong>Solicitud registrada</strong><time>{{ reservationDate(reserva.solicitada_en) }}</time></li>
        <li v-if="reserva.resuelta_en"><strong>{{ reserva.estado === 'RECHAZADA' ? 'Solicitud rechazada' : 'Reserva aprobada' }}</strong><time>{{ reservationDate(reserva.resuelta_en) }}</time><span>{{ person(reserva.resuelta_por) }}</span><p v-if="reserva.motivo_rechazo" class="reservation-full-purpose">{{ reserva.motivo_rechazo }}</p></li>
        <li v-if="reserva.cancelada_en"><strong>Reserva cancelada</strong><time>{{ reservationDate(reserva.cancelada_en) }}</time><span>{{ person(reserva.cancelada_por) }}</span><p v-if="reserva.motivo_cancelacion" class="reservation-full-purpose">{{ reserva.motivo_cancelacion }}</p></li>
      </ol>
    </section>
  </AppLayout>
</template>
