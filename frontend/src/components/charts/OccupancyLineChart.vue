<template>
  <div class="chart-card">
    <div class="chart-card__header">
      <div>
        <h3 class="chart-card__title">Occupancy Rate</h3>
        <p class="chart-card__sub">% of available room-nights · {{ year }}</p>
      </div>
      <div class="chart-card__meta">
        <span class="chart-card__badge">Avg: {{ data.avg_occupancy }}%</span>
        <span class="chart-card__badge chart-card__badge--peak">Peak: {{ data.peak_month }} ({{ data.peak_occupancy }}%)</span>
      </div>
    </div>
    <BaseChart v-if="chartData" type="line" :data="chartData" :options="chartOptions" :height="280" />
    <div v-else class="chart-card__empty">No occupancy data for {{ year }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseChart from './BaseChart.vue'
import { PALETTE, PALETTE_ALPHA, tooltipDefaults, gridColor, baseFont } from './chartTheme.js'

const props = defineProps({
  data: { type: Object, required: true },
  year: { type: Number, required: true },
})

const chartData = computed(() => {
  const monthly = props.data.monthly
  if (!monthly?.length) return null
  return {
    labels: monthly.map(m => m.month_label),
    datasets: [
      {
        label: 'Occupancy Rate (%)',
        data:  monthly.map(m => m.occupancy_rate),
        borderColor:     PALETTE[3],
        backgroundColor: PALETTE_ALPHA(PALETTE[3], 0.12),
        borderWidth: 2.5,
        pointRadius: 5,
        pointBackgroundColor: PALETTE[3],
        pointHoverRadius: 7,
        tension: 0.4,
        fill: true,
      },
      {
        label: 'Booked Nights',
        data:  monthly.map(m => m.booked_nights),
        yAxisID: 'y1',
        borderColor: PALETTE[1],
        backgroundColor: 'transparent',
        borderWidth: 1.5,
        borderDash: [5, 4],
        pointRadius: 3,
        tension: 0.4,
        fill: false,
      },
    ],
  }
})

const chartOptions = {
  plugins: {
    tooltip: { ...tooltipDefaults, callbacks: {
      label: ctx => ctx.dataset.yAxisID === 'y1'
        ? ` ${ctx.parsed.y} nights booked`
        : ` ${ctx.parsed.y}% occupancy`,
    }},
    legend: { position: 'top', labels: { font: baseFont, usePointStyle: true } },
    annotation: {
      annotations: {
        target: {
          type: 'line', yMin: 70, yMax: 70,
          borderColor: '#f59e0b', borderWidth: 1.5,
          borderDash: [6, 3],
          label: { content: '70% target', enabled: true, position: 'end', font: { size: 11 } },
        },
      },
    },
  },
  scales: {
    x:  { grid: { color: gridColor }, ticks: { font: baseFont } },
    y:  { min: 0, max: 100, grid: { color: gridColor }, ticks: { font: baseFont, callback: v => v + '%' } },
    y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: baseFont } },
  },
}
</script>

<style scoped src="./chart-card.css" />
