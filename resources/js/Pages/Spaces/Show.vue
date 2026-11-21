<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SpaceState from '../../Components/SpaceState.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import ConfirmAction from '../../Components/ConfirmAction.vue';
import SpaceIllustration from '../../Components/SpaceIllustration.vue';
import Icon from '../../Components/Icon.vue';
import { statesFor, focusError, localPeriod } from '../../spaces';
import { useAccess } from '../../security';
const props = defineProps({ espacio: Object, bloqueos: Object, estados: Array });
const { can } = useAccess();
const state = useForm({ estado: props.espacio.estado });
const block = useForm({ inicio: '', fin: '', motivo: '' });
function saveBlock() { block.post(`/espacios/${props.espacio.recurso_id}/bloqueos`, { preserveScroll: true, onError: focusError, onSuccess: () => block.reset() }); }
function deactivate(id) { router.patch(`/espacios/${props.espacio.recurso_id}/bloqueos/${id}/desactivar`, {}, { preserveScroll: true }); }
</script>
<template>
  <AppLayout :title="espacio.recurso.nombre" section="Espacios">
    <div class="page-heading">
      <div>
        <div class="space-title">
          <h1>{{ espacio.recurso.nombre }}</h1>
          <SpaceState :value="espacio.estado"/>
        </div>
        <p><span class="reservation-code">{{ espacio.recurso.codigo }}</span> · {{ espacio.tipo.nombre }} · {{ espacio.piso.nombre }}</p>
      </div>
      <div class="button-group">
        <Link href="/espacios" class="btn btn-secondary">Volver al catálogo</Link>
        <Link v-if="can('qr.gestionar')" :href="'/qr/recurso/'+espacio.recurso_id" class="btn btn-secondary"><Icon name="qr"/>Gestionar QR</Link>
        <Link v-if="can('espacios.gestionar')" :href="`/espacios/${espacio.recurso_id}/editar`" class="btn btn-primary"><Icon name="edit"/>Editar espacio</Link>
      </div>
    </div>

    <section class="panel detail-panel space-overview" aria-labelledby="space-info-heading">
      <SpaceIllustration class="space-overview-art" :kind="espacio.tipo.nombre + ' ' + espacio.recurso.nombre"/>
      <div class="space-overview-body">
        <div class="panel-heading">
          <h2 id="space-info-heading">Información del espacio</h2>
          <div v-if="can('incidencias.ver') || can('incidencias.gestionar')" class="button-group operational-links">
            <Link v-if="can('incidencias.ver')" :href="'/incidencias?recurso_id='+espacio.recurso_id" class="text-link">Ver incidencias del espacio</Link>
            <Link v-if="can('incidencias.gestionar')" :href="'/incidencias/crear?recurso_id='+espacio.recurso_id" class="text-link">Registrar incidencia o mantenimiento</Link>
          </div>
        </div>
        <dl class="detail-grid">
          <div>
            <dt>Capacidad</dt>
            <dd>{{ espacio.capacidad }} personas</dd>
          </div>
          <div>
            <dt>Piso</dt>
            <dd>{{ espacio.piso.nombre }}</dd>
          </div>
          <div>
            <dt>Tipo de espacio</dt>
            <dd>{{ espacio.tipo.nombre }}</dd>
          </div>
          <div>
            <dt>Referencia de ubicación</dt>
            <dd>{{ espacio.referencia_ubicacion || 'Sin referencia adicional' }}</dd>
          </div>
          <div class="span-2">
            <dt>Características</dt>
            <dd class="feature-chips">
              <span v-for="feature in espacio.caracteristicas" :key="feature.id">{{ feature.nombre }}</span>
              <span v-if="!espacio.caracteristicas.length">Sin características registradas</span>
            </dd>
          </div>
        </dl>
        <p class="hint">El estado general y los bloqueos temporales se administran por separado.</p>
      </div>
    </section>

    <section v-if="can('espacios.cambiar_estado') || can('espacios.gestionar_bloqueos')" class="panel space-operations" aria-labelledby="space-ops-heading">
      <div class="panel-heading">
        <h2 id="space-ops-heading">Gestión operativa</h2>
      </div>
      <form v-if="can('espacios.cambiar_estado')" class="space-state-form" @submit.prevent="state.patch(`/espacios/${espacio.recurso_id}/estado`, { preserveScroll: true, onError: focusError })">
        <h3 class="sr-only">Estado operativo</h3>
        <FormErrors :errors="state.errors"/>
        <div class="inline-form">
          <InputField id="estado-operativo" label="Estado operativo" v-model="state.estado" :options="statesFor(estados)" required :error="state.errors.estado"/>
          <button class="btn btn-secondary" :disabled="state.processing">Actualizar estado</button>
        </div>
      </form>
      <details v-if="can('espacios.gestionar_bloqueos')" class="disclosure">
        <summary>Registrar bloqueo temporal</summary>
        <form class="space-block-form" @submit.prevent="saveBlock">
          <p class="hint">Queda registrado con su motivo y con usted como responsable. Fechas y horas de Bolivia.</p>
          <FormErrors :errors="block.errors"/>
          <div class="form-grid">
            <InputField id="bloqueo-inicio" label="Inicio del bloqueo" v-model="block.inicio" type="datetime-local" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="block.errors.inicio"/>
            <InputField id="bloqueo-fin" label="Fin del bloqueo" v-model="block.fin" type="datetime-local" required min="1000-01-01T00:00" max="9999-12-31T23:59" :error="block.errors.fin"/>
            <InputField class="span-2" id="bloqueo-motivo" label="Motivo" v-model="block.motivo" type="textarea" required maxlength="500" :error="block.errors.motivo"/>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" :disabled="block.processing">Registrar bloqueo</button>
          </div>
        </form>
      </details>
    </section>

    <section class="panel">
      <div class="panel-heading">
        <h2>{{ can('espacios.gestionar_bloqueos') ? 'Historial de bloqueos' : 'Bloqueos actuales y próximos' }}</h2>
        <p>Un bloqueo afecta solo a los periodos que se solapan con su horario.</p>
      </div>
      <div v-if="bloqueos.data.length" class="table-scroll" role="region" tabindex="0" aria-label="Bloqueos del espacio desplazables">
        <table>
          <caption class="sr-only">Bloqueos del espacio</caption>
          <thead>
            <tr>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Estado del bloqueo</th>
              <template v-if="can('espacios.gestionar_bloqueos')">
                <th>Motivo</th>
                <th>Registrado por</th>
                <th>Acción</th>
              </template>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in bloqueos.data" :key="b.id">
              <td class="date-cell">{{ localPeriod(b.inicio) }}</td>
              <td class="date-cell">{{ localPeriod(b.fin) }}</td>
              <td>
                <StatusBadge :value="b.activo" domain="bloqueo" :label="b.activo ? 'Activo' : 'Desactivado'"/>
              </td>
              <template v-if="can('espacios.gestionar_bloqueos')">
                <td class="wrap-cell">{{ b.motivo }}</td>
                <td>{{ b.responsable.nombres }} {{ b.responsable.apellidos }}</td>
                <td>
                  <ConfirmAction v-if="b.activo" label="Desactivar bloqueo" message="El bloqueo dejará de impedir disponibilidad. Se conservarán su periodo, motivo y responsable." @confirm="deactivate(b.id)"/>
                  <span v-else class="hint">Historial conservado</span>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="calendar"/>
        <h3>Sin bloqueos para mostrar</h3>
        <p>Consulte la disponibilidad para un periodo específico.</p>
      </div>
      <Pagination :data="bloqueos"/>
    </section>
  </AppLayout>
</template>
