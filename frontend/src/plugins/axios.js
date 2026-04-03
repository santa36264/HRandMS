import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

// Attach token if present
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Global response error interceptor
// Handles: 401 (session expired), 403 (forbidden), 500 (server error),
//          503 (maintenance), and network/timeout failures.
// 422 validation errors are intentionally NOT handled here — components
// display those inline via ErrorBanner / FieldError.
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status

    // Lazy-import stores/router to avoid circular dependency at module load time
    const getToast  = () => import('../stores/toast').then(m => m.useToastStore())
    const getAuth   = () => import('../stores/auth').then(m => m.useAuthStore())
    const getRouter = () => import('../router/index.js').then(m => m.default)

    // ── 401 Unauthorised ────────────────────────────────────────────────────
    // Session expired or token invalid — clear auth and redirect to login.
    // _silent401: true skips toast/redirect (used by /me session restore).
    if (status === 401) {
      const isSilent = error.config?._silent401 === true
      const [auth, toast, router] = await Promise.all([getAuth(), getToast(), getRouter()])
      auth.clearAuth()
      if (!isSilent) {
        toast.add('warning', 'Your session has expired. Please log in again.', 5000)
        if (router.currentRoute.value.name !== 'login') {
          router.push({ name: 'login' })
        }
      }
    }
    // ── 403 Forbidden ───────────────────────────────────────────────────────
    // Authenticated but not authorised — show a toast and redirect home.
    else if (status === 403) {
      const [toast, router] = await Promise.all([getToast(), getRouter()])
      const message = error.response?.data?.message
        ?? 'You do not have permission to perform this action.'
      toast.add('error', message, 6000)
      // Only redirect if the user navigated directly to a forbidden route
      if (error.config?._redirectOn403 !== false) {
        const current = router.currentRoute.value
        if (current.name !== 'home' && current.name !== 'login') {
          router.push({ name: 'home' })
        }
      }
    }

    // ── 500 Internal Server Error ────────────────────────────────────────────
    // Generic server fault — show a persistent error toast.
    else if (status === 500) {
      const toast = await getToast()
      const message = error.response?.data?.message
        ?? 'Something went wrong on our end. Please try again shortly.'
      toast.add('error', message, 7000)
    }

    // ── 503 Service Unavailable ──────────────────────────────────────────────
    // Maintenance mode — sticky toast (duration 0 = no auto-dismiss).
    else if (status === 503) {
      const toast = await getToast()
      toast.add('warning', 'The service is under maintenance. Please check back soon.', 0)
    }

    // ── Network / Timeout ────────────────────────────────────────────────────
    // No response at all — offline or DNS failure.
    else if (!error.response) {
      // Skip toast for silent background calls
      if (error.config?._silent) return Promise.reject(error)

      const toast = await getToast()
      const isTimeout = error.code === 'ECONNABORTED' || error.message?.includes('timeout')
      toast.add(
        'error',
        isTimeout
          ? 'The request timed out. Please check your connection and try again.'
          : 'Unable to reach the server. Please check your internet connection.',
        6000
      )
    }

    return Promise.reject(error)
  }
)

export default api
