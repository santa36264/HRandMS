<template>
  <div class="chart-card">
    <div class="chart-card__header">
      <div>
        <h3 class="chart-card__title">Monthly Revenue</h3>
        <p class="chart-card__sub">ETB · {{ year }}</p>
      </div>
      <div class="chart-card__meta">
        <span class="chart-card__badge">Total: ETB {{ fmt(summary.total_revenue) }}</span>
        <span class="chart-card__badge chart-card__badge--peak">Peak: {{ summary.peak_month }}</span>
      </div>
    </div>
    <BaseChart v-if="chartData" type="bar" :data="chartData" :options="chartOptions" :height="280" />
    <div v-else class="chart-card__empty">No revenue data for {{ year }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseChart from './BaseChart.vue'
import { PALETTE, PALETTE_ALPHA, tooltipDefaults, gridColor, baseFont } from './chartTheme.js'

const props = defineProps({
  data:    { type: Object, required: true },
  year:    { type: Number, required: true },
})

const summary = computed(() => ({
  total_revenue: props.data.total_revenue ?? 0,
  peak_month:    props.data.peak_month    ?? '—',
}))

const chartData = computed(() => {
  const monthly = props.data.monthly
  if (!monthly?.length) return null
  return {
    labels: monthly.map(m => m.month_label),
    datasets: [
      {
        label: 'Revenue (ETB)',
        data:  monthly.map(m => m.revenue),
        backgroundColor: monthly.map((_, i) =>
          i === indexOfMax(monthly.map(m => m.revenue))
            ? PALETTE[0]
            : PALETTE_ALPHA(PALETTE[0], 0.6)
        ),
        borderColor:  PALETTE[0],
        borderWidth:  1,
        borderRadius: 6,
        borderSkipped: false,
      },
      {
        label: 'Transactions',
        data:  monthly.map(m => m.transactions),
        type:  'line',
        yAxisID: 'y1',
        borderColor: PALETTE[2],
        backgroundColor: PALETTE_ALPHA(PALETTE[2], 0.1),
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: PALETTE[2],
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
        ? ` ${ctx.parsed.y} transactions`
        : ` ETB ${ctx.parsed.y.toLocaleString()}`,
    }},
    legend: { position: 'top', labels: { font: baseFont, usePointStyle: true } },
  },
  scales: {
    x:  { grid: { color: gridColor }, ticks: { font: baseFont } },
    y:  { grid: { color: gridColor }, ticks: { font: baseFont, callback: v => 'ETB ' + v.toLocaleString() } },
    y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: baseFont } },
  },
}

function indexOfMax(arr) { return arr.indexOf(Math.max(...arr)) }
function fmt(n) { return Number(n).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
</script>

<style scoped src="./chart-card.css" />
