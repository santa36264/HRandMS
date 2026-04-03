<template>
  <div class="base-chart" :style="{ height: height + 'px' }">
    <canvas ref="canvasRef" />
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  type:    { type: String,  required: true },
  data:    { type: Object,  required: true },
  options: { type: Object,  default: () => ({}) },
  height:  { type: Number,  default: 300 },
})

const canvasRef = ref(null)
let chart = null

function buildChart() {
  if (chart) { chart.destroy(); chart = null }
  if (!canvasRef.value) return
  chart = new Chart(canvasRef.value, {
    type:    props.type,
    data:    props.data,
    options: { responsive: true, maintainAspectRatio: false, ...props.options },
  })
}

watch(() => [props.data, props.options], buildChart, { deep: true })
onMounted(buildChart)
onUnmounted(() => chart?.destroy())
</script>

<style scoped>
.base-chart { position: relative; width: 100%; }
</style>
