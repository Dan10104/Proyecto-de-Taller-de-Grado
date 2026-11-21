<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import { operations, verificationResults, explanations, traceabilityDate } from '../traceability';

const props = defineProps({ evidence: Object, entries: { type: Array, default: () => [] } });
const current = computed(() => props.evidence || props.entries[0]);

// Hechos de la evidencia, tal como llegan del servidor. Nada se infiere más allá de ellos.
// El estado «REGISTRADA» por sí solo no basta: hace falta la fecha de confirmación externa y, cuando
// el servidor la entrega, también la referencia. Sin ese respaldo no se afirma el registro.
const stateRegistered = computed(() => current.value?.estado === 'REGISTRADA');
const backed = computed(() => Boolean(current.value?.registrada_externamente_en) && (current.value?.referencia_externa === undefined || Boolean(current.value?.referencia_externa)));
const registered = computed(() => stateRegistered.value && backed.value);
const unbacked = computed(() => stateRegistered.value && !backed.value);
const verified = computed(() => Boolean(current.value?.ultima_verificacion));
const result = computed(() => (verified.value ? current.value.resultado_verificacion : null));
const confirmedValid = computed(() => registered.value && result.value === 'VALIDO');

// Primera capa: titular y una frase en lenguaje llano.
const verdict = computed(() => {
  if (!current.value) return null;
  const hint = props.evidence ? ' Seleccione «Verificar evidencia» para comprobar la información disponible.' : '';
  if (!verified.value && unbacked.value) {
    return { tone: 'pending', icon: 'alert', title: 'Registro externo sin respaldo', text: `El estado indica un registro externo, pero falta su confirmación o su referencia.${hint}` };
  }
  if (!verified.value) {
    return registered.value
      ? { tone: 'registered', icon: 'cube', title: 'Evidencia registrada, sin verificar', text: `La operación cuenta con un registro externo confirmado, pero aún no se ha verificado.${hint}` }
      : { tone: 'pending', icon: 'clock', title: 'Registro externo pendiente', text: `La evidencia está preparada, pero aún no tiene confirmación externa ni se ha verificado.${hint}` };
  }
  if (confirmedValid.value) return { tone: 'valid', icon: 'checkCircle', title: 'Evidencia verificada', text: 'La operación cuenta con un registro de trazabilidad válido.' };
  if (result.value === 'INCONSISTENTE') return { tone: 'danger', icon: 'alert', title: 'Se detectó una inconsistencia', text: explanations.INCONSISTENTE };
  if (result.value === 'NO_VERIFICABLE') return { tone: 'neutral', icon: 'info', title: 'No se pudo verificar', text: explanations.NO_VERIFICABLE };
  return { tone: 'pending', icon: 'clock', title: 'Verificación incompleta', text: explanations.PENDIENTE };
});

// Etapa de verificación: solo es verde cuando hay registro externo y un resultado válido.
const verification = computed(() => {
  if (!verified.value) return { tone: 'neutral', icon: 'clock', lit: false };
  if (confirmedValid.value) return { tone: 'green', icon: 'checkCircle', lit: true };
  if (result.value === 'INCONSISTENTE') return { tone: 'danger', icon: 'xCircle', lit: true };
  if (result.value === 'NO_VERIFICABLE') return { tone: 'neutral', icon: 'info', lit: false };
  return { tone: 'amber', icon: 'clock', lit: false };
});

// Solo presentación: una actividad sin reserva ni préstamo no muestra un identificador vacío.
function origin(a) {
  if (a.reserva_id) return { icon: 'calendar', value: `Reserva ${a.reserva_id}` };
  if (a.prestamo_id) return { icon: 'monitor', value: `Préstamo ${a.prestamo_id}` };
  return { icon: 'info', value: 'Origen no disponible' };
}

