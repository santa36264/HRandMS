<template>
  <div class="payment-view">

    <div v-if="loadError" class="payment-view__error">
      <span>⚠️</span>
      <p>{{ loadError }}</p>
      <button @click="router.back()">← Go Back</button>
    </div>

    <div v-else-if="!booking" class="payment-view__loading">
      <div class="payment-view__spinner"></div>
      <p>Loading booking details…</p>
    </div>

    <PaymentFlow
      v-else
      :booking="booking"
      @paid="onPaid"
      @cancel="onCancel"
    />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaymentFlow from '../../components/payment/PaymentFlow.vue'
import { bookingsService } from '../../services/bookings'

const route   = useRoute()
const router  = useRouter()
const booking = ref(null)
const loadError = ref('')

onMounted(async () => {
  const bookingId = route.query.booking_id
  if (!bookingId) {
    loadError.value = 'No booking ID provided.'
    return
  }
  try {
    const { data } = await bookingsService.show(bookingId)
    booking.value  = data.data
  } catch {
    loadError.value = 'Could not load booking details. Please go back and try again.'
  }
})

function onPaid(result) {
  router.push({
    name: 'payment-result',
    query: {
      ref:    booking.value?.booking_reference,
      status: 'success',
    },
  })
}

function onCancel() {
  router.back()
}
</script>

<style scoped>
.payment-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #f0f4ff 0%, #f8f9fc 100%);
  display: flex; align-items: center; justify-content: center;
  padding: 40px 16px;
}

.payment-view__loading {
  display: flex; flex-direction: column; align-items: center; gap: 14px;
  color: #6b7280; font-size: 15px;
}
.payment-view__spinner {
  width: 40px; height: 40px; border-radius: 50%;
  border: 3px solid #e0e7ff; border-top-color: #4f46e5;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.payment-view__error {
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  padding: 60px 20px; text-align: center;
}
.payment-view__error span { font-size: 2.5rem; }
.payment-view__error p    { font-size: 15px; color: #374151; }
.payment-view__error button {
  padding: 10px 20px; background: #4f46e5; color: #fff;
  border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
}
</style>
