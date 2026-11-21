<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { statusOf } from '../status';

// Reparto de un total por estado: una barra segmentada y su leyenda con conteos.
const props = defineProps({ title: String, total: Number, values: { type: Object, default: () => ({}) }, href: String });
const rows = computed(() => Object.entries(props.values).map(([key, count]) => ({ key, count, ...statusOf(key) })));
const sum = computed(() => rows.value.reduce((n, row) => n + row.count, 0));
</script>

<template>
  <section class="panel state-breakdown">
    <div class="panel-heading">
      <h2>{{ title }}</h2>
      <Link v-if="href" :href="href" class="text-link">Ver</Link>
    </div>
    <p class="state-total"><strong>{{ (total ?? 0).toLocaleString('es-BO') }}</strong> en total</p>
    <div v-if="sum" class="state-bar" aria-hidden="true">
      <span v-for="row in rows.filter(r => r.count)" :key="row.key" :class="`seg-${row.tone}`" :style="{ flexGrow: row.count }"></span>
    </div>
    <div v-else class="state-bar is-empty" aria-hidden="true"></div>
    <ul class="state-legend">
      <li v-for="row in rows" :key="row.key">
        <span class="state-dot" :class="`seg-${row.tone}`" aria-hidden="true"></span>
        <span>{{ row.label }}</span>
        <strong>{{ row.count.toLocaleString('es-BO') }}</strong>
      </li>
    </ul>
  </section>
</template>
