<template>
  <div class="payment-flow">

    <!-- ── STEP: Select gateway ─────────────────────────── -->
    <transition name="slide" mode="out-in">

      <div v-if="step === 'select'" key="select" class="payment-flow__panel">
        <div class="payment-flow__header">
          <h2 class="payment-flow__title">Choose Payment Method</h2>
          <p class="payment-flow__sub">All payments are processed securely in ETB</p>
        </div>

        <!-- Booking summary -->
        <div class="booking-summary">
          <div class="booking-summary__row">
            <span>{{ booking.room?.name ?? 'Room' }}</span>
            <span>{{ booking.nights }} night{{ booking.nights !== 1 ? 's' : '' }}</span>
          </div>
          <div class="booking-summary__row booking-summary__row--ref">
            <span class="booking-summary__label">Ref</span>
            <span class="booking-summary__ref">{{ booking.booking_reference }}</span>
          </div>
          <div class="booking-summary__row booking-summary__row--total">
            <span>Total</span>
            <span class="booking-summary__amount">ETB {{ formatAmount(booking.final_amount) }}</span>
          </div>
        </div>

        <!-- Gateway cards -->
        <div class="payment-flow__gateways">
          <GatewayCard
            v-for="gw in gateways"
            :key="gw.name"
            :gateway="gw"
            :selected="selectedGateway?.name === gw.name"
            @select="selectGateway"
          />
        </div>

        <p v-if="error" class="payment-flow__error" role="alert">⚠️ {{ error }}</p>

        <button
          class="payment-flow__btn payment-flow__btn--primary"
          :disabled="!selectedGateway"
          @click="initiatePayment"
        >
          Pay ETB {{ formatAmount(booking.final_amount) }} with {{ selectedGateway?.label ?? '...' }}
        </button>

        <p class="payment-flow__secure">
          🔒 Payments are encrypted and processed by {{ selectedGateway?.label ?? 'your selected gateway' }}
        </p>

        <!-- ── TEST MODE CREDENTIALS ──────────────────────── -->
        <div class="test-creds">
          <div class="test-creds__header" @click="testCredsOpen = !testCredsOpen">
            <span>🧪 Test Mode Credentials</span>
            <span class="test-creds__toggle">{{ testCredsOpen ? '▲' : '▼' }}</span>
          </div>
          <div v-if="testCredsOpen" class="test-creds__body">

            <p class="test-creds__section-title">💳 Test Cards</p>
            <div class="test-creds__table">
              <div class="test-creds__row test-creds__row--head">
                <span>Card Number</span><span>CVV</span><span>Expiry</span>
              </div>
              <div v-for="card in testCards" :key="card.number" class="test-creds__row">
                <span class="test-creds__mono">{{ card.number }}</span>
                <span class="test-creds__mono">{{ card.cvv }}</span>
                <span class="test-creds__mono">{{ card.expiry }}</span>
              </div>
            </div>

            <p class="test-creds__section-title" style="margin-top:14px">📱 Test Mobile (Awash / Amole)</p>
            <div class="test-creds__table">
              <div class="test-creds__row test-creds__row--head">
                <span>Phone</span><span>OTP</span>
              </div>
              <div v-for="mob in testMobile" :key="mob.phone" class="test-creds__row">
                <span class="test-creds__mono">{{ mob.phone }}</span>
                <span class="test-creds__mono">{{ mob.otp }}</span>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── STEP: Processing (initiating) ──────────────── -->
      <div v-else-if="step === 'processing'" key="processing" class="payment-flow__panel payment-flow__panel--center">
        <div class="payment-flow__spinner-wrap">
          <div class="payment-flow__spinner" />
        </div>
        <h3 class="payment-flow__title">Preparing your payment…</h3>
        <p class="payment-flow__sub">Connecting to {{ selectedGateway?.label }}</p>
      </div>

      <!-- ── STEP: Polling ───────────────────────────────── -->
      <div v-else-if="step === 'polling'" key="polling" class="payment-flow__panel">
        <PaymentPolling
          :gateway="selectedGateway"
          :payment-url="paymentUrl"
          :poll-count="pollCount"
          :max-polls="MAX_POLLS"
          @cancel="onCancel"
        />
      </div>

      <!-- ── STEP: Success ───────────────────────────────── -->
      <div v-else-if="step === 'success'" key="success" class="payment-flow__panel payment-flow__panel--center">
        <div class="payment-flow__result payment-flow__result--success">
          <div class="result-icon result-icon--success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="36" height="36">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h2 class="result-title">Payment Successful!</h2>
          <p class="result-sub">Your booking has been confirmed.</p>

          <div class="result-details">
            <div class="result-details__row">
              <span>Booking Ref</span>
              <strong>{{ booking.booking_reference }}</strong>
            </div>
            <div class="result-details__row">
              <span>Amount Paid</span>
              <strong>ETB {{ formatAmount(booking.final_amount) }}</strong>
            </div>
            <div class="result-details__row">
              <span>Gateway</span>
              <strong>{{ selectedGateway?.label }}</strong>
            </div>
            <div v-if="paymentResult?.transaction_id" class="result-details__row">
              <span>Transaction ID</span>
              <strong class="result-details__txid">{{ paymentResult.transaction_id }}</strong>
            </div>
          </div>

          <button class="payment-flow__btn payment-flow__btn--primary" @click="$emit('paid', paymentResult)">
            View Booking
          </button>
        </div>
      </div>

      <!-- ── STEP: Failed ────────────────────────────────── -->
      <div v-else-if="step === 'failed'" key="failed" class="payment-flow__panel payment-flow__panel--center">
        <div class="payment-flow__result payment-flow__result--failed">
          <div class="result-icon result-icon--failed">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="36" height="36">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
          <h2 class="result-title">Payment Failed</h2>
          <p class="result-sub">{{ error || 'Something went wrong with your payment.' }}</p>

          <div class="result-actions">
            <button class="payment-flow__btn payment-flow__btn--primary" @click="reset">Try Again</button>
            <button class="payment-flow__btn payment-flow__btn--ghost"   @click="$emit('cancel')">Cancel</button>
          </div>
        </div>
      </div>

    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import GatewayCard    from './GatewayCard.vue'
