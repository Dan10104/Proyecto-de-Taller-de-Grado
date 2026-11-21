<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';
import FormErrors from '../../Components/FormErrors.vue';
import ReportPeriodFields from '../../Components/ReportPeriodFields.vue';
import ReportReferenceField from '../../Components/ReportReferenceField.vue';
import { useAccess } from '../../security';
const props = defineProps({ catalogo: Array, preview: Object, limite: Number });
const page = usePage(), { can } = useAccess();
const selected = ref(props.preview?.reporte || props.catalogo[0]?.id || '');
const filters = ref({ ...(props.preview?.filtros || (selected.value === 'espacios' ? { vista: 'inventario' } : {})) }), busy = ref(false), exporting = ref(''), error = ref({}), status = ref('');
const definition = computed(() => props.catalogo.find(r => r.id === selected.value));
const labels = { estado: 'Estado', espacio_id: 'Espacio', piso_id: 'Piso', solicitante_id: 'Solicitante', tipo_espacio_id: 'Tipo de espacio', equipamiento_id: 'Equipamiento', tipo_equipamiento_id: 'Tipo de equipamiento', espacio_base_id: 'Ubicación base', sin_ubicacion: 'Sin ubicación base', vencido: 'Vencido', recurso_id: 'Recurso', tipo: 'Tipo', espacio_historico_id: 'Espacio histórico', sensor_id: 'Sensor', categoria: 'Categoría', accion: 'Acción', resultado: 'Resultado', vista: 'Modalidad', disponible: 'Disponible para el intervalo', inicio: 'Inicio del intervalo', fin: 'Fin del intervalo' };
const fieldSpecs = computed(() => Object.entries(definition.value?.filtros || {}).filter(([key]) => !(selected.value === 'espacios' && (filters.value.vista || 'inventario') !== 'disponibilidad' && ['inicio', 'fin', 'disponible'].includes(key))));
function normalized() { const result = {}; for (const [key, value] of Object.entries(filters.value)) { if (value !== '' && value != null) result[key] = value; } if (definition.value?.rango) { result.periodo ||= 'hoy'; if (result.periodo !== 'personalizado') { delete result.fecha_inicio; delete result.fecha_fin; } } if (selected.value === 'espacios') { result.vista ||= 'inventario'; if (result.vista === 'inventario') { delete result.inicio; delete result.fin; delete result.disponible; } } return result; }
function stable(value) { return JSON.stringify(Object.entries(value).sort(([a], [b]) => a.localeCompare(b)).map(([k,v]) => [k, String(v)])); }
const unchanged = computed(() => props.preview?.reporte === selected.value && stable(normalized()) === stable(props.preview?.filtros || {}));
function changeReport() { filters.value = selected.value === 'espacios' ? { vista: 'inventario' } : {}; error.value = {}; status.value = ''; }
function loadPreview(pageNumber = 1) { error.value = {}; status.value = ''; router.get('/reportes', { reporte: selected.value, filtros: normalized(), pagina: pageNumber }, { onStart: () => busy.value = true, onFinish: () => busy.value = false }); }
const valueLabel = (value, type) => value == null ? '—' : type === 'boolean' ? (value ? 'Sí' : 'No') : String(value);
async function download(format) {
  exporting.value = format; error.value = {}; status.value = '';
  try { const cookie = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')); const response = await fetch('/reportes/exportar', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': cookie ? decodeURIComponent(cookie.slice(11)) : '' }, body: JSON.stringify({ reporte: selected.value, filtros: normalized(), formato: format }) });
    if (!response.ok) { if (response.status === 422) { const data = await response.json(); error.value = Object.fromEntries(Object.entries(data.errors || {}).map(([k,v]) => [k, Array.isArray(v) ? v.join(' ') : v])); } else { error.value = { exportacion: response.status === 403 ? 'Sus permisos no permiten esta exportación. Actualice la página.' : 'No se pudo descargar. Revise su sesión e intente nuevamente.' }; } return; }
    const blob = await response.blob(), url = URL.createObjectURL(blob); const link = document.createElement('a'); link.href = url; link.download = response.headers.get('Content-Disposition')?.match(/filename="?([^";]+)"?/)?.[1] || `espacio-u_reporte.${format}`; document.body.appendChild(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(url), 30000); status.value = `Archivo ${format.toUpperCase()} preparado para descargar.`;
  } catch { error.value = { exportacion: 'La conexión se interrumpió. Intente nuevamente.' }; } finally { exporting.value = ''; }
}
</script>
<template>
  <AppLayout title="Reportes">
    <div class="page-heading">
      <div><h1>Reportes</h1><p>Consulte la vista previa y descárguela en PDF o XLSX.</p></div>
      <span class="hint">Hora de referencia: America/La_Paz</span>
    </div>

    <form class="panel report-form no-print" @submit.prevent="loadPreview()">
      <fieldset class="report-filter-lock" :disabled="busy || Boolean(exporting)">
        <div class="report-step">
          <span class="report-step-number" aria-hidden="true">1</span>
          <div class="report-step-body">
            <label class="report-field report-selector" for="report-type"><span>Reporte</span><select id="report-type" v-model="selected" @change="changeReport"><option v-for="r in catalogo" :key="r.id" :value="r.id">{{ r.titulo }}</option></select></label>
          </div>
        </div>
        <div v-if="definition" class="report-step">
          <span class="report-step-number" aria-hidden="true">2</span>
          <div class="report-step-body">
            <h2 class="report-step-title">Periodo y filtros</h2>
            <div class="report-filter-grid">
              <ReportPeriodFields v-if="definition.rango" v-model="filters" prefix="report-period"/>
              <template v-for="([field, spec]) in fieldSpecs" :key="`${selected}-${field}`">
                <ReportReferenceField v-if="spec === 'reference'" :report="selected" :field="field" :label="labels[field]" v-model="filters[field]"/>
                <label v-else class="report-field" :for="`filter-${field}`"><span>{{ labels[field] }}</span><input v-if="spec === 'instant'" :id="`filter-${field}`" type="datetime-local" v-model="filters[field]" required><select v-else :id="`filter-${field}`" :value="filters[field] || (field === 'vista' ? 'inventario' : '')" @change="filters[field] = $event.target.value"><option v-if="field !== 'vista'" value="">Todos</option><option v-for="choice in spec" :key="choice" :value="choice">{{ choice.replaceAll('_', ' ') }}</option></select></label>
              </template>
            </div>
          </div>
        </div>
        <div class="report-actions">
          <button class="btn btn-primary" :disabled="busy || !definition"><Icon name="eye"/>{{ busy ? 'Consultando…' : 'Ver vista previa' }}</button>
          <button type="button" class="text-button" @click="changeReport">Limpiar filtros</button>
          <span class="hint">Hasta {{ limite.toLocaleString('es-BO') }} filas por archivo.</span>
        </div>
      </fieldset>
    </form>
    <FormErrors class="no-print" :errors="{ ...page.props.errors, ...error }"/>
    <p v-if="status" role="status" class="alert alert-success no-print">{{ status }}</p>

    <section v-if="preview" class="panel report-results" :aria-busy="busy">
      <div class="report-results-heading">
        <div class="report-step">
          <span class="report-step-number no-print" aria-hidden="true">3</span>
          <div class="report-step-body">
            <h2>{{ preview.titulo }}</h2>
            <p class="report-total"><strong>{{ preview.total.toLocaleString('es-BO') }}</strong> filas <span class="report-generated">Consultado: {{ preview.generado_en }} · {{ preview.zona }}</span></p>
          </div>
        </div>
        <div v-if="can('reportes.exportar')" class="report-export no-print">
          <span class="report-step-number" aria-hidden="true">4</span>
          <div class="button-group" role="group" aria-label="Exportar">
            <button v-for="format in ['pdf','xlsx']" :key="format" type="button" class="btn btn-secondary" :disabled="!unchanged || !preview.total || preview.total > limite || busy || Boolean(exporting)" @click="download(format)"><Icon name="download"/>{{ exporting === format ? 'Generando…' : `Exportar ${format.toUpperCase()}` }}</button>
          </div>
        </div>
      </div>
      <p v-if="!unchanged" class="alert no-print" role="status">Actualice la vista previa para aplicar los filtros y habilitar la exportación.</p>
      <p v-if="preview.total > limite" class="alert alert-warning" role="status">Demasiados resultados para exportar. Refine los filtros; no se generará un archivo truncado.</p>
      <div v-if="Object.keys(preview.filtros_visibles || {}).length" class="report-applied">
        <p class="report-applied-line"><Icon name="filter"/><span>{{ Object.values(preview.filtros_visibles).join(' · ') }}</span></p>
        <details class="report-applied-more">
          <summary>Ver filtros aplicados</summary>
          <dl><div v-for="(value, key) in preview.filtros_visibles" :key="key"><dt>{{ key }}</dt><dd>{{ value }}</dd></div></dl>
        </details>
      </div>
      <details v-if="selected === 'actividad-operacional' || selected === 'iot'" class="disclosure report-note">
        <summary>Cómo leer este reporte</summary>
        <p v-if="selected === 'actividad-operacional'">Resumen agrupado por día, acción, resultado, responsable, categoría y referencia. No es un navegador de bitácora general.</p>
        <p v-if="selected === 'iot'">Eventos históricos sin identidad personal. El valor no expresa un porcentaje universal de ocupación ni asistencia.</p>
      </details>
      <div v-if="preview.filas.length" class="report-table-scroll table-scroll" tabindex="0" role="region" aria-label="Resultados del reporte"><table class="report-table"><caption class="sr-only">{{ preview.titulo }} · vista previa autorizada</caption><thead><tr><th v-for="column in preview.columnas" :key="column.key" :class="[column.type, { num: column.type === 'integer' || column.type === 'number' || column.type === 'decimal' }]" scope="col">{{ column.label }}</th></tr></thead><tbody><tr v-for="(row, index) in preview.filas" :key="index"><td v-for="column in preview.columnas" :key="column.key" :class="[column.type, { num: column.type === 'integer' || column.type === 'number' || column.type === 'decimal' }]">{{ valueLabel(row[column.key], column.type) }}</td></tr></tbody></table></div>
      <div v-else class="empty-state"><Icon name="report"/><h3>Sin datos para el periodo seleccionado.</h3><p>No se generará un archivo vacío.</p></div>
      <nav v-if="preview.ultima_pagina > 1" class="report-pagination no-print" aria-label="Páginas del reporte"><button class="btn btn-secondary" :disabled="preview.pagina <= 1 || busy || !unchanged" @click="loadPreview(preview.pagina - 1)">Anterior</button><span>Página {{ preview.pagina }} de {{ preview.ultima_pagina }}</span><button class="btn btn-secondary" :disabled="preview.pagina >= preview.ultima_pagina || busy || !unchanged" @click="loadPreview(preview.pagina + 1)">Siguiente</button></nav>
    </section>
    <section v-else class="panel report-placeholder">
      <div class="report-step">
        <span class="report-step-number is-idle" aria-hidden="true">3</span>
        <div class="report-step-body"><h2>Vista previa</h2><p class="hint">Seleccione el reporte y sus filtros para consultar los datos antes de exportar.</p></div>
      </div>
      <div v-if="can('reportes.exportar')" class="report-step">
        <span class="report-step-number is-idle" aria-hidden="true">4</span>
        <div class="report-step-body"><h2>Exportar</h2><p class="hint">PDF o XLSX, disponible tras la vista previa.</p></div>
      </div>
    </section>
  </AppLayout>
</template>
