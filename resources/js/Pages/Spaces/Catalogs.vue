<script setup>
import { ref, watch, nextTick } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import Pagination from '../../Components/Pagination.vue';
import { focusError } from '../../spaces';
const props = defineProps({ catalogo: String, registros: Object, filtros: Object });
const labels = { pisos: 'Pisos', tipos: 'Tipos de espacio', caracteristicas: 'Características' };
const editing = ref(null), search = ref(props.filtros.q || '');
const form = useForm({ nombre: '', numero: '', codigo: '', descripcion: '' });
function reset() { editing.value = null; form.reset(); form.clearErrors(); }
watch(() => props.catalogo, () => { reset(); search.value = props.filtros.q || ''; });
async function edit(row) { reset(); editing.value = row.id; Object.assign(form, { nombre: row.nombre, numero: row.numero ?? '', codigo: row.codigo ?? '', descripcion: row.descripcion ?? '' }); await nextTick(); document.getElementById('catalogo-nombre')?.focus(); }
function save() { const url = `/espacios/catalogos/${props.catalogo}`; const options = { preserveScroll: true, onError: focusError, onSuccess: reset }; editing.value ? form.put(`${url}/${editing.value}`, options) : form.post(url, options); }
</script>
<template>
  <AppLayout title="Catálogos auxiliares" section="Espacios">
    <div class="page-heading">
      <div>
        <h1>Catálogos auxiliares</h1>
        <p>Prepare las opciones de ubicación y características para registrar espacios.</p>
      </div>
      <Link href="/espacios" class="btn btn-secondary">Volver a espacios</Link>
    </div>
    <nav class="catalog-tabs" aria-label="Catálogos de espacios">
      <Link v-for="(label, key) in labels" :key="key" :href="`/espacios/catalogos/${key}`" :class="{active:catalogo === key}" :aria-current="catalogo === key ? 'page' : undefined">{{ label }}</Link>
    </nav>
    <section class="catalog-layout">
      <div class="panel">
        <div class="panel-heading">
          <h2>{{ labels[catalogo] }}</h2>
        </div>
        <form class="filter-bar" @submit.prevent="router.get(`/espacios/catalogos/${catalogo}`, { q: search }, { preserveState: true })">
          <InputField id="catalogo-buscar" label="Buscar por nombre" v-model="search" type="search" maxlength="120"/>
          <button class="btn btn-secondary">Buscar</button>
        </form>
        <div v-if="registros.data.length" class="table-scroll" role="region" tabindex="0" aria-label="Registros del catálogo desplazables">
          <table>
            <caption class="sr-only">{{ labels[catalogo] }}</caption>
            <thead>
              <tr>
                <th v-if="catalogo !== 'caracteristicas'">{{ catalogo === 'pisos' ? 'Número' : 'Código' }}</th>
                <th>Nombre</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in registros.data" :key="row.id">
                <td v-if="catalogo !== 'caracteristicas'">{{ catalogo === 'pisos' ? row.numero : row.codigo }}</td>
                <td class="wrap-cell">
                  <strong>{{ row.nombre }}</strong>
                  <small v-if="row.descripcion" class="cell-subtitle">{{ row.descripcion }}</small>
                </td>
                <td>
                  <button class="text-button" @click="edit(row)" :aria-label="`Editar ${row.nombre}`">Editar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <h3>No hay registros</h3>
          <p>Agregue una opción o ajuste la búsqueda.</p>
        </div>
        <Pagination :data="registros"/>
      </div>
      <form class="panel form-panel catalog-form" @submit.prevent="save">
        <h2>{{ editing ? 'Editar registro' : 'Agregar registro' }}</h2>
        <p class="hint">{{ labels[catalogo] }} · Campos obligatorios con *</p>
        <FormErrors :errors="form.errors"/>
        <InputField id="catalogo-nombre" label="Nombre" v-model="form.nombre" required :maxlength="catalogo === 'pisos' ? 100 : 120" :error="form.errors.nombre"/>
        <InputField v-if="catalogo === 'pisos'" id="catalogo-numero" label="Número de piso" v-model="form.numero" type="number" required min="-2147483648" max="2147483647" :error="form.errors.numero"/>
        <InputField v-if="catalogo === 'tipos'" id="catalogo-codigo" label="Código del tipo" v-model="form.codigo" required maxlength="60" :error="form.errors.codigo"/>
        <InputField v-if="catalogo === 'caracteristicas'" id="catalogo-descripcion" label="Descripción" v-model="form.descripcion" type="textarea" maxlength="255" :error="form.errors.descripcion"/>
        <div class="form-actions">
          <button v-if="editing" type="button" class="btn btn-secondary" @click="reset">Cancelar edición</button>
          <button class="btn btn-primary" :disabled="form.processing">Guardar registro</button>
        </div>
      </form>
    </section>
  </AppLayout>
</template>