import PaymentPolling from './PaymentPolling.vue'
import { usePayment } from '../../composables/usePayment'

const props = defineProps({
  booking: { type: Object, required: true },
})
const emit = defineEmits(['paid', 'cancel'])

const {
  step, selectedGateway, gateways, paymentUrl,
  error, pollCount, paymentResult, MAX_POLLS,
  loadGateways, selectGateway, initiatePayment, stopPolling, reset,
} = usePayment(props.booking.id)

// ── Test credentials (shown in dev/test mode) ──────────────────────
const testCredsOpen = ref(false)
const testCards = [
  { number: '4200 0000 0000 0000', cvv: '123',  expiry: '12/34' },
  { number: '5400 0000 0000 0000', cvv: '123',  expiry: '12/34' },
  { number: '3700 0000 0000 0000', cvv: '1234', expiry: '12/34' },
  { number: '6200 0000 0000 0000', cvv: '123',  expiry: '12/34' },
]
const testMobile = [
  { phone: '0900123456', otp: '12345' },
  { phone: '0900112233', otp: '12345' },
  { phone: '0900881111', otp: '12345' },
]

function onCancel() {
  stopPolling()
  reset()
  emit('cancel')
}

function formatAmount(val) {
  return Number(val).toLocaleString('en-ET', { minimumFractionDigits: 2 })
}

onMounted(async () => {
  await loadGateways()
  // Auto-select Chapa since it's the only gateway
  if (gateways.value.length === 1) {
    selectGateway(gateways.value[0])
  }
})
onUnmounted(stopPolling)
</script>

<style scoped>
.payment-flow {
  background: #fff; border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
  max-width: 480px; width: 100%; overflow: hidden;
}

.payment-flow__panel { padding: 28px; }
.payment-flow__panel--center { text-align: center; padding: 40px 28px; }

