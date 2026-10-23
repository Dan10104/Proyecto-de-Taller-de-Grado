<script setup>
import { useForm } from '@inertiajs/vue3';
import InputField from './InputField.vue';
import FormErrors from './FormErrors.vue';
import { optionsFor, statesFor, focusError } from '../spaces';
const props = defineProps({ filtros: Object, opciones: Object, availability: Boolean, fechaActual: String });
const defaults = { q: '', piso_id: '', tipo_espacio_id: '', capacidad_minima: '', estado: '', caracteristicas: [], ...(props.availability ? { buscar: '1', fecha: props.fechaActual, hora_inicio: '08:00', hora_fin: '09:00' } : {}) };
const form = useForm(Object.fromEntries(Object.keys(defaults).map(key => [key, props.filtros?.[key] ?? defaults[key]])));
function search() { form.get(props.availability ? '/disponibilidad' : '/espacios', { preserveState: true, replace: true, onError: focusError }); }
function clear() { form.defaults(defaults); form.reset(); search(); }
</script>
<template>
  <form class="panel space-filters" @submit.prevent="search">
    <FormErrors :errors="form.errors"/>
    <div v-if="availability" class="period-fields">
      <InputField id="f-fecha" label="Fecha" v-model="form.fecha" type="date" required min="1000-01-01" max="9999-12-31" :error="form.errors.fecha"/>
      <InputField id="f-inicio" label="Hora de inicio" v-model="form.hora_inicio" type="time" required :error="form.errors.hora_inicio"/>
      <InputField id="f-fin" label="Hora de fin" v-model="form.hora_fin" type="time" required :error="form.errors.hora_fin"/>
      <div class="period-context">
        <strong>Hora de Bolivia</strong>
        <span>America/La_Paz · UTC−4</span>
      </div>
    </div>
    <div class="space-filter-grid">
      <InputField id="f-q" label="Buscar espacio" v-model="form.q" type="search" maxlength="160" placeholder="Código o nombre" :error="form.errors.q"/>
      <InputField id="f-piso" label="Piso" v-model="form.piso_id" :options="optionsFor(opciones.pisos)" placeholder="Todos los pisos" :error="form.errors.piso_id"/>
      <InputField id="f-tipo" label="Tipo de espacio" v-model="form.tipo_espacio_id" :options="optionsFor(opciones.tipos)" placeholder="Todos los tipos" :error="form.errors.tipo_espacio_id"/>
      <InputField id="f-capacidad" label="Capacidad mínima" v-model="form.capacidad_minima" type="number" min="1" max="2147483647" placeholder="Personas" :error="form.errors.capacidad_minima"/>
      <InputField id="f-estado" label="Estado operativo" v-model="form.estado" :options="statesFor(opciones.estados)" placeholder="Todos los estados" :error="form.errors.estado"/>
    </div>
    <details v-if="opciones.caracteristicas.length" class="feature-filter">
      <summary>
        Características requeridas
        <span v-if="form.caracteristicas.length">({{ form.caracteristicas.length }})</span>
      </summary>
      <fieldset class="feature-options">
        <legend class="sr-only">Características requeridas</legend>
        <label v-for="feature in opciones.caracteristicas" :key="feature.id" class="check-option">
          <input type="checkbox" v-model="form.caracteristicas" :value="feature.id">
          {{ feature.nombre }}
        </label>
      </fieldset>
      <p class="hint">El espacio debe contar con todas las características seleccionadas.</p>
    </details>
    <div class="filter-actions">
      <button class="btn btn-primary" :disabled="form.processing">{{ availability ? 'Consultar disponibilidad' : 'Aplicar filtros' }}</button>
      <button type="button" class="text-button" @click="clear">Limpiar filtros</button>
    </div>
  </form>
</template>
