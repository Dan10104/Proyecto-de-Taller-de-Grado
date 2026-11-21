<script setup>
import { computed } from 'vue';

// Ilustración dibujada de un ambiente vacío. Es decorativa: no representa un espacio concreto
// ni sustituye a una fotografía del recurso. La variante se elige por el nombre o el tipo.
const props = defineProps({ kind: { type: String, default: '' } });
const variant = computed(() => (/auditorio/i.test(props.kind) ? 'auditorio' : /laboratorio|lab\b/i.test(props.kind) ? 'laboratorio' : 'aula'));
const rows = [
  { y: 196, w: 62, h: 12, gap: 26, chair: 15 },
  { y: 226, w: 78, h: 15, gap: 34, chair: 19 },
  { y: 262, w: 98, h: 19, gap: 44, chair: 24 },
];
const desks = rows.flatMap((row, r) => [0, 1, 2].map(i => {
  const total = row.w * 3 + row.gap * 2;
  const x = 240 - total / 2 + i * (row.w + row.gap);
  return { ...row, x, key: `${r}-${i}` };
}));
</script>

<template>
  <svg class="space-illustration" :class="`is-${variant}`" viewBox="0 0 480 300" role="presentation" aria-hidden="true" focusable="false">
    <!-- Sala: pared del fondo, techo y piso en perspectiva -->
    <path class="ill-floor" d="M72 178h336l72 122H0Z"/>
    <path class="ill-wall" d="M72 34h336v144H72Z"/>
    <path class="ill-line" d="M72 34 0 0M408 34 480 0M72 178 0 300M408 178 480 300"/>
    <!-- Luminarias -->
    <ellipse class="ill-light" cx="170" cy="16" rx="34" ry="5"/>
    <ellipse class="ill-light" cx="320" cy="16" rx="34" ry="5"/>
    <!-- Cortina azul recogida sobre la ventana -->
    <path class="ill-line" d="M82 42h92"/>
    <path class="ill-curtain" d="M86 44h84v126c-9 5-15-4-21 0s-13 5-21 0-13 4-21 0-14 4-21 0Z"/>
    <path class="ill-fold" d="M100 46v122M114 46v124M128 46v122M142 46v124M156 46v122"/>
    <!-- Aire acondicionado -->
    <rect class="ill-device" x="188" y="46" width="58" height="17" rx="4"/>
    <path class="ill-line" d="M196 57h42"/>
    <template v-if="variant !== 'auditorio'">
      <!-- Proyector de techo y proyección -->
      <path class="ill-line" d="M322 0v22"/>
      <rect class="ill-device" x="306" y="22" width="32" height="13" rx="3"/>
      <circle class="ill-lens" cx="314" cy="28.5" r="3"/>
      <rect class="ill-screen" x="262" y="62" width="134" height="84" rx="2"/>
      <path class="ill-slide" d="M274 76h58M274 90h110M274 100h96M274 110h104M274 124h44"/>
    </template>
    <template v-else>
      <!-- Escenario y pantalla -->
      <rect class="ill-screen" x="196" y="52" width="200" height="96" rx="2"/>
      <path class="ill-slide" d="M214 72h84M214 90h160M214 104h140M214 118h110"/>
      <path class="ill-stage" d="M96 178h288l14 14H82Z"/>
    </template>
    <!-- Mobiliario -->
    <g v-for="desk in desks" :key="desk.key">
      <template v-if="variant === 'auditorio'">
        <rect class="ill-chair" :x="desk.x" :y="desk.y" :width="desk.w" :height="desk.h + 4" :rx="desk.h / 2"/>
      </template>
      <template v-else>
        <rect class="ill-desk" :x="desk.x" :y="desk.y" :width="desk.w" :height="desk.h" rx="2.5"/>
        <template v-if="variant === 'laboratorio'">
          <rect class="ill-monitor" :x="desk.x + desk.w * 0.16" :y="desk.y - desk.h * 1.15" :width="desk.w * 0.28" :height="desk.h" rx="1.5"/>
          <rect class="ill-monitor" :x="desk.x + desk.w * 0.56" :y="desk.y - desk.h * 1.15" :width="desk.w * 0.28" :height="desk.h" rx="1.5"/>
        </template>
        <rect class="ill-chair" :x="desk.x + desk.w * 0.14" :y="desk.y + desk.h + 3" :width="desk.w * 0.3" :height="desk.chair" :rx="desk.chair * 0.22"/>
        <rect class="ill-chair" :x="desk.x + desk.w * 0.56" :y="desk.y + desk.h + 3" :width="desk.w * 0.3" :height="desk.chair" :rx="desk.chair * 0.22"/>
      </template>
    </g>
  </svg>
</template>
