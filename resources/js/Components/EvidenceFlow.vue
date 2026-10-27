<script setup>
import Icon from './Icon.vue';
defineProps({ kind: { type: String, default: 'blockchain' } });
</script>
<template>
  <section class="evidence-flow" :class="kind" :aria-label="kind === 'blockchain' ? 'Cómo se comprueba una evidencia' : 'Cómo se interpreta la evidencia ambiental'">
    <div v-if="kind === 'blockchain'"><h2>De la operación a su evidencia</h2><p>Registro local, huella digital y comprobación externa. El estado de cada evidencia indica qué se ha confirmado.</p></div>
    <div v-else><h2>Señales del entorno académico</h2><p>Medición y recepción conservan su contexto. Una señal reciente aporta evidencia; la ausencia de lecturas no confirma un espacio libre.</p></div>
    <div class="evidence-nodes" v-if="kind === 'blockchain'">
      <div class="evidence-node"><span><Icon name="activity"/></span><strong>Operación local</strong></div><span class="evidence-connector" aria-hidden="true"></span>
      <div class="evidence-node"><span><Icon name="network"/></span><strong>Huella SHA-256</strong></div><span class="evidence-connector" aria-hidden="true"></span>
      <div class="evidence-node"><span><Icon name="shield"/></span><strong>Verificación</strong></div>
    </div>
    <div class="evidence-nodes" v-else>
      <div class="evidence-node"><span><Icon name="sensor"/></span><strong>Sensor</strong></div><span class="evidence-connector" aria-hidden="true"></span>
      <div class="evidence-node"><span><Icon name="signal"/></span><strong>Medición</strong></div><span class="evidence-connector" aria-hidden="true"></span>
      <div class="evidence-node"><span><Icon name="building"/></span><strong>Espacio</strong></div>
    </div>
  </section>
</template>
