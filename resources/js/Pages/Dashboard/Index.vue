<script setup>
import { ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormErrors from '../../Components/FormErrors.vue';
import ReportPeriodFields from '../../Components/ReportPeriodFields.vue';
import AccessibleChart from '../../Components/AccessibleChart.vue';
import Icon from '../../Components/Icon.vue';
import '../../../css/reporting.css';
const props = defineProps({ vista: String, vistas: Array, filtros: Object, generado_en: String, zona: String, tarjetas: Array, graficos: Array, proximas: Array, iot: Object });
const filters = ref({ ...props.filtros });
const selectedView = ref(props.vista), loading = ref(false);
const page = usePage();
function refresh() { const query = { vista: selectedView.value, ...filters.value }; if (query.periodo !== 'personalizado') { delete query.fecha_inicio; delete query.fecha_fin; } router.get('/dashboard', query, { onStart: () => loading.value = true, onFinish: () => loading.value = false }); }
</script>
<template>
  <AppLayout title="Dashboard"><div class="page-heading"><div><h1>{{ vista === 'administrativo' ? 'Dashboard institucional' : 'Mi dashboard' }}</h1><p>{{ vista === 'administrativo' ? 'Indicadores de los ámbitos que puede consultar.' : 'Sus solicitudes, próximas reservas y avisos personales.' }}</p></div></div>
    <form class="panel report-filters" @submit.prevent="refresh"><fieldset class="report-filter-lock" :disabled="loading"><label v-if="vistas.length > 1" class="report-field" for="dashboard-vista"><span>Vista</span><select id="dashboard-vista" v-model="selectedView"><option v-for="v in vistas" :key="v" :value="v">{{ v === 'personal' ? 'Personal' : 'Institucional' }}</option></select></label><ReportPeriodFields v-if="selectedView === 'administrativo'" v-model="filters" prefix="dashboard-periodo"/><button class="btn btn-primary" :disabled="loading">{{ loading ? 'Actualizando…' : 'Actualizar' }}</button><span class="report-muted">{{ zona }}</span></fieldset></form>
    <FormErrors :errors="page.props.errors"/><p class="report-cut" role="status">Consultado: {{ generado_en }} · {{ zona }}. Las tarjetas de estado actual son independientes del periodo.</p>
    <div class="dashboard-cards" :aria-busy="loading"><section v-for="card in tarjetas" :key="card.id" class="panel dashboard-card" :data-metric="card.id"><div class="metric-heading"><h2>{{ card.titulo }}</h2><Icon :name="({ A1:'calendar',A2:'calendar',A3:'clock',A4:'building',A5:'exchange',A6:'exchange',A7:'clock',A8:'monitor',A9:'tool',A10:'sensor',P1:'calendar',P2:'calendar',P3:'exchange',P4:'exchange',P5:'exchange',P6:'clock',P7:'bell' })[card.id] || 'chart'"/></div><strong class="dashboard-value" :class="{'insufficient': card.valor === null}">{{ card.valor === null ? 'Sin datos suficientes' : card.valor.toLocaleString('es-BO') }}</strong><details class="metric-context"><summary>Contexto<span class="sr-only"> de {{ card.titulo }}</span></summary><p>{{ card.contexto }}</p><dl v-if="Object.keys(card.desglose).length" class="dashboard-breakdown"><div v-for="(n, name) in card.desglose" :key="name"><dt>{{ name.replaceAll('_', ' ') }}</dt><dd>{{ n }}</dd></div></dl></details></section></div>
    <p v-if="!tarjetas.length" class="panel report-empty">No tiene indicadores habilitados con sus permisos actuales.</p>
    <details v-if="iot && !iot.error" class="compact-help dashboard-iot"><summary>Evidencia IoT · ocupación no determinable</summary><p>{{ iot.con_evidencia_reciente }} espacios con evidencia reciente; {{ iot.sin_evidencia_reciente }} sin evidencia reciente. La ocupación es no determinable: estos datos no permiten afirmar que un espacio está libre ni calcular un porcentaje de ocupación.</p></details>
    <div v-if="graficos.length" class="dashboard-charts"><AccessibleChart v-for="chart in graficos" :key="chart.id" :chart="chart"/></div>
    <section v-if="vista === 'personal'" class="panel dashboard-upcoming"><h2>Mis próximas reservas</h2><p class="report-muted">Hasta cinco reservas aprobadas, ordenadas por inicio.</p><ul v-if="proximas.length"><li v-for="r in proximas" :key="r.id"><div><Link :href="`/reservas/${r.id}`">{{ r.codigo }} · {{ r.espacio }}</Link><p>{{ r.inicio }} — {{ r.fin }}</p></div><Link class="btn btn-secondary" :href="`/reservas/${r.id}`">Ver reserva</Link></li></ul><p v-else class="report-empty">No tiene próximas reservas aprobadas.</p></section>
  </AppLayout>
</template>
