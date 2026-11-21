<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import Pagination from '../../Components/Pagination.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import Icon from '../../Components/Icon.vue';
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
    <div class="page-heading">
      <div>
        <h1>Bitácora de actividad</h1>
        <p>{{ registros.total }} registros · consulta de solo lectura.</p>
      </div>
    </div>

    <form class="panel space-filters audit-filters" @submit.prevent="apply">
      <InputField id="fecha_desde" label="Desde" type="date" v-model="form.fecha_desde" :error="form.errors.fecha_desde" required/>
      <InputField id="fecha_hasta" label="Hasta (inclusive)" type="date" v-model="form.fecha_hasta" :error="form.errors.fecha_hasta" required/>
      <InputField id="accion" label="Acción" v-model="form.accion" :options="acciones" placeholder="Todas las acciones" :error="form.errors.accion"/>
      <InputField id="resultado" label="Resultado" v-model="form.resultado" :options="choices(['EXITO', 'RECHAZO', 'ERROR'])" placeholder="Todos" :error="form.errors.resultado"/>
      <InputField id="critica" label="Criticidad" v-model="form.critica" :options="[{value:'1', label:'Críticas'}, {value:'0', label:'No críticas'}]" placeholder="Todas" :error="form.errors.critica"/>
      <details class="audit-advanced" :open="Object.keys(references).some(key => filtros[key]) || !!filtros.tipo_entidad || !!filtros.proceso_responsable || !!filtros.responsable_tipo">
        <summary>Responsables y referencias</summary>
        <p class="hint">Use identificadores exactos. Los filtros se combinan; no se busca dentro de las descripciones.</p>
        <div class="audit-filter-grid">
          <InputField id="responsable_tipo" label="Tipo de responsable" v-model="form.responsable_tipo" :options="[{value:'humano',label:'Humano'}, {value:'proceso',label:'Proceso automático'}]" placeholder="Todos" :error="form.errors.responsable_tipo"/>
          <InputField id="proceso_responsable" label="Proceso" v-model="form.proceso_responsable" :options="choices(procesos)" placeholder="Todos" :error="form.errors.proceso_responsable"/>
          <InputField id="tipo_entidad" label="Tipo de referencia" v-model="form.tipo_entidad" :options="choices(tipos_entidad)" placeholder="Todas" :error="form.errors.tipo_entidad"/>
          <InputField v-for="(label,key) in references" :key="key" :id="key" :label="label" type="number" min="1" v-model="form[key]" :error="form.errors[key]" placeholder="Sin filtro"/>
        </div>
      </details>
      <div class="filter-actions">
        <p class="hint">Hora de Bolivia (America/La_Paz). Al limpiar se muestran los últimos 30 días.</p>
        <div class="button-group">
          <Link class="btn btn-secondary" href="/actividad">Limpiar filtros</Link>
          <button class="btn btn-primary" :disabled="form.processing">{{ form.processing ? 'Consultando…' : 'Aplicar filtros' }}</button>
        </div>
      </div>
    </form>

    <section class="panel" aria-label="Actividades registradas" :aria-busy="form.processing">
      <div v-if="registros.data.length" class="table-scroll" tabindex="0" role="region" aria-label="Tabla de actividad; desplazamiento horizontal disponible">
        <table class="audit-table">
          <caption class="sr-only">Actividades ordenadas por fecha e identificador descendentes</caption>
          <thead>
            <tr>
              <th scope="col">Fecha y hora</th>
              <th scope="col">Acción</th>
              <th scope="col">Resultado</th>
              <th scope="col">Responsable</th>
              <th scope="col">Referencias</th>
              <th scope="col">Criticidad</th>
              <th scope="col" class="align-right">Detalle</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in registros.data" :key="row.id">
              <td class="date-cell"><time :datetime="row.fecha_hora">{{ dateLabel(row.fecha_hora) }}</time><small class="cell-subline">#{{ row.id }}</small></td>
              <td class="audit-action">{{ row.accion_label }}</td>
              <td><StatusBadge :value="row.resultado"/></td>
              <td>
                <span v-if="row.actor">{{ row.actor.label }}</span>
                <small v-if="row.proceso_responsable" class="cell-subline">{{ row.proceso_responsable }}</small>
              </td>
              <td>
                <ul v-if="row.referencias.length" class="audit-refs">
                  <li v-for="ref in row.referencias" :key="ref.campo">{{ ref.label }}</li>
                </ul>
                <span v-else class="audit-refs-empty">Sin referencias asociadas</span>
              </td>
              <td>
                <div v-if="row.critica" class="feature-chips"><span>Crítica</span></div>
                <span v-else class="sr-only">No crítica</span>
              </td>
              <td><div class="row-actions"><Link :href="`/actividad/${row.id}`" :aria-label="`Ver actividad ${row.id}`">Ver</Link></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <Icon name="activity"/>
        <h2>Sin actividad para estos filtros</h2>
        <p>Pruebe otro periodo o quite condiciones para ampliar la consulta.</p>
      </div>
      <Pagination :data="registros"/>
      <p class="hint">Los nombres mostrados son los actuales; el registro conserva el ID histórico del usuario.</p>
    </section>
  </AppLayout>
</template>
