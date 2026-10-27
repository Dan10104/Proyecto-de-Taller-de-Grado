<script setup>
import { computed } from 'vue';
const props = defineProps({ chart: Object });
const entries = computed(() => Object.entries(props.chart.valores));
const maximum = computed(() => Math.max(1, ...entries.value.map(([, n]) => n)));
const label = (value) => value.replaceAll('_', ' ');
</script>
<template>
  <section class="panel dashboard-chart" :aria-labelledby="`chart-${chart.id}`"><h2 :id="`chart-${chart.id}`">{{ chart.titulo }}</h2><p class="report-muted">{{ chart.contexto }}</p>
    <p v-if="chart.sin_datos" class="report-empty">Sin datos para el periodo seleccionado.</p>
    <div v-else-if="chart.tipo !== 'tendencia'" class="chart-bars" aria-hidden="true"><div v-for="([name, n]) in entries" :key="name" class="chart-bar-row"><span>{{ label(name) }}</span><div class="chart-bar-track"><span :style="{width: `${n / maximum * 100}%`}"></span></div><strong>{{ n }}</strong></div></div>
    <div v-else class="chart-scroll chart-trend" aria-hidden="true">
      <svg viewBox="0 0 600 225" focusable="false">
        <line x1="36" y1="180" x2="580" y2="180" stroke="#bdcdd6"/>
        <line x1="36" y1="40" x2="580" y2="40" stroke="#e5edf1" stroke-dasharray="3 5"/>
        <text x="8" y="184" font-size="15" fill="#52647a">0</text>
        <text x="8" y="44" font-size="15" fill="#52647a">{{ maximum }}</text>
        <g v-for="([name, n], index) in entries" :key="name" :transform="`translate(${40 + index * (530 / entries.length)},0)`">
          <rect :y="180 - n / maximum * 140" :height="n / maximum * 140" :width="Math.max(1, 530 / entries.length - 3)" rx="2" fill="#367592"/>
          <text v-if="entries.length <= 12 && n > 0" :y="170 - n / maximum * 140" font-size="15" fill="#233349">{{ n }}</text>
          <text v-if="index % Math.ceil(entries.length / 6) === 0" y="204" font-size="15" fill="#52647a">{{ name.length === 10 ? name.slice(8) + '/' + name.slice(5, 7) : label(name) }}</text>
        </g>
      </svg>
    </div>
    <details><summary>Ver datos de {{ chart.titulo.toLowerCase() }}</summary><div class="report-table-scroll" tabindex="0" :aria-label="`Tabla: ${chart.titulo}`"><table class="report-table"><caption class="sr-only">{{ chart.titulo }} · {{ chart.contexto }}</caption><thead><tr><th scope="col">{{ chart.tipo === 'tendencia' ? 'Periodo local' : 'Categoría' }}</th><th scope="col">Cantidad</th></tr></thead><tbody><tr v-for="([name, n]) in entries" :key="name"><th scope="row">{{ label(name) }}</th><td>{{ n }}</td></tr></tbody></table></div></details>
  </section>
</template>
