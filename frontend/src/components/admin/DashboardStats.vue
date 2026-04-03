<template>
  <div class="stats-grid">
    <div v-for="card in cards" :key="card.label" class="stat-card" :class="`stat-card--${card.color}`">
      <div class="stat-card__icon">{{ card.icon }}</div>
      <div class="stat-card__body">
        <p class="stat-card__label">{{ card.label }}</p>
        <p class="stat-card__value">{{ card.value }}</p>
        <p v-if="card.sub" class="stat-card__sub" :class="{ 'stat-card__sub--up': card.trend > 0, 'stat-card__sub--down': card.trend < 0 }">
          <span v-if="card.trend != null">{{ card.trend > 0 ? '▲' : '▼' }} {{ Math.abs(card.trend) }}% vs last month</span>
          <span v-else>{{ card.sub }}</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ data: { type: Object, required: true } })

const cards = computed(() => {
  const d = props.data
  return [
    {
      label: 'Revenue This Month', icon: '💰', color: 'indigo',
      value: 'ETB ' + Number(d.revenue?.this_month ?? 0).toLocaleString('en-ET', { minimumFractionDigits: 2 }),
      sub: null, trend: d.revenue?.trend_pct ?? null,
    },
    {
      label: 'Bookings This Month', icon: '📋', color: 'blue',
      value: d.bookings?.this_month ?? 0,
      sub: `${d.bookings?.confirmed ?? 0} confirmed · ${d.bookings?.pending ?? 0} pending`,
    },
    {
      label: 'Room Occupancy', icon: '🏨', color: 'green',
      value: (d.rooms?.occupancy_rate ?? 0) + '%',
      sub: `${d.rooms?.occupied ?? 0} / ${d.rooms?.total ?? 0} rooms occupied`,
    },
    {
      label: "Today's Activity", icon: '📅', color: 'amber',
      value: (d.today?.check_ins ?? 0) + ' check-ins',
      sub: `${d.today?.check_outs ?? 0} check-outs today`,
    },
    {
      label: 'New Guests', icon: '👤', color: 'purple',
      value: d.guests?.new_this_month ?? 0,
      sub: 'registered this month',
    },
    {
      label: 'Maintenance', icon: '🔧', color: 'red',
      value: d.rooms?.maintenance ?? 0,
      sub: 'rooms under maintenance',
    },
  ]
})
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}

.stat-card {
  background: #fff; border-radius: 14px; padding: 18px 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f0f0f0;
  display: flex; gap: 14px; align-items: flex-start;
  transition: transform 0.15s;
}
.stat-card:hover { transform: translateY(-2px); }

.stat-card__icon {
  font-size: 1.6rem; width: 44px; height: 44px;
  border-radius: 10px; display: flex; align-items: center;
  justify-content: center; flex-shrink: 0;
}
.stat-card--indigo .stat-card__icon { background: #ede9fe; }
.stat-card--blue   .stat-card__icon { background: #dbeafe; }
.stat-card--green  .stat-card__icon { background: #d1fae5; }
.stat-card--amber  .stat-card__icon { background: #fef3c7; }
.stat-card--purple .stat-card__icon { background: #f3e8ff; }
.stat-card--red    .stat-card__icon { background: #fee2e2; }

.stat-card__body { flex: 1; min-width: 0; }
.stat-card__label { font-size: 11.5px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
.stat-card__value { font-size: 20px; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.stat-card__sub   { font-size: 11.5px; color: #9ca3af; }
.stat-card__sub--up   { color: #10b981; }
.stat-card__sub--down { color: #ef4444; }
</style>
