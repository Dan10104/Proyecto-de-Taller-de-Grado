<script setup>
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import ReportPeriodFields from '../../Components/ReportPeriodFields.vue';
import AccessibleChart from '../../Components/AccessibleChart.vue';
import StateBreakdown from '../../Components/StateBreakdown.vue';
import Icon from '../../Components/Icon.vue';
import { reservationDate } from '../../reservations';
import { statusOf } from '../../status';
import { useAccess } from '../../security';

const props = defineProps({ vista: String, vistas: Array, filtros: Object, generado_en: String, zona: String, tarjetas: Array, graficos: Array, proximas: Array, iot: Object });
const filters = ref({ ...props.filtros });
const selectedView = ref(props.vista), loading = ref(false);
const page = usePage();
const { can } = useAccess();
function refresh() { const query = { vista: selectedView.value, ...filters.value }; if (query.periodo !== 'personalizado') { delete query.fecha_inicio; delete query.fecha_fin; } router.get('/dashboard', query, { onStart: () => loading.value = true, onFinish: () => loading.value = false }); }

// Presentación de cada indicador calculado por el servidor: icono, destino y si pide atención.
// No se añade ni se recalcula ningún valor; solo se ordena lo que llega en «tarjetas».
const present = {
  A1: { icon: 'calendar', href: '/reservas?alcance=administrativas&estado=PENDIENTE', lead: true },
  A5: { icon: 'exchange', href: '/prestamos?alcance=administrativos&estado=PENDIENTE', lead: true },
  A7: { icon: 'clock', href: '/prestamos?alcance=administrativos&vencidos=1', lead: true, alert: 'danger' },
  A9: { icon: 'tool', href: '/incidencias?estado=ABIERTA', lead: true, alert: 'warning' },
  // A2 y P2 cuentan un intervalo (en curso / próximas) que el listado no puede filtrar: se muestran sin enlace.
  A2: { icon: 'calendar', lead: true },
  A6: { icon: 'exchange', href: '/prestamos?alcance=administrativos&estado=ENTREGADO', lead: true },
  A3: { icon: 'clock' },
  A4: { icon: 'building', breakdown: true, href: '/espacios' },
  A8: { icon: 'monitor', breakdown: true, href: '/equipamientos' },
  A10: { iot: true },
  P1: { icon: 'calendar', href: '/reservas?alcance=propias&estado=PENDIENTE', lead: true },
  P2: { icon: 'calendar', lead: true },
  P4: { icon: 'exchange', href: '/prestamos?alcance=propios&estado=APROBADO', lead: true },
  P5: { icon: 'monitor', href: '/prestamos?alcance=propios&estado=ENTREGADO', lead: true },
  P6: { icon: 'clock', href: '/prestamos?alcance=propios&vencidos=1', lead: true, alert: 'danger' },
  P7: { icon: 'bell', href: '/notificaciones?lectura=no_leidas', lead: true },
  P3: { icon: 'exchange', href: '/prestamos?alcance=propios&estado=PENDIENTE' },
};
const cards = computed(() => props.tarjetas.map(card => ({ ...card, ...(present[card.id] || { icon: 'chart' }) })));
const lead = computed(() => cards.value.filter(card => card.lead));
const breakdowns = computed(() => cards.value.filter(card => card.breakdown));
const more = computed(() => cards.value.filter(card => !card.lead && !card.breakdown && !card.iot));
const tone = card => (card.alert && card.valor > 0 ? `is-${card.alert}` : '');
// Desglose que el servidor envía con el indicador (p. ej. vigentes y vencidos): se muestra, no se recalcula.
const detailLabels = { VIGENTES: ['vigente', 'vigentes'], VENCIDOS: ['vencido', 'vencidos'], INCIDENCIA: ['incidencia', 'incidencias'], MANTENIMIENTO: ['mantenimiento', 'mantenimientos'] };
const detail = card => Object.entries(card.desglose || {}).map(([key, value]) => {
  const forms = detailLabels[key];
  return `${value.toLocaleString('es-BO')} ${forms ? forms[value === 1 ? 0 : 1] : statusOf(key).label.toLowerCase()}`;
});
const sameDay = r => String(r.inicio).split(' ')[0] === String(r.fin).split(' ')[0];

