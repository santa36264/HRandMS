<template>
  <div class="bookings-view">

    <div class="bookings-view__header">
      <div>
        <h1 class="bookings-view__title">Bookings</h1>
        <p class="bookings-view__sub">Manage all hotel reservations</p>
      </div>
    </div>

    <BookingsTable @view="openDetail" />

    <!-- Detail drawer -->
    <teleport to="body">
      <transition name="drawer">
        <div v-if="detailBooking" class="drawer-overlay" @click.self="detailBooking = null">
          <div class="drawer">
            <div class="drawer__header">
              <h3 class="drawer__title">Booking Details</h3>
              <button class="drawer__close" @click="detailBooking = null">✕</button>
            </div>
            <div class="drawer__body">
              <div class="detail-row"><span>Reference</span><strong>{{ detailBooking.booking_reference }}</strong></div>
              <div class="detail-row"><span>Guest</span><strong>{{ detailBooking.user?.name }}</strong></div>
              <div class="detail-row"><span>Email</span><span>{{ detailBooking.user?.email }}</span></div>
              <div class="detail-row"><span>Room</span><strong>{{ detailBooking.room?.name }}</strong></div>
              <div class="detail-row"><span>Type</span><span>{{ detailBooking.room?.type }}</span></div>
              <div class="detail-row"><span>Check-in</span><span>{{ detailBooking.check_in_date }}</span></div>
              <div class="detail-row"><span>Check-out</span><span>{{ detailBooking.check_out_date }}</span></div>
              <div class="detail-row"><span>Nights</span><span>{{ detailBooking.nights }}</span></div>
              <div class="detail-row"><span>Guests</span><span>{{ detailBooking.guests_count }}</span></div>
              <div class="detail-row"><span>Total</span><strong>ETB {{ Number(detailBooking.total_amount).toLocaleString() }}</strong></div>
              <div class="detail-row"><span>Discount</span><span>ETB {{ Number(detailBooking.discount_amount).toLocaleString() }}</span></div>
              <div class="detail-row"><span>Final</span><strong class="detail-row__final">ETB {{ Number(detailBooking.final_amount).toLocaleString() }}</strong></div>
              <div class="detail-row"><span>Status</span><BookingStatusBadge :status="detailBooking.status" /></div>
              <div class="detail-row"><span>Payment</span><span>{{ detailBooking.payment_status }}</span></div>
              <div v-if="detailBooking.special_requests" class="detail-row detail-row--block">
                <span>Special Requests</span>
                <p>{{ detailBooking.special_requests }}</p>
              </div>
              <div v-if="detailBooking.cancellation_reason" class="detail-row detail-row--block">
                <span>Cancellation Reason</span>
                <p>{{ detailBooking.cancellation_reason }}</p>
              </div>
              <div class="detail-row"><span>Created</span><span>{{ detailBooking.created_at }}</span></div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import BookingsTable      from '../../components/admin/BookingsTable.vue'
import BookingStatusBadge from '../../components/admin/BookingStatusBadge.vue'

const detailBooking = ref(null)
function openDetail(booking) { detailBooking.value = booking }
</script>

<style scoped>
.bookings-view { max-width: 1400px; margin: 0 auto; padding: 28px 20px; }

.bookings-view__header {
  display: flex; justify-content: space-between; align-items: flex-start;
  margin-bottom: 20px;
}
.bookings-view__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.bookings-view__sub   { font-size: 13px; color: #6b7280; }

/* Drawer */
.drawer-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 900;
  display: flex; justify-content: flex-end;
}
.drawer {
  width: 100%; max-width: 420px; background: #fff; height: 100%;
  display: flex; flex-direction: column; box-shadow: -8px 0 32px rgba(0,0,0,0.1);
}
.drawer__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 22px; border-bottom: 1px solid #f0f0f0;
}
.drawer__title { font-size: 15px; font-weight: 700; color: #1a202c; }
.drawer__close {
  background: none; border: none; font-size: 16px; color: #9ca3af;
  cursor: pointer; padding: 4px; border-radius: 6px; transition: background 0.15s;
}
.drawer__close:hover { background: #f3f4f6; }
.drawer__body { flex: 1; overflow-y: auto; padding: 20px 22px; display: flex; flex-direction: column; gap: 12px; }

.detail-row {
  display: flex; justify-content: space-between; align-items: center;
  font-size: 13.5px; padding: 8px 0; border-bottom: 1px solid #f9fafb;
}
.detail-row span:first-child { color: #6b7280; font-weight: 600; }
.detail-row strong { color: #1a202c; }
.detail-row__final { color: #4f46e5; font-size: 15px; }
.detail-row--block { flex-direction: column; align-items: flex-start; gap: 4px; }
.detail-row--block p { font-size: 13px; color: #374151; margin: 0; }

.drawer-enter-active, .drawer-leave-active { transition: opacity 0.2s; }
.drawer-enter-active .drawer, .drawer-leave-active .drawer { transition: transform 0.25s ease; }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
.drawer-enter-from .drawer, .drawer-leave-to .drawer { transform: translateX(100%); }
</style>
