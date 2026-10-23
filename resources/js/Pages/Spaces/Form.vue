<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import { optionsFor, statesFor, focusError } from '../../spaces';
import { useAccess } from '../../security';
const props = defineProps({ espacio: Object, opciones: Object });
const { can } = useAccess();
const form = useForm({ codigo: props.espacio?.recurso.codigo || '', nombre: props.espacio?.recurso.nombre || '', piso_id: props.espacio?.piso_id || '', tipo_espacio_id: props.espacio?.tipo_espacio_id || '', capacidad: props.espacio?.capacidad || '', referencia_ubicacion: props.espacio?.referencia_ubicacion || '', caracteristicas: props.espacio?.caracteristicas.map(c => c.id) || [], ...(can('espacios.cambiar_estado') ? { estado: props.espacio?.estado || 'DISPONIBLE' } : {}) });
function save() { const options = { onError: focusError }; props.espacio ? form.put(`/espacios/${props.espacio.recurso_id}`, options) : form.post('/espacios', options); }
</script>
<template>
  <AppLayout :title="espacio ? 'Editar espacio' : 'Registrar espacio'" section="Espacios">
    <div class="page-heading">
      <div>
        <h1>{{ espacio ? 'Editar espacio' : 'Registrar espacio' }}</h1>
        <p>Complete la información del ambiente. Los campos con * son obligatorios.</p>
      </div>
      <Link href="/espacios" class="btn btn-secondary">Volver al catálogo</Link>
    </div>
    <div v-if="!opciones.pisos.length || !opciones.tipos.length" class="alert">
      Para registrar un espacio necesita un piso y un tipo.
      <Link href="/espacios/catalogos/pisos" class="text-link">Administrar catálogos auxiliares</Link>
    </div>
    <form class="panel form-panel" @submit.prevent="save">
      <FormErrors :errors="form.errors"/>
      <div class="section-heading">
        <h2>Identificación y ubicación</h2>
        <p>El código identifica al recurso en toda la plataforma.</p>
      </div>
      <div class="form-grid">
        <InputField id="codigo" label="Código" v-model="form.codigo" required maxlength="60" :error="form.errors.codigo"/>
        <InputField id="nombre" label="Nombre" v-model="form.nombre" required maxlength="160" :error="form.errors.nombre"/>
        <InputField id="piso" label="Piso" v-model="form.piso_id" required :options="optionsFor(opciones.pisos)" :error="form.errors.piso_id"/>
        <InputField id="tipo" label="Tipo de espacio" v-model="form.tipo_espacio_id" required :options="optionsFor(opciones.tipos)" :error="form.errors.tipo_espacio_id"/>
        <InputField id="capacidad" label="Capacidad" v-model="form.capacidad" type="number" required min="1" max="2147483647" :error="form.errors.capacidad"/>
        <InputField v-if="can('espacios.cambiar_estado')" id="estado" label="Estado operativo" v-model="form.estado" required :options="statesFor(opciones.estados)" :error="form.errors.estado"/>
        <InputField class="span-2" id="referencia" label="Referencia de ubicación" v-model="form.referencia_ubicacion" maxlength="255" placeholder="Indicación adicional para encontrar el espacio" :error="form.errors.referencia_ubicacion"/>
      </div>
      <fieldset class="role-selection">
        <legend>Características del espacio</legend>
        <div class="feature-options">
          <label v-for="feature in opciones.caracteristicas" :key="feature.id" class="check-option">
            <input type="checkbox" v-model="form.caracteristicas" :value="feature.id">
            {{ feature.nombre }}
          </label>
        </div>
        <p v-if="!opciones.caracteristicas.length" class="hint">No hay características registradas. Puede agregarlas desde los catálogos auxiliares.</p>
        <p v-if="form.errors.caracteristicas" class="field-error">{{ form.errors.caracteristicas }}</p>
      </fieldset>
      <div class="form-actions">
        <Link href="/espacios" class="btn btn-secondary">Cancelar</Link>
        <button class="btn btn-primary" :disabled="form.processing || !opciones.pisos.length || !opciones.tipos.length">{{ form.processing ? 'Guardando…' : 'Guardar espacio' }}</button>
      </div>
    </form>
  </AppLayout>
</template>