const groups = computed(() => {
  const pick = (...ids) => props.graficos.filter(chart => ids.includes(chart.id));
  return [
    { id: 'reservas', label: 'Reservas', charts: pick('G5', 'G1') },
    { id: 'prestamos', label: 'Préstamos', charts: pick('G6', 'G2') },
    { id: 'recursos', label: 'Equipos e incidencias', charts: pick('G3', 'G4') },
  ].filter(group => group.charts.length);
});
const tab = ref(null);
const activeGroup = computed(() => groups.value.find(group => group.id === tab.value) || groups.value[0]);
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="page-heading">
      <div>
        <h1>{{ vista === 'administrativo' ? 'Dashboard' : 'Mi resumen' }}</h1>
        <p role="status">Actualizado {{ generado_en }}</p>
      </div>
      <form class="dashboard-filter" @submit.prevent="refresh">
        <fieldset class="report-filter-lock" :disabled="loading">
          <label v-if="vistas.length > 1" class="report-field" for="dashboard-vista"><span>Vista</span><select id="dashboard-vista" v-model="selectedView"><option v-for="v in vistas" :key="v" :value="v">{{ v === 'personal' ? 'Personal' : 'Institucional' }}</option></select></label>
          <ReportPeriodFields v-if="selectedView === 'administrativo'" v-model="filters" prefix="dashboard-periodo"/>
          <button class="btn btn-secondary" :disabled="loading">{{ loading ? 'Actualizando…' : 'Actualizar' }}</button>
        </fieldset>
      </form>
    </div>
    <FormErrors :errors="page.props.errors"/>

    <section v-if="lead.length" class="numerals" aria-label="Indicadores principales" :aria-busy="loading">
      <component :is="card.href ? Link : 'div'" v-for="card in lead" :key="card.id" :href="card.href" class="numeral" :data-metric="card.id">
        <span class="numeral-head"><Icon :name="card.icon"/><Icon v-if="card.href" name="arrow" class="numeral-go"/></span>
        <strong class="numeral-value" :class="[tone(card), { 'is-empty': card.valor === null }]">{{ card.valor === null ? 'Sin datos' : card.valor.toLocaleString('es-BO') }}</strong>
        <span class="numeral-label">{{ card.titulo }}</span>
        <span v-if="detail(card).length" class="numeral-detail">{{ detail(card).join(' · ') }}</span>
      </component>
    </section>
    <div v-if="!tarjetas.length" class="panel empty-state">
      <Icon name="chart"/>
      <h2>Sin indicadores para su cuenta</h2>
      <p>Su rol todavía no tiene indicadores habilitados.</p>
    </div>

    <div v-if="breakdowns.length || iot" class="dashboard-status">
      <StateBreakdown v-for="card in breakdowns" :key="card.id" :title="card.titulo" :context="card.contexto" :total="card.valor" :values="card.desglose" :href="card.href" :data-metric="card.id"/>
      <section v-if="iot" class="tech-surface dashboard-iot" aria-labelledby="dashboard-iot-title" data-metric="A10">
        <div class="dashboard-iot-head">
          <h2 id="dashboard-iot-title">Monitoreo de ocupación</h2>
          <svg class="dashboard-iot-glyph" viewBox="0 0 64 56" aria-hidden="true"><path d="M32 6 58 19 32 32 6 19Z"/><path d="M32 16 58 29 32 42 6 29Z"/><path d="M32 26 58 39 32 52 6 39Z"/></svg>
        </div>
        <p v-if="iot.error">No se pudo consultar el monitoreo en este momento.</p>
        <template v-else>
          <p class="dashboard-iot-total"><strong>{{ iot.no_determinable }}</strong> {{ iot.no_determinable === 1 ? 'espacio monitoreado, con ocupación no determinable' : 'espacios monitoreados, todos con ocupación no determinable' }}</p>
          <dl class="dashboard-iot-figures">
            <div><dd>{{ iot.con_evidencia_reciente }}</dd><dt>con lectura reciente</dt></div>
            <div><dd>{{ iot.sin_evidencia_reciente }}</dd><dt>sin lectura reciente</dt></div>
          </dl>
        </template>
        <details v-if="!iot.error"><summary>Cómo leer este dato</summary><p>Una lectura reciente es evidencia del ambiente; no permite afirmar que un espacio esté libre ni calcular un porcentaje. La ocupación se declara no determinable.</p></details>
        <Link href="/iot/monitoreo" class="btn btn-secondary">Abrir monitoreo</Link>
      </section>
    </div>

    <section v-if="groups.length" class="panel dashboard-charts-panel">
      <div class="panel-heading">
        <h2>Actividad y estado actual</h2>
        <div v-if="groups.length > 1" class="segmented" role="tablist" aria-label="Grupo de gráficos">
          <button v-for="group in groups" :key="group.id" type="button" role="tab" :aria-selected="group.id === activeGroup.id" :class="{ active: group.id === activeGroup.id }" @click="tab = group.id">{{ group.label }}</button>
        </div>
      </div>
      <div class="dashboard-charts"><AccessibleChart v-for="chart in activeGroup.charts" :key="chart.id" :chart="chart"/></div>
    </section>

    <section v-if="vista === 'personal'" class="panel dashboard-upcoming">
      <div class="panel-heading"><h2>Mis próximas reservas</h2><Link v-if="proximas.length" href="/reservas?alcance=propias" class="text-link">Ver todas</Link></div>
      <ul v-if="proximas.length">
        <li v-for="r in proximas" :key="r.id">
          <Link :href="`/reservas/${r.id}`"><strong>{{ r.espacio }}</strong><small>{{ r.codigo }}</small></Link>
          <span>{{ reservationDate(r.inicio) }} – {{ sameDay(r) ? r.fin.split(' ')[1] : reservationDate(r.fin) }}</span>
        </li>
      </ul>
      <div v-else class="empty-state"><Icon name="calendar"/><h3>No tiene reservas próximas</h3><Link v-if="can('reservas.solicitar') && can('espacios.ver')" href="/disponibilidad" class="btn btn-primary">Reservar un espacio</Link></div>
    </section>

    <details v-if="more.length" class="disclosure dashboard-more">
      <summary>Más indicadores</summary>
      <dl class="dashboard-breakdown">
        <div v-for="card in more" :key="card.id" :data-metric="card.id"><dt>{{ card.titulo }}<small>{{ card.contexto }}</small></dt><dd>{{ card.valor === null ? 'Sin datos' : card.valor.toLocaleString('es-BO') }}</dd></div>
      </dl>
    </details>
    <p class="hint">Hora de referencia: {{ zona }}. Los indicadores de estado actual no dependen del periodo.</p>
  </AppLayout>
</template>
