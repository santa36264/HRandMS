<template>
  <div
    class="star-rating"
    :class="[`star-rating--${size}`, { 'star-rating--readonly': readonly }]"
    role="radiogroup"
    :aria-label="label"
  >
    <button
      v-for="n in 5"
      :key="n"
      type="button"
      class="star-rating__star"
      :class="{
        'star-rating__star--filled':  n <= displayValue,
        'star-rating__star--hovered': !readonly && n <= hovered,
      }"
      :aria-label="`${n} star${n > 1 ? 's' : ''}`"
      :aria-pressed="n === modelValue"
      :disabled="readonly"
      @mouseenter="!readonly && (hovered = n)"
      @mouseleave="!readonly && (hovered = 0)"
      @click="!readonly && emit('update:modelValue', n)"
    >★</button>

    <span v-if="showLabel && modelValue" class="star-rating__label">
      {{ ratingLabels[modelValue - 1] }}
    </span>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: { type: Number, default: 0 },
  readonly:   { type: Boolean, default: false },
  size:       { type: String, default: 'md' },   // sm | md | lg
  label:      { type: String, default: 'Rating' },
  showLabel:  { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const hovered = ref(0)

const displayValue = computed(() =>
  !props.readonly && hovered.value ? hovered.value : props.modelValue
)

const ratingLabels = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent']
</script>

<style scoped>
.star-rating {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.star-rating__star {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  line-height: 1;
  transition: transform 0.1s, color 0.15s;
  color: #d1d5db;
}
.star-rating--readonly .star-rating__star { cursor: default; }

/* Sizes */
.star-rating--sm .star-rating__star { font-size: 16px; }
.star-rating--md .star-rating__star { font-size: 24px; }
.star-rating--lg .star-rating__star { font-size: 32px; }

/* States */
.star-rating__star--filled  { color: #f59e0b; }
.star-rating__star--hovered { color: #fbbf24; transform: scale(1.15); }

.star-rating:not(.star-rating--readonly) .star-rating__star:hover { transform: scale(1.2); }
.star-rating:not(.star-rating--readonly) .star-rating__star:active { transform: scale(0.95); }

.star-rating__label {
  margin-left: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #f59e0b;
}
</style>
