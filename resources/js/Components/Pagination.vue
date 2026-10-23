<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ data: Object });
</script>

<template>
  <div v-if="data" class="pagination">
    <p>{{ data.from||0 }}–{{ data.to||0 }} de {{ data.total }} registros</p>
    <nav aria-label="Paginación">
      <Link v-if="data.prev_page_url" :href="data.prev_page_url" class="btn btn-secondary" preserve-scroll>Anterior</Link>
      <span class="page-number">Página {{ data.current_page }} de {{ data.last_page }}</span>
      <Link v-if="data.next_page_url" :href="data.next_page_url" class="btn btn-secondary" preserve-scroll>Siguiente</Link>
    </nav>
  </div>
</template>
