<template>
  <div class="analytics">

    <!-- Header -->
    <div class="analytics__header">
      <div>
        <h1 class="analytics__title">Analytics Dashboard</h1>
        <p class="analytics__sub">Hotel performance overview</p>
      </div>
      <div class="analytics__controls">
        <select class="analytics__year-select" v-model="selectedYear" @change="loadAll">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="analytics__refresh" :class="{ 'analytics__refresh--loading': loading }" @click="loadAll">
          ↻
        </button>
      </div>
    </div>

    <!-- Stat cards -->
    <DashboardStats v-if="dashboard" :data="dashboard" class="analytics__stats" />
    <div v-else class="analytics__skeleton-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card" />
    </div>

    <!-- Charts grid -->
    <div class="analytics__charts">

      <!-- Revenue bar chart — full width -->
      <div class="analytics__chart analytics__chart--full">
        <RevenueBarChart
          v-if="revenueData"
          :data="revenueData"
          :year="selectedYear"
        />
        <ChartSkeleton v-else title="Monthly Revenue" />
      </div>

      <!-- Occupancy line chart -->
      <div class="analytics__chart analytics__chart--half">
        <OccupancyLineChart
          v-if="occupancyData"
          :data="occupancyData"
          :year="selectedYear"
        />
        <ChartSkeleton v-else title="Occupancy Rate" />
      </div>

      <!-- Payment pie chart -->
      <div class="analytics__chart analytics__chart--half">
        <PaymentPieChart
          v-if="paymentData"
          :data="paymentData"
          :year="selectedYear"
        />
        <ChartSkeleton v-else title="Payment Breakdown" />
      </div>

      <!-- Room performance — full width -->
      <div class="analytics__chart analytics__chart--full">
        <RoomPerformanceChart
          v-if="roomData"
          :data="roomData"
          :year="selectedYear"
        />
        <ChartSkeleton v-else title="Room Performance" />
      </div>

    </div>

    <!-- Global error -->
    <div v-if="error" class="analytics__error">⚠️ {{ error }}</div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardStats       from '../../components/admin/DashboardStats.vue'
import RevenueBarChart      from '../../components/charts/RevenueBarChart.vue'
import OccupancyLineChart   from '../../components/charts/OccupancyLineChart.vue'
import PaymentPieChart      from '../../components/charts/PaymentPieChart.vue'
import RoomPerformanceChart from '../../components/charts/RoomPerformanceChart.vue'
import ChartSkeleton        from '../../components/charts/ChartSkeleton.vue'
import { analyticsService } from '../../services/analytics'

// ── State ──────────────────────────────────────────
const selectedYear  = ref(new Date().getFullYear())
const years         = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i)

const dashboard    = ref(null)
const revenueData  = ref(null)
const occupancyData = ref(null)
const paymentData  = ref(null)
const roomData     = ref(null)
const loading      = ref(false)
const error        = ref('')

// ── Load ───────────────────────────────────────────
async function loadAll() {
  loading.value = true
  error.value   = ''

  await Promise.allSettled([
    loadDashboard(),
    loadRevenue(),
    loadOccupancy(),
    loadPayments(),
    loadRooms(),
  ])

  loading.value = false
}

async function loadDashboard() {
  const { data } = await analyticsService.dashboard()
  dashboard.value = data.data
}

async function loadRevenue() {
  const { data } = await analyticsService.revenue(selectedYear.value)
  revenueData.value = data.data
}

async function loadOccupancy() {
  const { data } = await analyticsService.occupancy(selectedYear.value)
  occupancyData.value = data.data
}

async function loadPayments() {
  const { data } = await analyticsService.payments(selectedYear.value)
  paymentData.value = data.data
}

async function loadRooms() {
  const { data } = await analyticsService.rooms(selectedYear.value)
  roomData.value = data.data
}

onMounted(loadAll)
</script>

<style scoped>
.analytics { max-width: 1280px; margin: 0 auto; padding: 28px 20px; }

.analytics__header {
  display: flex; justify-content: space-between; align-items: flex-start;
  flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
}
.analytics__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.analytics__sub   { font-size: 13px; color: #6b7280; }

.analytics__controls { display: flex; gap: 10px; align-items: center; }
.analytics__year-select {
  padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #1a202c; background: #fff; cursor: pointer; outline: none;
}
.analytics__year-select:focus { border-color: #4f46e5; }

.analytics__refresh {
  width: 36px; height: 36px; border-radius: 8px;
  border: 1.5px solid #e5e7eb; background: #fff;
  font-size: 18px; cursor: pointer; transition: transform 0.3s;
  display: flex; align-items: center; justify-content: center;
}
.analytics__refresh:hover { background: #f3f4f6; }
.analytics__refresh--loading { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.analytics__stats { margin-bottom: 24px; }

/* Skeleton cards */
.analytics__skeleton-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px; margin-bottom: 24px;
}
.skeleton-card {
  height: 100px; border-radius: 14px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* Charts grid */
.analytics__charts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.analytics__chart--full { grid-column: 1 / -1; }
.analytics__chart--half { grid-column: span 1; }

.analytics__error {
  margin-top: 20px; padding: 14px 18px;
  background: #fee2e2; border-radius: 10px;
  color: #991b1b; font-size: 14px;
}

@media (max-width: 768px) {
  .analytics__charts { grid-template-columns: 1fr; }
  .analytics__chart--half { grid-column: 1 / -1; }
}
</style>
