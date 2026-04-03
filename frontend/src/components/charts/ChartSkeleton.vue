<template>
  <div class="chart-skeleton">
    <div class="chart-skeleton__header">
      <div class="chart-skeleton__title-block">
        <div class="skel skel--title" />
        <div class="skel skel--sub" />
      </div>
      <div class="skel skel--badge" />
    </div>
    <div class="chart-skeleton__body">
      <div v-if="type === 'bar'" class="skel-bars">
        <div v-for="i in 12" :key="i" class="skel-bar" :style="{ height: (30 + Math.random() * 60) + '%' }" />
      </div>
      <div v-else-if="type === 'pie'" class="skel-pie">
        <div class="skel-pie__circle" />
      </div>
      <div v-else class="skel-line">
        <svg viewBox="0 0 300 80" preserveAspectRatio="none">
          <polyline points="0,60 30,40 60,50 90,20 120,35 150,15 180,30 210,10 240,25 270,5 300,20"
            fill="none" stroke="#e5e7eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>
    <p class="chart-skeleton__label">Loading {{ title }}…</p>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'chart' },
  type:  { type: String, default: 'line' },
})
</script>

<style scoped>
.chart-skeleton {
  background: #fff; border-radius: 14px; padding: 20px 22px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f0f0f0;
}
.chart-skeleton__header {
  display: flex; justify-content: space-between; margin-bottom: 18px;
}
.chart-skeleton__title-block { display: flex; flex-direction: column; gap: 6px; }

.skel {
  border-radius: 6px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}
.skel--title  { width: 140px; height: 16px; }
.skel--sub    { width: 90px;  height: 12px; }
.skel--badge  { width: 100px; height: 24px; border-radius: 99px; }

.chart-skeleton__body { height: 220px; display: flex; align-items: flex-end; }

.skel-bars { display: flex; align-items: flex-end; gap: 6px; width: 100%; height: 100%; }
.skel-bar  { flex: 1; border-radius: 4px 4px 0 0; background: #f0f0f0; animation: shimmer 1.2s infinite; }

.skel-pie { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.skel-pie__circle {
  width: 160px; height: 160px; border-radius: 50%;
  background: conic-gradient(#e5e7eb 0% 40%, #f0f0f0 40% 70%, #e8e8e8 70% 100%);
}

.skel-line { width: 100%; height: 100%; }
.skel-line svg { width: 100%; height: 100%; }

.chart-skeleton__label {
  text-align: center; font-size: 12px; color: #9ca3af; margin-top: 12px;
}

@keyframes shimmer { to { background-position: -200% 0; } }
</style>
