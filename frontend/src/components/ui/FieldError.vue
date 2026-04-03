<template>
  <p v-if="message" role="alert" class="field-error">
    <span class="field-error__icon" aria-hidden="true">⚠</span>
    {{ message }}
  </p>
</template>

<script setup>
defineProps({
  message: { type: String, default: null },
})
</script>

<style scoped>
.field-error {
  display: flex;
  align-items: center;
  gap: 4px;
  margin: 4px 0 0;
  font-size: 0.8rem;
  color: #dc2626;
  line-height: 1.4;
}
.field-error__icon { font-size: 0.75rem; }
</style>
