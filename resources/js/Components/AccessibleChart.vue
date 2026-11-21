<script setup>
import { computed } from 'vue';
import { statusOf } from '../status';

const props = defineProps({ chart: Object });
const entries = computed(() => Object.entries(props.chart.valores));
const maximum = computed(() => Math.max(1, ...entries.value.map(([, n]) => n)));
const total = computed(() => entries.value.reduce((sum, [, n]) => sum + n, 0));
const label = (value) => statusOf(value).label;
const short = (name) => (name.length === 10 ? `${name.slice(8)}/${name.slice(5, 7)}` : name.length === 7 ? `${name.slice(5)}/${name.slice(2, 4)}` : label(name));
const step = computed(() => Math.max(1, Math.ceil(entries.value.length / 8)));
const slot = computed(() => 560 / Math.max(1, entries.value.length));
const column = computed(() => Math.min(36, Math.max(2, slot.value * 0.64)));
</script>

<template>
  <section class="dashboard-chart" :aria-labelledby="`chart-${chart.id}`">
    <div class="chart-head">
      <h3 :id="`chart-${chart.id}`">{{ chart.titulo }}</h3>
      <strong v-if="!chart.sin_datos" class="chart-total">{{ total.toLocaleString('es-BO') }}</strong>
    </div>
    <p v-if="chart.sin_datos" class="chart-empty">Sin registros en el periodo.</p>
    <div v-else-if="chart.tipo !== 'tendencia'" class="chart-bars" aria-hidden="true">
      <div v-for="([name, n]) in entries" :key="name" class="chart-bar-row">
        <span>{{ label(name) }}</span>
        <div class="chart-bar-track"><span :class="`seg-${statusOf(name).tone}`" :style="{width: `${n / maximum * 100}%`}"></span></div>
        <strong>{{ n }}</strong>
      </div>
    </div>
    <div v-else class="chart-scroll chart-trend" aria-hidden="true">
      <svg viewBox="0 0 600 200" focusable="false">
        <line class="chart-grid" x1="32" y1="24" x2="596" y2="24"/>
        <line class="chart-grid" x1="32" y1="92" x2="596" y2="92"/>
        <line class="chart-axis" x1="32" y1="160" x2="596" y2="160"/>
        <text class="chart-tick" x="24" y="28" text-anchor="end">{{ maximum }}</text>
        <text class="chart-tick" x="24" y="164" text-anchor="end">0</text>
        <g v-for="([name, n], index) in entries" :key="name" :transform="`translate(${36 + index * slot},0)`">
          <rect class="chart-column" :class="{ 'is-last': index === entries.length - 1 }" :x="(slot - column) / 2" :y="160 - n / maximum * 136" :height="n / maximum * 136" :width="column" rx="2"/>
          <text v-if="entries.length <= 14 && n > 0" class="chart-value" :x="slot / 2" :y="152 - n / maximum * 136" text-anchor="middle">{{ n }}</text>
          <text v-if="index % step === 0" class="chart-tick" :x="slot / 2" y="182" text-anchor="middle">{{ short(name) }}</text>
        </g>
      </svg>
    </div>
    <details class="chart-data">
      <summary>Ver datos<span class="sr-only"> de {{ chart.titulo.toLowerCase() }}</span></summary>
      <p class="hint">{{ chart.contexto }}</p>
      <div class="report-table-scroll" tabindex="0" :aria-label="`Tabla: ${chart.titulo}`">
        <table class="report-table"><caption class="sr-only">{{ chart.titulo }} · {{ chart.contexto }}</caption><thead><tr><th scope="col">{{ chart.tipo === 'tendencia' ? 'Periodo local' : 'Categoría' }}</th><th scope="col">Cantidad</th></tr></thead><tbody><tr v-for="([name, n]) in entries" :key="name"><th scope="row">{{ chart.tipo === 'tendencia' ? name : label(name) }}</th><td>{{ n }}</td></tr></tbody></table>
      </div>
    </details>
  </section>
</template>
