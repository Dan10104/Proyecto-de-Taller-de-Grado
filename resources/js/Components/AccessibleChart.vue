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
    <div v-else class="chart-scroll" aria-hidden="true"><svg :viewBox="`0 0 ${Math.max(540, entries.length * 48)} 225`" :style="{ minWidth: `${Math.max(540, entries.length * 48)}px` }" focusable="false"><line x1="20" y1="180" :x2="Math.max(520, entries.length * 48 - 20)" y2="180" stroke="#ced8e5"/><g v-for="([name, n], index) in entries" :key="name" :transform="`translate(${28 + index * (Math.max(500, entries.length * 48 - 40) / entries.length)},0)`"><rect :y="180 - n / maximum * 140" :height="n / maximum * 140" :width="Math.max(10, (Math.max(500, entries.length * 48 - 40) / entries.length) - 16)" rx="3" fill="#2464b4"/><text :y="170 - n / maximum * 140" font-size="12" fill="#233349">{{ n }}</text><text y="201" font-size="10" fill="#52647a">{{ name.length > 12 ? name.slice(-5) : label(name).slice(0, 11) }}</text></g></svg></div>
    <details><summary>Ver datos de {{ chart.titulo.toLowerCase() }}</summary><div class="report-table-scroll" tabindex="0" :aria-label="`Tabla: ${chart.titulo}`"><table class="report-table"><caption class="sr-only">{{ chart.titulo }} · {{ chart.contexto }}</caption><thead><tr><th scope="col">{{ chart.tipo === 'tendencia' ? 'Periodo local' : 'Categoría' }}</th><th scope="col">Cantidad</th></tr></thead><tbody><tr v-for="([name, n]) in entries" :key="name"><th scope="row">{{ label(name) }}</th><td>{{ n }}</td></tr></tbody></table></div></details>
  </section>
</template>
