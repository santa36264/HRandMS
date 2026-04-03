import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  // ── Public ──────────────────────────────────────────────────────
  {
    path: '/',
    component: () => import('../layouts/GuestLayout.vue'),
    children: [
      { path: '',        name: 'home',    component: () => import('../views/HomeView.vue') },
      { path: 'rooms',        name: 'rooms',       component: () => import('../views/rooms/RoomsView.vue') },
      { path: 'rooms/:id',    name: 'room-detail', component: () => import('../views/rooms/RoomDetailView.vue') },
      { path: 'rooms/:id/book', name: 'book', component: () => import('../views/rooms/BookingConfirmView.vue'), meta: { requiresAuth: true } },
      { path: 'rooms/:id/pay',  name: 'pay',  component: () => import('../views/rooms/PaymentView.vue'), meta: { requiresAuth: true } },
      { path: 'booking/payment-result', name: 'payment-result', component: () => import('../views/rooms/PaymentResultView.vue') },
      { path: 'profile', name: 'profile', component: () => import('../views/ProfileView.vue'), meta: { requiresAuth: true } },
      { path: 'terms',   name: 'terms',   component: () => import('../views/TermsView.vue') },
      { path: 'privacy', name: 'privacy', component: () => import('../views/PrivacyView.vue') },
    ],
  },

  // ── Auth ─────────────────────────────────────────────────────────
  {
    path: '/auth',
    component: () => import('../layouts/AuthLayout.vue'),
    meta: { guestOnly: true },
    children: [
      { path: 'login',           name: 'login',           component: () => import('../views/auth/LoginView.vue') },
      { path: 'register',        name: 'register',        component: () => import('../views/auth/RegisterView.vue') },
      { path: 'forgot-password', name: 'forgot-password', component: () => import('../views/auth/ForgotPasswordView.vue') },
      { path: 'reset-password',  name: 'reset-password',  component: () => import('../views/auth/ResetPasswordView.vue') },
    ],
  },
  {
    path: '/auth/google/callback',
    name: 'google-callback',
    component: () => import('../views/auth/GoogleCallbackView.vue'),
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('../views/auth/VerifyEmailView.vue'),
    meta: { requiresAuth: true },
  },

  // ── Admin ─────────────────────────────────────────────────────────
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      { path: '',            name: 'admin.dashboard',   component: () => import('../views/admin/AdminDashboard.vue') },
      { path: 'analytics',   name: 'admin.analytics',   component: () => import('../views/admin/AnalyticsDashboard.vue') },
      { path: 'bookings',    name: 'admin.bookings',    component: () => import('../views/admin/BookingsView.vue') },
      { path: 'rooms',       name: 'admin.rooms',       component: () => import('../views/admin/RoomsView.vue') },
      { path: 'guests',      name: 'admin.guests',      component: () => import('../views/admin/GuestsView.vue') },
      { path: 'payments',    name: 'admin.payments',    component: () => import('../views/admin/PaymentsView.vue') },
      { path: 'maintenance', name: 'admin.maintenance', component: () => import('../views/admin/MaintenanceView.vue') },
      { path: 'reviews',     name: 'admin.reviews',     component: () => import('../views/admin/ReviewsView.vue') },
      { path: 'settings',    name: 'admin.settings',    component: () => import('../views/admin/SettingsView.vue') },
      { path: 'refunds',     name: 'admin.refunds',     component: () => import('../views/admin/RefundsView.vue') },
    ],
  },

  // ── Fallback ──────────────────────────────────────────────────────
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../views/NotFoundView.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

// ── Navigation Guards ─────────────────────────────────────────────
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Restore session if token exists but user not loaded
  if (auth.token && !auth.user) {
    await auth.fetchMe()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'home' }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.isAdmin ? { path: '/admin' } : { name: 'home' }
  }
})

export default router
