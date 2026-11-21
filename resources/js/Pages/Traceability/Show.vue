<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import TraceabilityChain from '../../Components/TraceabilityChain.vue';
import { operations, verificationResults, explanations, externalRegistration, registrationLabels } from '../../traceability';
import { traceabilityDate } from '../../traceability';
import { useAccess } from '../../security';
const props = defineProps({ evidencia: Object, explorador: String });
const { can } = useAccess();
const form = useForm({});
const run = action => form.post(`/trazabilidad/${props.evidencia.id}/${action}`, { preserveScroll: true });
</script>
<template>
  <AppLayout :title="`Evidencia ${evidencia.id}`">
    <div class="page-heading">
      <div>
        <h1>Evidencia {{ evidencia.id }}</h1>
        <p>{{ operations[evidencia.actividad.accion] }} · {{ traceabilityDate(evidencia.actividad.fecha_hora) }}</p>
      </div>
      <div class="button-group">
        <Link href="/trazabilidad" class="btn btn-secondary">Volver a las evidencias</Link>
        <button v-if="can('trazabilidad.evidencias.verificar')" class="btn btn-primary" :disabled="form.processing" @click="run('verificar')">{{ form.processing ? 'Procesando…' : 'Verificar evidencia' }}</button>
        <button v-if="can('trazabilidad.reintentar') && evidencia.estado === 'PENDIENTE'" class="btn btn-secondary" :disabled="form.processing" @click="run('reintentar')">Reintentar registro</button>
      </div>
    </div>
    <p v-if="form.errors.trazabilidad" class="alert alert-error" role="alert">{{ form.errors.trazabilidad }}</p>

    <TraceabilityChain :evidence="evidencia">
      <template #technical>
        <div><dt>Contexto</dt><dd>{{ evidencia.actividad.reserva_id ? `Reserva ${evidencia.actividad.reserva_id}` : evidencia.actividad.prestamo_id ? `Préstamo ${evidencia.actividad.prestamo_id}` : 'Origen no disponible' }}<template v-if="evidencia.actividad.recurso_id"> · Recurso {{ evidencia.actividad.recurso_id }}</template></dd></div>
        <div><dt>Preparada localmente</dt><dd>{{ traceabilityDate(evidencia.registrada_localmente_en) }}</dd></div>
        <div><dt>{{ externalRegistration(evidencia) === 'registered' ? 'Registrada externamente' : 'Fecha externa informada' }}</dt><dd>{{ evidencia.registrada_externamente_en ? traceabilityDate(evidencia.registrada_externamente_en) : externalRegistration(evidencia) === 'unbacked' ? 'No informada' : 'Pendiente de confirmación' }}</dd></div>
        <div><dt>Registro externo</dt><dd>{{ registrationLabels[externalRegistration(evidencia)] }}</dd></div>
        <div class="span-2"><dt>Referencia externa</dt><dd class="chain-hash">{{ evidencia.referencia_externa || 'Aún no disponible' }}</dd></div>
        <div v-if="evidencia.ultima_verificacion" class="span-2"><dt>Última comprobación</dt><dd>{{ traceabilityDate(evidencia.ultima_verificacion) }} · {{ verificationResults[evidencia.resultado_verificacion] }}. {{ explanations[evidencia.resultado_verificacion] }}</dd></div>
        <div v-if="explorador" class="span-2"><dt>Transacción externa</dt><dd><a :href="explorador" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Consultar transacción en Sepolia<span class="sr-only"> (se abre en una pestaña nueva)</span></a></dd></div>
      </template>
    </TraceabilityChain>
  </AppLayout>
</template>
