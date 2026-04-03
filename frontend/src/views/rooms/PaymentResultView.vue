<template>
  <div class="result-page">
    <div v-if="checking" class="result-card result-card--loading">
      <div class="result-spinner"></div>
      <p>Verifying your payment…</p>
      <p class="result-hint">This may take a few seconds.</p>
    </div>

    <div v-else class="result-card" :class="isSuccess ? 'result-card--success' : 'result-card--failed'">

      <div class="result-icon" :class="isSuccess ? 'result-icon--success' : 'result-icon--failed'">
        <svg v-if="isSuccess" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="40" height="40">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="40" height="40">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>

      <h1 class="result-title">{{ isSuccess ? 'Payment Successful!' : 'Payment Failed' }}</h1>
      <p class="result-sub">
        {{ isSuccess
          ? 'Your booking is confirmed. A confirmation email has been sent to you.'
          : 'Something went wrong with your payment. Please try again.' }}
      </p>

      <div v-if="isSuccess && bookingRef" class="result-ref">
        <span class="result-ref__label">Booking Reference</span>
        <span class="result-ref__val">{{ bookingRef }}</span>
      </div>

      <div class="result-actions">
        <router-link v-if="isSuccess" to="/profile" class="btn-primary">View My Bookings</router-link>
        <router-link v-else to="/rooms" class="btn-primary">Browse Rooms</router-link>
        <router-link to="/" class="btn-ghost">Back to Home</router-link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../plugins/axios'

const route      = useRoute()
const checking   = ref(true)
const isSuccess  = ref(false)
const bookingRef = ref(route.query.ref ?? '')

const sleep = (ms) => new Promise(r => setTimeout(r, ms))

onMounted(async () => {
  const ref = route.query.ref ?? ''

  if (!ref) {
    isSuccess.value = false
    checking.value  = false
    return
  }

  // Retry up to 8 times (16s total).
  // Each iteration: call verify-by-reference (which hits Chapa API and updates DB),
  // then immediately check if booking is now paid.
  const MAX_TRIES = 8
  for (let i = 0; i < MAX_TRIES; i++) {
    try {
      const { data } = await api.post('/guest/payments/verify-by-reference',
        { booking_reference: ref },
        { _silent401: true }
      )
      const result = data.data

      if (result?.status === 'completed' || result?.booking?.payment_status === 'paid') {
        isSuccess.value  = true
        bookingRef.value = ref
        checking.value   = false
        return
      }
    } catch (e) {
      if (e.response?.status === 401) break
    }

    if (i < MAX_TRIES - 1) await sleep(2000)
  }

  isSuccess.value = false
  checking.value  = false
})
</script>

<style scoped>
.result-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f0f4ff 0%, #f8f9fc 100%);
  display: flex; align-items: center; justify-content: center;
  padding: 40px 16px;
}
.result-card {
  background: #fff; border-radius: 20px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.1);
  padding: 48px 40px; max-width: 460px; width: 100%;
  text-align: center;
}
.result-card--success { border-top: 4px solid #10b981; }
.result-card--failed  { border-top: 4px solid #ef4444; }
.result-card--loading {
  border-top: 4px solid #c9a84c;
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  color: #6b7280;
}
.result-spinner {
  width: 48px; height: 48px; border-radius: 50%;
  border: 4px solid #e0e7ff; border-top-color: #4f46e5;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.result-hint { font-size: 12px; color: #9ca3af; }

.result-icon {
  width: 80px; height: 80px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 24px;
}
.result-icon--success { background: #d1fae5; color: #059669; }
.result-icon--failed  { background: #fee2e2; color: #dc2626; }

.result-title { font-size: 1.6rem; font-weight: 900; color: #1a202c; margin-bottom: 10px; }
.result-sub   { font-size: 14.5px; color: #6b7280; line-height: 1.7; margin-bottom: 28px; }

.result-ref {
  background: #f9fafb; border-radius: 10px;
  padding: 14px 20px; margin-bottom: 28px;
  display: flex; flex-direction: column; gap: 4px;
}
.result-ref__label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; }
.result-ref__val   { font-size: 18px; font-weight: 900; color: #4f46e5; font-family: monospace; }

.result-actions { display: flex; flex-direction: column; gap: 10px; }
.btn-primary {
  display: block; padding: 13px; background: #4f46e5; color: #fff;
  border-radius: 10px; font-size: 15px; font-weight: 700;
  text-decoration: none; transition: background 0.2s;
}
.btn-primary:hover { background: #4338ca; }
.btn-ghost {
  display: block; padding: 13px; background: #f3f4f6; color: #374151;
  border-radius: 10px; font-size: 15px; font-weight: 600;
  text-decoration: none; transition: background 0.2s;
}
.btn-ghost:hover { background: #e5e7eb; }
</style>
