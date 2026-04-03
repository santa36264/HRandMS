<template>
  <div class="confirm-page">

    <!-- Loading skeleton -->
    <div v-if="loading" class="confirm-page__skeleton">
      <div class="skel-panel shimmer" />
      <div class="skel-side shimmer" />
    </div>

    <!-- Error -->
    <div v-else-if="loadError" class="confirm-page__error">
      <span>⚠️</span>
      <p>{{ loadError }}</p>
      <button @click="router.back()">← Go Back</button>
    </div>

    <!-- Form -->
    <BookingForm
      v-else-if="room"
      :room="room"
      :initial-check-in="checkIn"
      :initial-check-out="checkOut"
      @booked="onBooked"
    />

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BookingForm from '../../components/rooms/BookingForm.vue'
import { roomService } from '../../services/rooms'

const route  = useRoute()
const router = useRouter()

const room      = ref(null)
const loading   = ref(true)
const loadError = ref('')

const checkIn  = route.query.check_in  ?? ''
const checkOut = route.query.check_out ?? ''

onMounted(async () => {
  try {
    const { data } = await roomService.show(route.params.id)
    room.value = data.data
  } catch {
    loadError.value = 'Could not load room details. Please go back and try again.'
  } finally {
    loading.value = false
  }
})

function onBooked(booking) {
  router.push({
    name: 'pay',
    params: { id: booking.room?.id ?? route.params.id },
    query: { booking_id: booking.id, ref: booking.booking_reference },
  })
}
</script>

<style scoped>
.confirm-page {
  display: flex; justify-content: center; align-items: flex-start;
  padding: 40px 16px; min-height: 100vh;
  background: var(--bg-soft);
}

/* Skeleton */
.confirm-page__skeleton {
  display: grid; grid-template-columns: 1fr 340px; gap: 24px;
  width: 100%; max-width: 900px;
}
.skel-panel { height: 560px; border-radius: 16px; }
.skel-side  { height: 360px; border-radius: 16px; }
.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* Error */
.confirm-page__error {
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  padding: 60px 20px; text-align: center; color: #6b7280;
}
.confirm-page__error span { font-size: 2.5rem; }
.confirm-page__error p    { font-size: 15px; color: #374151; }
.confirm-page__error button {
  padding: 10px 20px; background: #4f46e5; color: #fff;
  border: none; border-radius: 8px; font-size: 14px; font-weight: 600;
  cursor: pointer;
}

@media (max-width: 720px) {
  .confirm-page__skeleton { grid-template-columns: 1fr; }
  .skel-side { display: none; }
}
</style>
