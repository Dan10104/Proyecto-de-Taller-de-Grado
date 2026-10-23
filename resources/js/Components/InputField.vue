<script setup>
const model = defineModel();
defineProps({ id: String, label: String, error: String, options: Array, type: { type: String, default: 'text' }, required: Boolean, maxlength: [String, Number], min: [String, Number], max: [String, Number], placeholder: String });
</script>
<template>
  <div class="field">
    <label :for="id">{{ label }}{{ required ? ' *' : '' }}</label>
    <select v-if="options" :id="id" v-model="model" :required="required" :aria-invalid="!!error" :aria-describedby="error ? `${id}-error` : undefined">
      <option value="">{{ placeholder || 'Seleccione una opción' }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
    <textarea v-else-if="type === 'textarea'" :id="id" v-model="model" :required="required" :maxlength="maxlength" rows="3" :aria-invalid="!!error" :aria-describedby="error ? `${id}-error` : undefined"></textarea>
    <input v-else :id="id" v-model="model" :type="type" :required="required" :maxlength="maxlength" :min="min" :max="max" :placeholder="placeholder" :aria-invalid="!!error" :aria-describedby="error ? `${id}-error` : undefined">
    <p v-if="error" :id="`${id}-error`" class="field-error">{{ error }}</p>
  </div>
</template>
