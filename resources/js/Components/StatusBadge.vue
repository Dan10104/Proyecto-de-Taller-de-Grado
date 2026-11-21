<script setup>
import { computed } from 'vue';
import { statusOf } from '../status';

const props = defineProps({
  value: { type: [String, Boolean], default: '' },
  domain: { type: String, default: null },
  label: { type: String, default: null },
});

const status = computed(() => statusOf(props.value, props.domain));

// Figuras de 12 px con el mismo trazo; acompañan siempre a la etiqueta.
const marks = {
  check: 'm2.5 6.2 2.3 2.3 4.7-5',
  cross: 'm3 3 6 6M9 3 3 9',
  minus: 'M3 6h6',
  clock: 'M6 3.2V6l1.9 1.2M10.5 6a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z',
  ring: 'M10 6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
  dot: 'M6 6h.01M8 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z',
  alert: 'M6 3.4v3.2M6 8.6h.01M10.5 6a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z',
  lock: 'M3.2 5.6h5.6v4H3.2ZM4.4 5.6V4.2a1.6 1.6 0 0 1 3.2 0v1.4',
  tool: 'm7.2 2.4 2.4 2.4M2.4 9.6l3.6-3.6M7.4 1.9 5.9 3.4l.5 2.2 2.2.5 1.5-1.5-1.8.3-1.2-1.2Z',
  arrow: 'M2.5 6h7M6.8 3.2 9.6 6 6.8 8.8',
};
</script>

<template>
  <span class="badge" :class="`badge-${status.tone}`">
    <svg class="badge-mark" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path :d="marks[status.mark] || marks.ring"/>
    </svg>
    {{ label || status.label }}
  </span>
</template>
