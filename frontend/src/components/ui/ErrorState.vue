<template>
  <div class="error-state" :class="`error-state--${config.style}`">
    <div class="error-state__icon">{{ config.icon }}</div>
    <h2 class="error-state__title">{{ config.title }}</h2>
    <p class="error-state__desc">{{ config.description }}</p>

    <div class="error-state__actions">
      <button
        v-if="config.showRetry"
        class="error-state__btn error-state__btn--primary"
        :disabled="retrying"
        @click="handleRetry"
      >
        <span v-if="retrying" class="error-state__spinner" />
        {{ retrying ? 'Retrying…' : config.retryLabel }}
      </button>

      <router-link
        v-if="config.showHome"
        to="/"
        class="error-state__btn error-state__btn--ghost"
      >Go to Home</router-link>

      <button
        v-if="config.showBack"
        class="error-state__btn error-state__btn--ghost"
        @click="$router.back()"
      >Go Back</button>
    </div>

    <!-- Debug detail (dev only) -->
    <details v-if="isDev && detail" class="error-state__debug">
      <summary>Technical details</summary>
      <pre>{{ detail }}</pre>
    </details>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  /** Parsed error object from useApiError, a status code, or a type string */
  error:   { type: [Object, Number, String], default: null },
  /** Override any config key */
  title:   { type: String, default: null },
  message: { type: String, default: null },
  /** Called when the retry button is clicked */
  onRetry: { type: Function, default: null },
})

const retrying = ref(false)
const isDev    = import.meta.env.DEV

const TYPE_CONFIG = {
  network: {
    icon: '📡', style: 'warning',
    title: 'No Internet Connection',
    description: 'Please check your connection and try again.',
    showRetry: true, retryLabel: 'Try Again', showHome: false, showBack: true,
  },
  timeout: {
    icon: '⏱️', style: 'warning',
    title: 'Request Timed Out',
    description: 'The server took too long to respond. Please try again.',
    showRetry: true, retryLabel: 'Retry', showHome: false, showBack: true,
  },
  auth: {
    icon: '🔒', style: 'info',
    title: 'Session Expired',
    description: 'Your session has expired. Please log in to continue.',
    showRetry: false, retryLabel: '', showHome: false, showBack: false,
  },
  forbidden: {
    icon: '🚫', style: 'error',
    title: 'Access Denied',
    description: 'You do not have permission to view this page.',
    showRetry: false, retryLabel: '', showHome: true, showBack: true,
  },
  not_found: {
    icon: '🔍', style: 'neutral',
    title: 'Not Found',
    description: 'The resource you are looking for does not exist or has been removed.',
    showRetry: false, retryLabel: '', showHome: true, showBack: true,
  },
  server: {
    icon: '🔧', style: 'error',
    title: 'Server Error',
    description: 'Something went wrong on our end. Our team has been notified.',
    showRetry: true, retryLabel: 'Try Again', showHome: true, showBack: false,
  },
  validation: {
    icon: '⚠️', style: 'warning',
    title: 'Invalid Request',
    description: 'Please check your input and try again.',
    showRetry: false, retryLabel: '', showHome: false, showBack: true,
  },
  unknown: {
    icon: '⚠️', style: 'error',
    title: 'Something Went Wrong',
    description: 'An unexpected error occurred. Please try again.',
    showRetry: true, retryLabel: 'Try Again', showHome: true, showBack: true,
  },
}

const config = computed(() => {
  let type = 'unknown'

  if (typeof props.error === 'number') {
    if (props.error === 401) type = 'auth'
    else if (props.error === 403) type = 'forbidden'
    else if (props.error === 404) type = 'not_found'
    else if (props.error >= 500)  type = 'server'
  } else if (typeof props.error === 'string') {
    type = props.error
  } else if (props.error?.type) {
    type = props.error.type
  }

  const base = TYPE_CONFIG[type] ?? TYPE_CONFIG.unknown

  return {
    ...base,
    title:       props.title   || (props.error?.message && type !== 'unknown' ? base.title : base.title),
    description: props.message || props.error?.message || base.description,
  }
})

const detail = computed(() => {
  if (!props.error || typeof props.error !== 'object') return null
  return JSON.stringify(props.error, null, 2)
})

async function handleRetry() {
  if (!props.onRetry) return
  retrying.value = true
  try { await props.onRetry() } finally { retrying.value = false }
}
</script>

<style scoped>
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 24px;
  min-height: 320px;
  border-radius: 16px;
}

.error-state--error   { background: #fef2f2; }
.error-state--warning { background: #fffbeb; }
.error-state--info    { background: #eff6ff; }
.error-state--neutral { background: #f8f9fc; }

.error-state__icon  { font-size: 3.5rem; margin-bottom: 16px; line-height: 1; }
.error-state__title {
  font-size: 20px; font-weight: 800; color: #1a202c;
  margin: 0 0 10px;
}
.error-state__desc  {
  font-size: 14.5px; color: #6b7280; line-height: 1.7;
  max-width: 380px; margin: 0 0 28px;
}

.error-state__actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }

.error-state__btn {
  padding: 10px 24px; border-radius: 9px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  border: none; text-decoration: none; display: inline-flex;
  align-items: center; gap: 8px; transition: background 0.15s;
}
.error-state__btn--primary {
  background: #4f46e5; color: #fff;
}
.error-state__btn--primary:hover:not(:disabled) { background: #4338ca; }
.error-state__btn--primary:disabled { opacity: 0.6; cursor: not-allowed; }
.error-state__btn--ghost {
  background: #f3f4f6; color: #374151;
}
.error-state__btn--ghost:hover { background: #e5e7eb; }

.error-state__spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.error-state__debug {
  margin-top: 24px; text-align: left; width: 100%; max-width: 500px;
}
.error-state__debug summary {
  font-size: 12px; color: #9ca3af; cursor: pointer; margin-bottom: 8px;
}
.error-state__debug pre {
  font-size: 11px; background: #f3f4f6; padding: 12px;
  border-radius: 8px; overflow: auto; color: #374151;
}
</style>
