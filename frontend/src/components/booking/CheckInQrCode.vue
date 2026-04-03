<template>
  <div class="qr-card">
    <!-- Header -->
    <div class="qr-card__header">
      <div class="qr-card__hotel">SATAAB Hotel</div>
      <div class="qr-card__title">Check-In Pass</div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="qr-card__loading">
      <div class="qr-card__spinner" />
      <span>Generating QR code…</span>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="qr-card__error">
      <span>⚠️ {{ error }}</span>
      <button class="qr-card__retry" @click="fetchToken">Retry</button>
    </div>

    <!-- QR content -->
    <template v-else-if="qrValue">
      <div class="qr-card__qr-wrap">
        <qrcode-vue
          :value="qrValue"
          :size="200"
          level="H"
          render-as="svg"
          class="qr-card__qr"
        />
        <div class="qr-card__scan-hint">Scan at front desk</div>
      </div>

      <!-- Booking details -->
      <div class="qr-card__details">
        <div class="qr-card__ref">{{ booking.booking_reference }}</div>
        <div class="qr-card__room-name">{{ booking.room?.name }}</div>

        <div class="qr-card__dates">
          <div class="qr-card__date-block">
            <span class="qr-card__date-label">Check-in</span>
            <span class="qr-card__date-val">{{ formatDate(booking.check_in_date) }}</span>
          </div>
          <div class="qr-card__date-sep">→</div>
          <div class="qr-card__date-block">
            <span class="qr-card__date-label">Check-out</span>
            <span class="qr-card__date-val">{{ formatDate(booking.check_out_date) }}</span>
          </div>
        </div>

        <div class="qr-card__meta">
          <span>👥 {{ booking.guests_count }} guest{{ booking.guests_count > 1 ? 's' : '' }}</span>
          <span>🌙 {{ booking.nights }} night{{ booking.nights > 1 ? 's' : '' }}</span>
        </div>
      </div>

      <!-- Expiry notice -->
      <div class="qr-card__expiry" v-if="expiresIn">
        🔒 QR expires in {{ expiresIn }}
      </div>

      <!-- Actions -->
      <div class="qr-card__actions">
        <button class="qr-card__btn qr-card__btn--download" @click="downloadQr">
          ⬇ Download
        </button>
        <button class="qr-card__btn qr-card__btn--close" @click="$emit('close')">
          Close
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import QrcodeVue from 'qrcode.vue'
import { bookingsService } from '../../services/bookings'

const props = defineProps({
  booking: { type: Object, required: true },
})
const emit = defineEmits(['close'])

const loading  = ref(false)
const error    = ref('')
const qrValue  = ref('')
const tokenExp = ref(null)   // Unix timestamp when token expires
let   timer    = null

// ── Countdown ─────────────────────────────────────────────────────
const expiresIn = computed(() => {
  if (!tokenExp.value) return ''
  const diff = Math.max(0, tokenExp.value - Math.floor(Date.now() / 1000))
  if (diff === 0) return 'expired'
  const m = Math.floor(diff / 60)
  const s = diff % 60
  return m > 0 ? `${m}m ${s}s` : `${s}s`
})

async function fetchToken() {
  loading.value = true
  error.value   = ''
  qrValue.value = ''
  try {
    const { data } = await bookingsService.checkinToken(props.booking.id)
    qrValue.value  = data.data.qr_payload
    tokenExp.value = data.data.expires_at
    // Refresh 30s before expiry
    const ttl = (tokenExp.value - Math.floor(Date.now() / 1000) - 30) * 1000
    if (ttl > 0) timer = setTimeout(fetchToken, ttl)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to generate QR code.'
  } finally {
    loading.value = false
  }
}

function downloadQr() {
  const svg = document.querySelector('.qr-card__qr svg')
  if (!svg) return
  const blob = new Blob([svg.outerHTML], { type: 'image/svg+xml' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `checkin-${props.booking.booking_reference}.svg`
  a.click()
  URL.revokeObjectURL(url)
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(fetchToken)
onUnmounted(() => { if (timer) clearTimeout(timer) })
</script>

<style scoped>
.qr-card {
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  width: 320px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.18);
  display: flex;
  flex-direction: column;
}

/* Header */
.qr-card__header {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2b55 100%);
  padding: 18px 20px 14px;
  text-align: center;
}
.qr-card__hotel {
  font-size: 11px;
  font-weight: 700;
  color: #a5b4fc;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 4px;
}
.qr-card__title {
  font-size: 18px;
  font-weight: 900;
  color: #fff;
}

/* Loading */
.qr-card__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 48px 20px;
  color: #6b7280;
  font-size: 13px;
}
.qr-card__spinner {
  width: 32px; height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Error */
.qr-card__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 40px 20px;
  color: #dc2626;
  font-size: 13px;
  text-align: center;
}
.qr-card__retry {
  padding: 7px 18px;
  background: #4f46e5;
  color: #fff;
  border: none;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

/* QR */
.qr-card__qr-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 20px 12px;
  gap: 8px;
}
.qr-card__qr {
  border: 6px solid #f3f4f6;
  border-radius: 12px;
  padding: 8px;
  background: #fff;
}
.qr-card__scan-hint {
  font-size: 11.5px;
  color: #9ca3af;
  font-weight: 600;
}

/* Details */
.qr-card__details {
  padding: 0 20px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  text-align: center;
}
.qr-card__ref {
  font-size: 11px;
  font-family: monospace;
  color: #9ca3af;
  letter-spacing: 0.5px;
}
.qr-card__room-name {
  font-size: 15px;
  font-weight: 800;
  color: #1a202c;
}
.qr-card__dates {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 4px;
}
.qr-card__date-block {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.qr-card__date-label {
  font-size: 9.5px;
  color: #9ca3af;
  font-weight: 700;
  text-transform: uppercase;
}
.qr-card__date-val {
  font-size: 12.5px;
  font-weight: 700;
  color: #1a202c;
}
.qr-card__date-sep { color: #d1d5db; font-size: 14px; }
.qr-card__meta {
  display: flex;
  gap: 14px;
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
}

/* Expiry */
.qr-card__expiry {
  margin: 0 20px 12px;
  background: #fef3c7;
  color: #92400e;
  font-size: 11.5px;
  font-weight: 700;
  text-align: center;
  padding: 6px 12px;
  border-radius: 8px;
}

/* Actions */
.qr-card__actions {
  display: flex;
  gap: 10px;
  padding: 12px 20px 20px;
}
.qr-card__btn {
  flex: 1;
  padding: 10px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: background 0.15s;
}
.qr-card__btn--download {
  background: #4f46e5;
  color: #fff;
}
.qr-card__btn--download:hover { background: #4338ca; }
.qr-card__btn--close {
  background: #f3f4f6;
  color: #374151;
}
.qr-card__btn--close:hover { background: #e5e7eb; }
</style>