const stages = computed(() => {
  const e = current.value;
  if (!e) return [];
  const a = e.actividad;
  return [
    { key: 'origen', stage: 'Origen', tone: 'cyan', lit: true, ...origin(a) },
    { key: 'operacion', stage: 'Operación', tone: 'blue', lit: true, icon: 'activity', value: operations[a.accion], note: traceabilityDate(a.fecha_hora) },
    { key: 'huella', stage: 'Huella digital', tone: 'indigo', lit: true, icon: 'hash', value: 'Generada', note: e.algoritmo_hash },
    {
      key: 'registro', stage: 'Registro externo', tag: true,
      tone: registered.value ? 'violet' : 'amber', lit: registered.value, icon: registered.value ? 'check' : unbacked.value ? 'alert' : 'clock',
      value: registered.value ? 'Registrada' : unbacked.value ? 'Sin respaldo' : 'Pendiente',
      note: registered.value ? traceabilityDate(e.registrada_externamente_en) : 'Sin confirmación externa',
    },
    {
      key: 'verificacion', stage: 'Verificación', tag: true, ...verification.value,
      value: verified.value ? verificationResults[result.value] : 'Sin verificar',
      note: verified.value ? traceabilityDate(e.ultima_verificacion) : null,
    },
  ];
});
</script>

<template>
  <section class="tech-surface blockchain-chain" :aria-labelledby="current ? 'chain-title' : undefined" :aria-label="current ? undefined : 'Recorrido de la evidencia'">
    <template v-if="current">
      <div v-if="!evidence" class="chain-head">
        <Link class="chain-open" :href="`/trazabilidad/${current.id}`">Abrir evidencia #{{ current.id }}</Link>
      </div>

      <div class="chain-verdict" :class="`is-${verdict.tone}`">
        <span class="chain-verdict-mark" aria-hidden="true"><Icon :name="verdict.icon"/></span>
        <div>
          <h2 id="chain-title">{{ verdict.title }}</h2>
          <p role="status">{{ verdict.text }}</p>
        </div>
      </div>

      <ol class="chain-path" aria-label="Etapas de la evidencia">
        <li v-for="(s, i) in stages" :key="s.key" class="chain-node" :class="[`chain-${s.tone}`, { 'is-unlit': !s.lit }]">
          <span v-if="stages[i + 1]" class="chain-link" :class="[`chain-${stages[i + 1].tone}`, { 'is-unlit': !stages[i + 1].lit }]" aria-hidden="true"></span>
          <span class="chain-cube" aria-hidden="true">
            <svg viewBox="-40 -22 80 88" focusable="false">
              <path class="chain-cube-body" d="M0-20 40 0V44L0 64-40 44V0Z"/>
              <path d="M0-20 40 0 0 20-40 0Z" fill="currentColor" fill-opacity=".28"/>
              <path d="M-40 0 0 20V64L-40 44Z" fill="currentColor" fill-opacity=".14"/>
              <path d="M40 0 0 20V64L40 44Z" fill="currentColor" fill-opacity=".06"/>
              <path class="chain-cube-edge" d="M0-20 40 0V44L0 64-40 44V0ZM-40 0 0 20 40 0M0 20V64" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
              <text class="chain-cube-number" transform="matrix(.894 .447 -.894 .447 0 0)" y="10.5" font-size="30" font-weight="700" text-anchor="middle">{{ i + 1 }}</text>
            </svg>
          </span>
          <span class="chain-stage">{{ s.stage }}</span>
          <strong class="chain-value" :class="{ 'chain-tag': s.tag }"><Icon :name="s.icon"/><span>{{ s.value }}</span></strong>
          <small v-if="s.note" class="chain-meta">{{ s.note }}</small>
        </li>
      </ol>

      <details class="disclosure chain-technical">
        <summary>Ver detalles técnicos</summary>
        <dl class="detail-grid">
          <div class="span-2"><dt>Hash de la evidencia</dt><dd class="chain-hash">{{ current.hash }}</dd></div>
          <div><dt>Algoritmo</dt><dd>{{ current.algoritmo_hash }}</dd></div>
          <div><dt>Versión del formato</dt><dd>{{ current.version_canonica }}</dd></div>
          <div><dt>Actividad</dt><dd>{{ current.registro_actividad_id }}</dd></div>
          <div><dt>Estado recibido</dt><dd>{{ current.estado }}</dd></div>
          <slot name="technical"/>
        </dl>
      </details>

      <p class="chain-foot">Los bloques representan las etapas de una sola evidencia; no bloques enlazados entre operaciones ni una confirmación de red.</p>
    </template>
    <div v-else class="chain-empty">
      <Icon name="cube"/>
      <p>El recorrido de la evidencia aparecerá aquí con el primer registro disponible.</p>
    </div>
  </section>
</template>