.payment-flow__header { margin-bottom: 20px; }
.payment-flow__title  { font-size: 1.2rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.payment-flow__sub    { font-size: 13px; color: #6b7280; }

/* Booking summary */
.booking-summary {
  background: #f7f8ff; border: 1.5px solid #e0e7ff;
  border-radius: 10px; padding: 14px 16px; margin-bottom: 20px;
}
.booking-summary__row {
  display: flex; justify-content: space-between;
  font-size: 13.5px; color: #374151; padding: 4px 0;
}
.booking-summary__row--ref   { color: #9ca3af; font-size: 12px; }
.booking-summary__row--total { font-weight: 700; font-size: 15px; padding-top: 8px; border-top: 1px solid #e0e7ff; margin-top: 4px; }
.booking-summary__label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
.booking-summary__ref   { font-family: monospace; color: #4f46e5; }
.booking-summary__amount { color: #4f46e5; }

/* Gateways */
.payment-flow__gateways { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }

/* Buttons */
.payment-flow__btn {
  width: 100%; padding: 13px; border: none; border-radius: 10px;
  font-size: 15px; font-weight: 700; cursor: pointer;
  transition: background 0.2s, opacity 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.payment-flow__btn--primary { background: #4f46e5; color: #fff; margin-bottom: 10px; }
.payment-flow__btn--primary:hover:not(:disabled) { background: #4338ca; }
.payment-flow__btn--primary:disabled { opacity: 0.45; cursor: not-allowed; }
.payment-flow__btn--ghost   { background: #f3f4f6; color: #374151; }
.payment-flow__btn--ghost:hover { background: #e5e7eb; }

.payment-flow__secure { font-size: 12px; color: #9ca3af; text-align: center; }
.payment-flow__error  { font-size: 13px; color: #ef4444; margin-bottom: 12px; }

/* Processing spinner */
.payment-flow__spinner-wrap { margin: 0 auto 20px; width: 64px; height: 64px; }
.payment-flow__spinner {
  width: 64px; height: 64px; border-radius: 50%;
  border: 4px solid #e0e7ff; border-top-color: #4f46e5;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Result */
.result-icon {
  width: 72px; height: 72px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
}
.result-icon--success { background: #d1fae5; color: #059669; }
.result-icon--failed  { background: #fee2e2; color: #dc2626; }

.result-title { font-size: 1.3rem; font-weight: 800; color: #1a202c; margin-bottom: 6px; }
.result-sub   { font-size: 14px; color: #6b7280; margin-bottom: 20px; }

.result-details {
  background: #f9fafb; border-radius: 10px;
  padding: 14px 16px; margin-bottom: 20px; text-align: left;
}
.result-details__row {
  display: flex; justify-content: space-between;
  font-size: 13.5px; color: #374151; padding: 5px 0;
  border-bottom: 1px solid #f3f4f6;
}
.result-details__row:last-child { border-bottom: none; }
.result-details__txid { font-family: monospace; font-size: 12px; color: #4f46e5; }

.result-actions { display: flex; flex-direction: column; gap: 10px; }

/* Slide transition */
.slide-enter-active, .slide-leave-active { transition: all 0.25s ease; }
.slide-enter-from { opacity: 0; transform: translateX(20px); }
.slide-leave-to   { opacity: 0; transform: translateX(-20px); }

/* ── Test credentials panel ─────────────────────────────── */
.test-creds {
  margin-top: 16px;
  border: 1.5px dashed #fbbf24;
  border-radius: 10px;
  overflow: hidden;
  font-size: 12.5px;
}
.test-creds__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 14px;
  background: #fffbeb;
  cursor: pointer;
  font-weight: 700; color: #92400e;
  user-select: none;
}
.test-creds__header:hover { background: #fef3c7; }
.test-creds__toggle { font-size: 10px; color: #b45309; }
.test-creds__body { padding: 12px 14px; background: #fffdf5; }
.test-creds__section-title {
  font-size: 11.5px; font-weight: 800; color: #78350f;
  text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 8px;
}
.test-creds__table { display: flex; flex-direction: column; gap: 4px; }
.test-creds__row {
  display: grid; grid-template-columns: 2fr 1fr 1fr;
  gap: 8px; padding: 5px 8px;
  background: #fff; border-radius: 6px;
  border: 1px solid #fde68a;
}
.test-creds__row--head {
  background: #fef3c7; font-weight: 700;
  font-size: 10.5px; text-transform: uppercase;
  letter-spacing: 0.3px; color: #92400e;
}
.test-creds__row:not(.test-creds__row--head) .test-creds__mono {
  font-family: monospace; font-size: 12px; color: #1a202c;
}
/* Mobile table: 2 cols */
.test-creds__body .test-creds__table:last-child .test-creds__row {
  grid-template-columns: 1fr 1fr;
}
</style>
