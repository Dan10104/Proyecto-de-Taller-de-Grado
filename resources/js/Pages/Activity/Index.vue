<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import { dateLabel } from '../../security';
const props = defineProps({ registros: Object, filtros: Object, acciones: Array, tipos_entidad: Array, procesos: Array });
const references = { actor_id: 'ID del actor', usuario_afectado_id: 'ID del usuario afectado', reserva_id: 'ID de reserva', prestamo_id: 'ID de préstamo', recurso_id: 'ID de recurso', incidencia_id: 'ID de incidencia' };
const fields = ['fecha_desde', 'fecha_hasta', 'accion', 'resultado', 'critica', 'tipo_entidad', 'responsable_tipo', 'proceso_responsable', ...Object.keys(references)];
const form = useForm(Object.fromEntries(fields.map(key => [key, props.filtros[key] ?? ''])));
const choices = values => values.map(value => ({ value, label: value.replaceAll('_', ' ') }));
function apply() {
  form.transform(data => Object.fromEntries(Object.entries(data).filter(([, value]) => value !== '' && value !== null))).get('/actividad', { preserveScroll: true });
}
</script>
<template>
  <AppLayout title="Bitácora">
    <div class="page-heading"><div><h1>Bitácora de actividad</h1><p>Acciones registradas, responsables y referencias. Consulta de solo lectura.</p></div><span class="audit-count">{{ registros.total }} registros</span></div>
    <form class="panel audit-filters" @submit.prevent="apply">
      <div class="audit-filter-grid">
        <InputField id="fecha_desde" label="Desde" type="date" v-model="form.fecha_desde" :error="form.errors.fecha_desde" required/>
        <InputField id="fecha_hasta" label="Hasta (inclusive)" type="date" v-model="form.fecha_hasta" :error="form.errors.fecha_hasta" required/>
        <InputField id="accion" label="Acción" v-model="form.accion" :options="acciones" placeholder="Todas las acciones" :error="form.errors.accion"/>
        <InputField id="resultado" label="Resultado" v-model="form.resultado" :options="choices(['EXITO', 'RECHAZO', 'ERROR'])" placeholder="Todos" :error="form.errors.resultado"/>
        <InputField id="critica" label="Criticidad" v-model="form.critica" :options="[{value:'1', label:'Críticas'}, {value:'0', label:'No críticas'}]" placeholder="Todas" :error="form.errors.critica"/>
      </div>
      <details class="audit-advanced" :open="Object.keys(references).some(key => filtros[key]) || !!filtros.tipo_entidad || !!filtros.proceso_responsable || !!filtros.responsable_tipo">
        <summary>Responsables y referencias</summary>
        <p>Use identificadores exactos. Todos los filtros se combinan; no se busca dentro de descripciones.</p>
        <div class="audit-filter-grid">
          <InputField id="responsable_tipo" label="Tipo de responsable" v-model="form.responsable_tipo" :options="[{value:'humano',label:'Humano'}, {value:'proceso',label:'Proceso automático'}]" placeholder="Todos" :error="form.errors.responsable_tipo"/>
          <InputField id="proceso_responsable" label="Proceso" v-model="form.proceso_responsable" :options="choices(procesos)" placeholder="Todos" :error="form.errors.proceso_responsable"/>
          <InputField id="tipo_entidad" label="Tipo de referencia" v-model="form.tipo_entidad" :options="choices(tipos_entidad)" placeholder="Todas" :error="form.errors.tipo_entidad"/>
          <InputField v-for="(label,key) in references" :key="key" :id="key" :label="label" type="number" min="1" v-model="form[key]" :error="form.errors[key]" placeholder="Sin filtro"/>
        </div>
      </details>
      <div class="audit-toolbar"><p>Hora local: America/La_Paz. Últimos 30 días al limpiar filtros.</p><div><button class="btn btn-primary" :disabled="form.processing">{{ form.processing ? 'Consultando…' : 'Aplicar filtros' }}</button> <Link class="btn btn-secondary" href="/actividad">Limpiar filtros</Link></div></div>
    </form>
    <section class="panel audit-results" aria-label="Actividades registradas" :aria-busy="form.processing">
      <p class="audit-note">Los nombres mostrados son actuales; el registro conserva el ID histórico del usuario.</p>
      <div v-if="registros.data.length" class="audit-table-wrap" tabindex="0" role="region" aria-label="Tabla de actividad; desplazamiento horizontal disponible">
        <table class="audit-table"><caption class="sr-only">Actividades ordenadas por fecha e identificador descendentes</caption><thead><tr><th scope="col">Fecha / hora</th><th scope="col">Acción</th><th scope="col">Resultado</th><th scope="col">Responsable</th><th scope="col">Referencias</th><th scope="col">Criticidad</th><th scope="col">Detalle</th></tr></thead><tbody>
          <tr v-for="row in registros.data" :key="row.id"><td><time :datetime="row.fecha_hora">{{ dateLabel(row.fecha_hora) }}</time><small>#{{ row.id }}</small></td><td>{{ row.accion_label }}</td><td><span class="audit-badge" :class="`audit-${row.resultado.toLowerCase()}`">{{ row.resultado }}</span></td><td><span v-if="row.actor">{{ row.actor.label }}</span><small v-if="row.proceso_responsable">{{ row.proceso_responsable }}</small></td><td><span v-for="ref in row.referencias" :key="ref.campo" class="audit-reference">{{ ref.label }}</span><span v-if="!row.referencias.length">Sin referencias asociadas</span></td><td>{{ row.critica ? 'Crítica' : 'No crítica' }}</td><td><Link :href="`/actividad/${row.id}`" class="btn btn-secondary" :aria-label="`Ver actividad ${row.id}`">Ver</Link></td></tr>
        </tbody></table>
      </div>
      <div v-else class="audit-empty"><h2>Sin actividad para estos filtros</h2><p>Pruebe otro periodo o quite condiciones para ampliar la consulta.</p></div>
      <Pagination :data="registros"/>
    </section>
  </AppLayout>
