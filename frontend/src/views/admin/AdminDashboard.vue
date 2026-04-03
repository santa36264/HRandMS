<template>
  <div class="dashboard">

    <!-- Welcome bar -->
    <div class="dashboard__welcome">
      <div>
        <h1 class="dashboard__title">Good {{ greeting }}, {{ firstName }} 👋</h1>
        <p class="dashboard__sub">Here's what's happening at the hotel today.</p>
      </div>
      <router-link to="/admin/analytics" class="dashboard__analytics-link">
        View full analytics →
      </router-link>
    </div>

    <!-- KPI cards -->
    <DashboardStats v-if="dashboard" :data="dashboard" class="dashboard__stats" />
    <div v-else class="dashboard__skeleton-grid">
      <div v-for="i in 6" :key="i" class="skeleton-card" />
    </div>

    <!-- Quick charts row -->
    <div class="dashboard__charts">
      <div class="dashboard__chart-card">
        <div class="dashboard__chart-header">
          <span class="dashboard__chart-title">Revenue This Year</span>
          <router-link to="/admin/analytics" class="dashboard__chart-link">Details</router-link>
        </div>
        <RevenueBarChart v-if="revenueData" :data="revenueData" :year="currentYear" :compact="true" />
        <ChartSkeleton v-else title="" />
      </div>

      <div class="dashboard__chart-card">
        <div class="dashboard__chart-header">
          <span class="dashboard__chart-title">Occupancy Rate</span>
          <router-link to="/admin/analytics" class="dashboard__chart-link">Details</router-link>
        </div>
        <OccupancyLineChart v-if="occupancyData" :data="occupancyData" :year="currentYear" :compact="true" />
        <ChartSkeleton v-else title="" />
      </div>
    </div>

    <!-- Quick actions -->
    <div class="dashboard__actions">
      <h2 class="dashboard__section-title">Quick Actions</h2>
      <div class="dashboard__action-grid">
        <router-link v-for="action in quickActions" :key="action.to" :to="action.to" class="action-card">
          <span class="action-card__icon">{{ action.icon }}</span>
          <span class="action-card__label">{{ action.label }}</span>
        </router-link>
      </div>
    </div>

    <div v-if="error" class="dashboard__error">⚠️ {{ error }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import DashboardStats    from '../../components/admin/DashboardStats.vue'
import RevenueBarChart   from '../../components/charts/RevenueBarChart.vue'
import OccupancyLineChart from '../../components/charts/OccupancyLineChart.vue'
import ChartSkeleton     from '../../components/charts/ChartSkeleton.vue'
import { analyticsService } from '../../services/analytics'

const auth        = useAuthStore()
const dashboard   = ref(null)
const revenueData = ref(null)
const occupancyData = ref(null)
const error       = ref('')
const currentYear = new Date().getFullYear()

const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? 'Admin')
const greeting  = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 17) return 'afternoon'
  return 'evening'
})

const quickActions = [
  { to: '/admin/bookings',    icon: '📋', label: 'Manage Bookings' },
  { to: '/admin/rooms',       icon: '🛏️', label: 'Manage Rooms' },
  { to: '/admin/payments',    icon: '💳', label: 'View Payments' },
  { to: '/admin/guests',      icon: '👥', label: 'Guest List' },
  { to: '/admin/maintenance', icon: '🔧', label: 'Maintenance' },
  { to: '/admin/reviews',     icon: '⭐', label: 'Reviews' },
]

async function load() {
  try {
    const [dashRes, revRes, occRes] = await Promise.allSettled([
      analyticsService.dashboard(),
      analyticsService.revenue(currentYear),
      analyticsService.occupancy(currentYear),
    ])
    if (dashRes.status === 'fulfilled') dashboard.value   = dashRes.value.data.data
    if (revRes.status  === 'fulfilled') revenueData.value = revRes.value.data.data
    if (occRes.status  === 'fulfilled') occupancyData.value = occRes.value.data.data
  } catch (e) {
    error.value = 'Failed to load dashboard data.'
  }
}

onMounted(load)
</script>

<style scoped>
.dashboard { max-width: 1280px; margin: 0 auto; padding: 28px 20px; }

.dashboard__welcome {
  display: flex; justify-content: space-between; align-items: flex-start;
  flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
}
.dashboard__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.dashboard__sub   { font-size: 13px; color: #6b7280; }
.dashboard__analytics-link {
  font-size: 13.5px; color: #4f46e5; font-weight: 600;
  text-decoration: none; padding: 8px 14px;
  border: 1.5px solid #e0e7ff; border-radius: 8px;
  transition: background 0.15s;
}
.dashboard__analytics-link:hover { background: #eef2ff; }

.dashboard__stats { margin-bottom: 28px; }

/* Skeleton */
.dashboard__skeleton-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px; margin-bottom: 28px;
}
.skeleton-card {
  height: 100px; border-radius: 14px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* Charts row */
.dashboard__charts {
  display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px;
}
.dashboard__chart-card {
  background: #fff; border-radius: 14px; padding: 20px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #f0f0f0;
}
.dashboard__chart-header {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;
}
.dashboard__chart-title { font-size: 14px; font-weight: 700; color: #1a202c; }
.dashboard__chart-link  { font-size: 12.5px; color: #4f46e5; text-decoration: none; font-weight: 600; }
.dashboard__chart-link:hover { text-decoration: underline; }

/* Quick actions */
.dashboard__section-title { font-size: 15px; font-weight: 700; color: #1a202c; margin-bottom: 14px; }
.dashboard__action-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;
}
.action-card {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 18px 12px; background: #fff; border-radius: 12px;
  border: 1.5px solid #f0f0f0; text-decoration: none;
  transition: border-color 0.15s, transform 0.15s, box-shadow 0.15s;
}
.action-card:hover {
  border-color: #c7d2fe; transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(79,70,229,0.1);
}
.action-card__icon  { font-size: 1.8rem; }
.action-card__label { font-size: 12.5px; font-weight: 600; color: #374151; text-align: center; }

.dashboard__error {
  margin-top: 20px; padding: 14px 18px;
  background: #fee2e2; border-radius: 10px; color: #991b1b; font-size: 14px;
}

@media (max-width: 768px) {
  .dashboard__charts { grid-template-columns: 1fr; }
}
</style>
