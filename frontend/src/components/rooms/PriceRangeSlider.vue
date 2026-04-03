<template>
  <div class="price-slider">
    <div class="price-slider__labels">
      <span class="price-slider__label">Price per night</span>
      <span class="price-slider__value">${{ modelValue.min }} – ${{ modelValue.max === max ? max + '+' : modelValue.max }}</span>
    </div>

    <div class="price-slider__track-wrapper" ref="trackRef">
      <div class="price-slider__track">
        <div class="price-slider__fill" :style="fillStyle" />
      </div>

      <!-- Min thumb -->
      <input
        type="range"
        class="price-slider__input price-slider__input--min"
        :min="min" :max="max" :step="step"
        :value="modelValue.min"
        @input="onMinInput"
        aria-label="Minimum price"
      />

      <!-- Max thumb -->
      <input
        type="range"
        class="price-slider__input price-slider__input--max"
        :min="min" :max="max" :step="step"
        :value="modelValue.max"
        @input="onMaxInput"
        aria-label="Maximum price"
      />
    </div>

    <div class="price-slider__ticks">
      <span v-for="tick in ticks" :key="tick">${{ tick }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({ min: 0, max: 1000 }) },
  min:        { type: Number, default: 0 },
  max:        { type: Number, default: 1000 },
  step:       { type: Number, default: 10 },
})
const emit = defineEmits(['update:modelValue'])

const ticks = computed(() => {
  const count = 5
  return Array.from({ length: count }, (_, i) =>
    Math.round(props.min + (i / (count - 1)) * (props.max - props.min))
  )
})

const fillStyle = computed(() => {
  const range = props.max - props.min
  const left  = ((props.modelValue.min - props.min) / range) * 100
  const right = ((props.max - props.modelValue.max) / range) * 100
  return { left: `${left}%`, right: `${right}%` }
})

function onMinInput(e) {
  const val = Math.min(Number(e.target.value), props.modelValue.max - props.step)
  emit('update:modelValue', { ...props.modelValue, min: val })
}

function onMaxInput(e) {
  const val = Math.max(Number(e.target.value), props.modelValue.min + props.step)
  emit('update:modelValue', { ...props.modelValue, max: val })
}
</script>

<style scoped>
.price-slider { width: 100%; }

.price-slider__labels {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 13px;
}
.price-slider__label { color: #6b7280; font-weight: 500; }
.price-slider__value { color: #4f46e5; font-weight: 700; }

.price-slider__track-wrapper {
  position: relative;
  height: 20px;
  display: flex;
  align-items: center;
}

.price-slider__track {
  position: absolute;
  width: 100%;
  height: 4px;
  background: #e5e7eb;
  border-radius: 99px;
}

.price-slider__fill {
  position: absolute;
  height: 100%;
  background: #4f46e5;
  border-radius: 99px;
}

.price-slider__input {
  position: absolute;
  width: 100%;
  height: 4px;
  appearance: none;
  background: transparent;
  pointer-events: none;
  outline: none;
}

.price-slider__input::-webkit-slider-thumb {
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #4f46e5;
  border: 2px solid #fff;
  box-shadow: 0 1px 6px rgba(79,70,229,0.4);
  pointer-events: all;
  cursor: pointer;
  transition: transform 0.15s;
}
.price-slider__input::-webkit-slider-thumb:hover { transform: scale(1.2); }
.price-slider__input::-moz-range-thumb {
  width: 18px; height: 18px;
  border-radius: 50%;
  background: #4f46e5;
  border: 2px solid #fff;
  pointer-events: all;
  cursor: pointer;
}

.price-slider__ticks {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 11px;
  color: #9ca3af;
}
</style>
