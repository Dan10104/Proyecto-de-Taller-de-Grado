<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
import { dateLabel } from '../../security';
defineProps({ actividad: Object });
</script>
<template>
  <AppLayout title="Detalle de actividad">
    <div class="page-heading">
      <div>
        <h1>{{ actividad.accion_label }}</h1>
        <p>Registro #{{ actividad.id }} · solo lectura</p>
      </div>
      <Link href="/actividad" class="btn btn-secondary">Volver a la bitácora</Link>
    </div>

    <section class="panel">
      <div class="panel-heading"><h2>Qué ocurrió</h2></div>
      <dl class="detail-grid">
        <div>
          <dt>Resultado</dt>
          <dd><StatusBadge :value="actividad.resultado"/></dd>
        </div>
        <div>
          <dt>Responsable</dt>
          <dd>
            <template v-if="actividad.actor">
              <Link v-if="actividad.actor.url" :href="actividad.actor.url" class="text-link">{{ actividad.actor.label }}</Link>
              <span v-else>{{ actividad.actor.label }}</span>
            </template>
            <span v-else-if="actividad.proceso_responsable">Proceso automático</span>
            <span v-else>—</span>
          </dd>
        </div>
        <div>
          <dt>Fecha y hora</dt>
          <dd><time :datetime="actividad.fecha_hora">{{ dateLabel(actividad.fecha_hora) }}</time></dd>
        </div>
        <div>
          <dt>Criticidad</dt>
          <dd><div class="feature-chips"><span>{{ actividad.critica ? 'Crítica' : 'No crítica' }}</span></div></dd>
        </div>
        <div v-if="actividad.usuario_afectado">
          <dt>Usuario afectado</dt>
          <dd>
            <Link v-if="actividad.usuario_afectado.url" :href="actividad.usuario_afectado.url" class="text-link">{{ actividad.usuario_afectado.label }}</Link>
            <span v-else>{{ actividad.usuario_afectado.label }}</span>
          </dd>
        </div>
      </dl>
    </section>

    <section class="panel">
      <div class="panel-heading">
        <h2>Referencias</h2>
        <p v-if="actividad.referencias.length">{{ actividad.referencias.length }} {{ actividad.referencias.length === 1 ? 'asociada' : 'asociadas' }}</p>
      </div>
      <ul v-if="actividad.referencias.length" class="audit-links">
        <li v-for="ref in actividad.referencias" :key="ref.campo">
          <Icon name="layers"/>
          <Link v-if="ref.url" :href="ref.url" class="text-link">{{ ref.label }}</Link>
          <span v-else>{{ ref.label }}</span>
        </li>
      </ul>
      <p v-else class="hint">Sin referencias asociadas.</p>
    </section>

    <details class="disclosure audit-technical">
      <summary>Ver detalles técnicos</summary>
      <dl class="detail-grid">
        <div><dt>ID técnico</dt><dd>{{ actividad.id }}</dd></div>
        <div><dt>Zona horaria</dt><dd>America/La_Paz</dd></div>
        <div v-if="actividad.accion"><dt>Código de acción</dt><dd><code>{{ actividad.accion }}</code></dd></div>
        <div v-if="actividad.proceso_responsable"><dt>Proceso responsable</dt><dd><code>{{ actividad.proceso_responsable }}</code></dd></div>
        <div v-if="actividad.evidencia">
          <dt>Evidencia de trazabilidad</dt>
          <dd>
            <Link v-if="actividad.evidencia.url" :href="actividad.evidencia.url" class="text-link">Ver evidencia de trazabilidad</Link>
            <span v-else>{{ actividad.evidencia.existe ? 'Evidencia asociada. El acceso requiere permiso vigente de trazabilidad.' : 'Sin evidencia asociada.' }}</span>
          </dd>
        </div>
      </dl>
      <div class="audit-description">
        <h3>Descripción</h3>
        <p>{{ actividad.descripcion || 'Sin descripción disponible para consulta segura.' }}</p>
      </div>
      <p class="hint">Los IDs conservan la referencia histórica; los nombres autorizados corresponden a los datos actuales. Este registro no modifica la operación ni verifica Blockchain. Puede incluir una referencia técnica adicional.</p>
    </details>
  </AppLayout>
</template>
