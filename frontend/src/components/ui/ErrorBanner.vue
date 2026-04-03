<template>
  <transition name="banner">
    <div v-if="visible" class="error-banner" :class="`error-banner--${type}`" role="alert">
      <!-- Icon + summary -->
      <div class="error-banner__head">
        <span class="error-banner__icon">{{ ICONS[type] }}</span>
        <span class="error-banner__message">{{ message }}</span>
        <button
          v-if="dismissible"
          class="error-banner__close"
          @click="$emit('dismiss')"
          aria-label="Dismiss"
        >✕</button>
      </div>

      <!-- Field-level errors list (422) -->
      <ul v-if="hasFieldErrors" class="error-banner__list">
        <li v-for="(msgs, field) in errors" :key="field">
          <span class="error-banner__field">{{ formatField(field) }}:</span>
          {{ msgs[0] }}
        </li>
      </ul>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  /** Parsed error object from useApiError, or a plain string */
  error:       { type: [Object, String], default: null },
  /** Override the type (auto-detected from error.type if not set) */
  type:        { type: String, default: null },
  dismissible: { type: Boolean, default: true },
})

defineEmits(['dismiss'])

const ICONS = {
  validation: '⚠️',
  auth:       '🔒',
  forbidden:  '🚫',
  not_found:  '🔍',
  server:     '🔧',
  network:    '📡',
  timeout:    '⏱️',
  warning:    '⚠️',
  error:      '✕',
  unknown:    '⚠️',
}

const TYPE_STYLES = {
  validation: 'warning',
  auth:       'error',
  forbidden:  'error',
  not_found:  'warning',
  server:     'error',
  network:    'error',
  timeout:    'warning',
  unknown:    'error',
}

const visible = computed(() => !!props.error)

const message = computed(() => {
  if (!props.error) return ''
  if (typeof props.error === 'string') return props.error
  return props.error.message ?? 'An error occurred.'
})

const errors = computed(() => {
  if (!props.error || typeof props.error === 'string') return {}
  return props.error.errors ?? {}
})

const hasFieldErrors = computed(() =>
  Object.keys(errors.value).length > 0
)

const type = computed(() => {
  if (props.type) return props.type
  if (typeof props.error === 'string') return 'error'
  const t = props.error?.type ?? 'unknown'
  return TYPE_STYLES[t] ?? 'error'
})

function formatField(field) {
  return field
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<style scoped>
.error-banner {
  border-radius: 10px;
  padding: 13px 16px;
  margin-bottom: 16px;
  font-size: 14px;
  line-height: 1.5;
}

.error-banner--error   { background: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b; }
.error-banner--warning { background: #fffbeb; border: 1.5px solid #fcd34d; color: #92400e; }
.error-banner--info    { background: #eff6ff; border: 1.5px solid #93c5fd; color: #1e40af; }

.error-banner__head {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}
.error-banner__icon    { flex-shrink: 0; font-size: 15px; }
.error-banner__message { flex: 1; font-weight: 600; }
.error-banner__close {
  background: none; border: none; cursor: pointer;
  font-size: 13px; opacity: 0.5; padding: 0; color: inherit;
}
.error-banner__close:hover { opacity: 1; }

.error-banner__list {
  margin: 10px 0 0 24px;
  padding: 0;
  list-style: disc;
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
}
.error-banner__field { font-weight: 700; }

/* Transition */
.banner-enter-active { transition: all 0.2s ease; }
.banner-leave-active { transition: all 0.15s ease; }
.banner-enter-from, .banner-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