</template>

<style scoped>
.audit-count{border:1px solid #d6e2e7;border-radius:999px;padding:.55rem 1rem;white-space:nowrap;font-weight:600;color:#225569;background:#edf6f7}.audit-filters{padding:1.25rem;margin-bottom:1.25rem}.audit-filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem}.audit-advanced{margin-top:1.2rem;border-top:1px solid #e3e9ed;padding-top:1rem}.audit-advanced summary{cursor:pointer;font-weight:600;padding:.35rem 0}.audit-advanced p,.audit-toolbar p,.audit-note{font-size:.85rem;color:#536675}.audit-advanced p{margin:.6rem 0 1rem}.audit-toolbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-top:1rem;flex-wrap:wrap}.audit-results{padding:1.1rem}.audit-note{margin:0 0 1rem}.audit-table-wrap{max-width:100%;overflow:auto;border:1px solid #e3e9ed;border-radius:10px}.audit-table{width:100%;border-collapse:collapse;font-size:.86rem;min-width:960px}.audit-table th{background:#f2f6f8;text-align:left;font-size:.75rem;letter-spacing:.03em}.audit-table th,.audit-table td{padding:.85rem .7rem;border-bottom:1px solid #e3e9ed;vertical-align:top}.audit-table small,.audit-reference{display:block;margin-top:.25rem;color:#536675}.audit-badge{display:inline-block;border-radius:6px;padding:.18rem .45rem;font-size:.73rem;font-weight:650;border:1px solid}.audit-exito{color:#246448;background:#eef8f0;border-color:#c7e5d0}.audit-rechazo{color:#87520d;background:#fff8e9;border-color:#ebd9b3}.audit-error{color:#993742;background:#fff0f1;border-color:#e9c5c9}.audit-empty{text-align:center;padding:3rem 1rem}.audit-table-wrap:focus-visible,summary:focus-visible{outline:3px solid #147f96;outline-offset:3px}@media(max-width:600px){.audit-filters,.audit-results{padding:1rem}.audit-filter-grid{grid-template-columns:1fr}.audit-toolbar>div{display:flex;gap:.5rem;flex-wrap:wrap}.audit-count{align-self:flex-start}}
</style>
