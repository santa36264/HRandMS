import { useToastStore } from '../stores/toast'
import { parseApiError } from './useApiError'

/**
 * Convenience composable for showing toasts.
 *
 * Usage:
 *   const toast = useToast()
 *   toast.success('Booking confirmed!')
 *   toast.apiError(err)          // auto-parses axios error
 *   toast.validationError(err)   // shows first field error or fallback
 */
export function useToast() {
  const store = useToastStore()

  return {
    success: (msg, duration)  => store.add('success', msg, duration),
    error:   (msg, duration)  => store.add('error',   msg, duration),
    warning: (msg, duration)  => store.add('warning', msg, duration),
    info:    (msg, duration)  => store.add('info',    msg, duration),

    /** Parse an axios error and show the appropriate toast */
    apiError(err, fallback = 'Something went wrong. Please try again.') {
      const parsed = parseApiError(err)
      // Don't toast 422 — those are shown inline via ErrorBanner
      if (parsed.type === 'validation') return
      store.add('error', parsed.message || fallback, 5000)
    },

    /** Show first field error or the summary message */
    validationError(err) {
      const parsed = parseApiError(err)
      const first  = Object.values(parsed.errors ?? {})[0]?.[0]
      store.add('error', first || parsed.message, 5000)
    },
  }
}
