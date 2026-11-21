<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Pagination from '../../Components/Pagination.vue';
import ReleaseResult from '../../Components/ReleaseResult.vue';
import ReservationState from '../../Components/ReservationState.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { reservationDate } from '../../reservations';
defineProps({ evaluacion: Object, alcance: String, validaciones: Object, eventos: Object });
// Solo presentación: veredicto en lenguaje llano para cada valor exacto del enum.
// NO_DETERMINABLE nunca se presenta como espacio libre.
const verdicts = {
  MANTENER_POR_USO: 'Se mantuvo la reserva porque hubo uso',
  NO_DETERMINABLE: 'No se pudo determinar el uso; la reserva se conservó',
  ALERTAR: 'Se emitió una alerta; la reserva se conservó',
  LIBERAR: 'Se liberó la reserva al no registrarse uso',
};
</script>
<template>
  <AppLayout title="Detalle de evaluación" section="Liberación">
    <div class="page-heading"><div><h1>{{ evaluacion.espacio.nombre }}</h1><p>{{ evaluacion.espacio.codigo }} · {{ reservationDate(evaluacion.fecha_hora) }}</p></div><Link :href="`/liberacion/evaluaciones?alcance=${alcance}`" class="btn btn-secondary">Volver a evaluaciones</Link></div>

    <section class="panel release-verdict" aria-labelledby="release-verdict-title">
      <ReleaseResult :value="evaluacion.resultado"/>
      <h2 id="release-verdict-title" class="release-verdict-title">{{ verdicts[evaluacion.resultado] || evaluacion.resultado }}</h2>
      <p class="release-verdict-reason">{{ evaluacion.motivo }}</p>
      <p v-if="evaluacion.resultado === 'ALERTAR'" class="alert alert-warning" role="status">Requiere atención / condición de posible no utilización. La reserva se conserva.</p>
      <dl class="detail-grid">
        <div><dt>Reserva</dt><dd class="reservation-code">{{ evaluacion.reserva.codigo }}</dd></div>
        <div><dt>Estado actual</dt><dd><ReservationState :value="evaluacion.reserva.estado"/></dd></div>
        <div><dt>Política aplicada</dt><dd>{{ evaluacion.politica.nombre }} · v{{ evaluacion.politica.version }}</dd></div>
      </dl>
      <details class="disclosure"><summary>Cómo leer este resultado</summary><p>Este registro conserva la decisión tomada con la evidencia disponible en su fecha. La disponibilidad también depende del estado operativo, bloqueos y mantenimiento.</p></details>
      <details class="disclosure"><summary>Ver detalles técnicos</summary>
        <dl class="detail-grid"><div><dt>Evaluación</dt><dd>#{{ evaluacion.id }}</dd></div><div><dt>Reserva</dt><dd>#{{ evaluacion.reserva.id }}</dd></div><div><dt>Política</dt><dd>#{{ evaluacion.politica.id }}</dd></div><div><dt>Resultado registrado</dt><dd><code>{{ evaluacion.resultado }}</code></dd></div><div><dt>Fecha y hora</dt><dd>{{ evaluacion.fecha_hora }}</dd></div></dl>
      </details>
    </section>

    <template v-if="alcance === 'administrativas'">
      <section class="panel" aria-labelledby="release-qr-title">
        <div class="panel-heading"><h2 id="release-qr-title">Validaciones QR consideradas</h2><p>{{ validaciones.total ?? validaciones.data.length }} {{ (validaciones.total ?? validaciones.data.length) === 1 ? 'validación' : 'validaciones' }}</p></div>
        <div v-if="validaciones.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Evidencia QR considerada, tabla desplazable">
          <table><caption class="sr-only">Validaciones QR vinculadas a esta evaluación</caption><thead><tr><th>Fecha</th><th>Resultado</th><th>Validado por</th></tr></thead>
            <tbody><tr v-for="(q, i) in validaciones.data" :key="i"><td class="date-cell">{{ reservationDate(q.fecha_hora) }}</td><td><StatusBadge :value="q.resultado"/></td><td>{{ q.actor }}</td></tr></tbody>
          </table>
        </div>
        <p v-else class="hint">No se enlazaron validaciones QR en esta evaluación.</p>
        <Pagination :data="validaciones"/>
      </section>
      <section class="panel" aria-labelledby="release-iot-title">
        <div class="panel-heading"><h2 id="release-iot-title">Lecturas IoT consideradas</h2><p>Sensores del espacio en la ventana evaluada</p></div>
        <div v-if="eventos.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Evidencia IoT considerada, tabla desplazable">
          <table><caption class="sr-only">Lecturas IoT vinculadas a esta evaluación</caption><thead><tr><th>Fecha del evento</th><th>Tipo</th><th>Valor</th><th>Sensor</th></tr></thead>
            <tbody><tr v-for="(e, i) in eventos.data" :key="i"><td class="date-cell">{{ reservationDate(e.fecha_evento) }}<small class="cell-subline">Recibida {{ reservationDate(e.fecha_recepcion) }}</small></td><td>{{ e.tipo === 'PRESENCIA' ? 'Presencia' : 'Ocupación' }}</td><td>{{ e.valor ?? 'Sin valor' }}</td><td><code class="release-sensor">{{ e.sensor }}</code></td></tr></tbody>
          </table>
        </div>
        <p v-else class="hint">No se enlazaron lecturas IoT en esta evaluación.</p>
        <Pagination :data="eventos"/>
      </section>
    </template>
  </AppLayout>
</template>
