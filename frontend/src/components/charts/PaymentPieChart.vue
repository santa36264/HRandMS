<template>
  <div class="chart-card">
    <div class="chart-card__header">
      <div>
        <h3 class="chart-card__title">Payment Breakdown</h3>
        <p class="chart-card__sub">By gateway · {{ year ?? 'All time' }}</p>
      </div>
      <span class="chart-card__badge">Total: ETB {{ fmt(data.total_revenue) }}</span>
    </div>

    <div class="pie-layout">
      <!-- Doughnut -->
      <div class="pie-layout__chart">
        <BaseChart v-if="chartData" type="doughnut" :data="chartData" :options="chartOptions" :height="220" />
        <div v-else class="chart-card__empty">No payment data</div>
      </div>

      <!-- Legend table -->
      <div class="pie-layout__legend">
        <div
          v-for="(gw, i) in data.by_gateway"
          :key="gw.gateway"
          class="pie-legend-row"
        >
          <span class="pie-legend-row__dot" :style="{ background: PALETTE[i % PALETTE.length] }" />
          <div class="pie-legend-row__info">
            <span class="pie-legend-row__label">{{ gw.label }}</span>
            <span class="pie-legend-row__pct">{{ gw.percentage }}%</span>
          </div>
          <div class="pie-legend-row__values">
            <span>ETB {{ fmt(gw.revenue) }}</span>
            <span class="pie-legend-row__txn">{{ gw.transactions }} txns</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseChart from './BaseChart.vue'
import { PALETTE, tooltipDefaults, legendDefaults } from './chartTheme.js'

const props = defineProps({
  data: { type: Object, required: true },
  year: { type: Number, default: null },
})

const chartData = computed(() => {
  const gateways = props.data.by_gateway
  if (!gateways?.length) return null
  return {
    labels: gateways.map(g => g.label),
    datasets: [{
      data:            gateways.map(g => g.revenue),
      backgroundColor: gateways.map((_, i) => PALETTE[i % PALETTE.length]),
      borderWidth:     2,
      borderColor:     '#fff',
      hoverOffset:     8,
    }],
  }
})

const chartOptions = {
  cutout: '65%',
  plugins: {
    tooltip: { ...tooltipDefaults, callbacks: {
      label: ctx => ` ETB ${ctx.parsed.toLocaleString()} (${ctx.dataset.data[ctx.dataIndex] > 0
        ? Math.round((ctx.parsed / ctx.dataset.data.reduce((a, b) => a + b, 0)) * 100)
        : 0}%)`,
    }},
    legend: { ...legendDefaults, display: false },
  },
}

function fmt(n) { return Number(n).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
</script>

<style scoped src="./chart-card.css" />
<style scoped>
.pie-layout { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
.pie-layout__chart  { flex: 0 0 220px; }
.pie-layout__legend { flex: 1; min-width: 180px; display: flex; flex-direction: column; gap: 10px; }

.pie-legend-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px; border-radius: 8px; background: #f9fafb;
}
.pie-legend-row__dot {
  width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.pie-legend-row__info {
  flex: 1; display: flex; justify-content: space-between;
  font-size: 13px; font-weight: 600; color: #1a202c;
}
.pie-legend-row__pct { color: #6b7280; }
.pie-legend-row__values {
  display: flex; flex-direction: column; align-items: flex-end;
  font-size: 12px; color: #374151;
}
.pie-legend-row__txn { color: #9ca3af; font-size: 11px; }
</style>
