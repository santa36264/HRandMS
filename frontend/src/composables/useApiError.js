import { ref, computed } from 'vue'

/**
 * useApiError
 *
 * Normalises any axios error into a consistent shape so every
 * component can handle API failures the same way.
 *
 * Returned shape:
 * {
 *   message : string
 *   errors  : Record<string, string[]>  — field-level 422 errors
 *   status  : number | null
 *   type    : 'validation' | 'auth' | 'forbidden' | 'not_found'
 *             | 'server' | 'network' | 'timeout' | 'unknown'
 * }
 */

const STATUS_MESSAGES = {
  400: 'The request was invalid. Please check your input.',
  401: 'Your session has expired. Please log in again.',
  403: 'You do not have permission to perform this action.',
  404: 'The requested resource was not found.',
  408: 'The request timed out. Please try again.',
  409: 'A conflict occurred. The resource may already exist.',
  422: 'Please fix the highlighted errors and try again.',
  429: 'Too many requests. Please wait a moment and try again.',
  500: 'Something went wrong on our end. Please try again shortly.',
  502: 'The server is temporarily unavailable. Please try again.',
  503: 'The service is under maintenance. Please check back soon.',
  504: 'The server took too long to respond. Please try again.',
}

const TYPE_MAP = {
  400: 'validation',
  401: 'auth',
  403: 'forbidden',
  404: 'not_found',
  408: 'timeout',
  422: 'validation',
  500: 'server',
  502: 'server',
  503: 'server',
  504: 'server',
}

/**
 * Parse an axios error into a normalised error object.
 * Safe to call with any value — never throws.
 */
export function parseApiError(err) {
  if (!err?.response) {
    if (err?.code === 'ECONNABORTED' || err?.message?.includes('timeout')) {
      return {
        message: 'The request timed out. Please check your connection.',
        errors: {}, status: null, type: 'timeout',
      }
    }
    return {
      message: 'Unable to reach the server. Please check your internet connection.',
      errors: {}, status: null, type: 'network',
    }
  }

  const { status, data } = err.response
  const message = data?.message || STATUS_MESSAGES[status] || `Unexpected error (${status}).`
  const errors  = data?.errors ?? {}
  const type    = TYPE_MAP[status] ?? 'unknown'

  return { message, errors, status, type }
}

/**
 * Composable — reactive wrapper around parseApiError.
 *
 * Usage:
 *   const { apiError, hasError, fieldError, setError, clearError } = useApiError()
 *   try { await api.post(...) } catch (e) { setError(e) }
 */
export function useApiError() {
  const raw = ref(null)

  const apiError = computed(() => raw.value)
  const hasError = computed(() => raw.value !== null)

  /** First message for a specific field, or null */
  function fieldError(field) {
    return raw.value?.errors?.[field]?.[0] ?? null
  }

  /** All messages for a specific field */
  function fieldErrors(field) {
    return raw.value?.errors?.[field] ?? []
  }

  function setError(err) {
    raw.value = parseApiError(err)
  }

  function clearError() {
    raw.value = null
  }

  return { apiError, hasError, fieldError, fieldErrors, setError, clearError }
}
