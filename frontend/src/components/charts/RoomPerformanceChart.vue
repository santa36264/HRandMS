<template>
  <div class="chart-card">
    <div class="chart-card__header">
      <div>
        <h3 class="chart-card__title">Room Type Performance</h3>
        <p class="chart-card__sub">Revenue by type · {{ year ?? 'All time' }}</p>
      </div>
    </div>

    <BaseChart v-if="chartData" type="bar" :data="chartData" :options="chartOptions" :height="220" />
    <div v-else class="chart-card__empty">No room data</div>

    <!-- Top rooms table -->
    <div v-if="data.top_rooms?.length" class="top-rooms">
      <p class="top-rooms__title">Top 5 Rooms</p>
      <div class="top-rooms__list">
        <div v-for="(room, i) in data.top_rooms" :key="room.id" class="top-room-row">
          <span class="top-room-row__rank">#{{ i + 1 }}</span>
          <div class="top-room-row__info">
            <span class="top-room-row__name">{{ room.name }}</span>
            <span class="top-room-row__meta">{{ room.room_number }} · {{ room.type }}</span>
          </div>
          <div class="top-room-row__stats">
            <span>ETB {{ fmt(room.revenue) }}</span>
            <span class="top-room-row__bookings">{{ room.bookings }} bookings</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseChart from './BaseChart.vue'
import { PALETTE, PALETTE_ALPHA, tooltipDefaults, gridColor, baseFont } from './chartTheme.js'

const props = defineProps({
  data: { type: Object, required: true },
  year: { type: Number, default: null },
})

const chartData = computed(() => {
  const types = props.data.by_type
  if (!types?.length) return null
  return {
    labels: types.map(t => t.type.charAt(0).toUpperCase() + t.type.slice(1)),
    datasets: [
      {
        label: 'Revenue (ETB)',
        data:  types.map(t => t.revenue),
        backgroundColor: types.map((_, i) => PALETTE[i % PALETTE.length]),
        borderRadius: 6,
        borderSkipped: false,
      },
      {
        label: 'Bookings',
        data:  types.map(t => t.bookings),
        yAxisID: 'y1',
        backgroundColor: types.map((_, i) => PALETTE_ALPHA(PALETTE[i % PALETTE.length], 0.4)),
        borderRadius: 6,
        borderSkipped: false,
      },
    ],
  }
})

const chartOptions = {
  indexAxis: 'y',
  plugins: {
    tooltip: { ...tooltipDefaults },
    legend: { position: 'top', labels: { font: baseFont, usePointStyle: true } },
  },
  scales: {
    x:  { grid: { color: gridColor }, ticks: { font: baseFont, callback: v => 'ETB ' + v.toLocaleString() } },
    y:  { grid: { color: gridColor }, ticks: { font: baseFont } },
    y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: baseFont } },
  },
}

function fmt(n) { return Number(n).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
</script>

<style scoped src="./chart-card.css" />
<style scoped>
.top-rooms { margin-top: 20px; border-top: 1px solid #f3f4f6; padding-top: 16px; }
.top-rooms__title { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
.top-rooms__list  { display: flex; flex-direction: column; gap: 8px; }

.top-room-row {
  display: flex; align-items: center; gap: 12px;
  padding: 8px 10px; border-radius: 8px; background: #f9fafb;
}
.top-room-row__rank  { font-size: 13px; font-weight: 800; color: #4f46e5; width: 24px; }
.top-room-row__info  { flex: 1; }
.top-room-row__name  { font-size: 13px; font-weight: 600; color: #1a202c; display: block; }
.top-room-row__meta  { font-size: 11px; color: #9ca3af; }
.top-room-row__stats { text-align: right; font-size: 13px; font-weight: 600; color: #374151; }
.top-room-row__bookings { display: block; font-size: 11px; color: #9ca3af; font-weight: 400; }
</style>
