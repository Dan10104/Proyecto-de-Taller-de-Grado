<script setup>
defineProps({
  errors: { type: Object, default: () => ({}) },
});
</script>

<template>
  <div v-if="Object.keys(errors).length" class="alert alert-error" role="alert" tabindex="-1">
    <strong>Revise la información</strong>
    <ul>
      <li v-for="(error,key) in errors" :key="key">{{ error }}</li>
    </ul>
  </div>
</template>
