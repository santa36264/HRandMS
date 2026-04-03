<template>
  <div class="profile">

    <!-- ── Header banner ──────────────────────────────────────────── -->
    <div class="profile__banner">
      <div class="profile__banner-inner">
        <div class="profile__avatar-wrap">
          <div class="profile__avatar">{{ initials }}</div>
          <span
            class="profile__verified-dot"
            :title="auth.user?.email_verified_at ? 'Verified' : 'Not verified'"
          >{{ auth.user?.email_verified_at ? '✓' : '!' }}</span>
        </div>
        <div class="profile__banner-info">
          <h1 class="profile__name">{{ auth.user?.name }}</h1>
          <p class="profile__email">{{ auth.user?.email }}</p>
          <div class="profile__meta">
            <span v-if="auth.user?.phone">📞 {{ auth.user.phone }}</span>
            <span v-if="auth.user?.nationality">🌍 {{ auth.user.nationality }}</span>
            <span>📅 Member since {{ memberSince }}</span>
          </div>
        </div>
        <div class="profile__stats">
          <div class="profile__stat">
            <span class="profile__stat-value">{{ stats.total }}</span>
            <span class="profile__stat-label">Total Bookings</span>
          </div>
          <div class="profile__stat">
            <span class="profile__stat-value">{{ stats.upcoming }}</span>
            <span class="profile__stat-label">Upcoming</span>
          </div>
          <div class="profile__stat">
            <span class="profile__stat-value">ETB {{ formatPrice(stats.spent) }}</span>
            <span class="profile__stat-label">Total Spent</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Tabs ───────────────────────────────────────────────────── -->
    <div class="profile__tabs-bar">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="profile__tab"
        :class="{ 'profile__tab--active': activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        {{ tab.icon }} {{ tab.label }}
        <span v-if="tab.key === 'bookings' && stats.upcoming" class="profile__tab-badge">
          {{ stats.upcoming }}
        </span>
      </button>
    </div>

    <div class="profile__body">

      <!-- ── Tab: Bookings ──────────────────────────────────────── -->
      <div v-if="activeTab === 'bookings'" class="profile__tab-content">

        <!-- Filter pills -->
        <div class="profile__filter-pills">
          <button
            v-for="f in bookingFilters"
            :key="f.value"
            class="profile__pill"
            :class="{ 'profile__pill--active': bookingFilter === f.value }"
            @click="bookingFilter = f.value"
          >{{ f.label }}</button>
        </div>

        <!-- Skeleton -->
        <div v-if="bookingsLoading" class="profile__booking-list">
          <div v-for="i in 3" :key="i" class="booking-card booking-card--skeleton">
            <div class="skel shimmer" style="width:80px;height:80px;border-radius:10px;flex-shrink:0" />
            <div style="flex:1;display:flex;flex-direction:column;gap:8px">
              <div class="skel shimmer" style="height:14px;width:40%" />
              <div class="skel shimmer" style="height:18px;width:65%" />
              <div class="skel shimmer" style="height:12px;width:55%" />
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else-if="!filteredBookings.length" class="profile__empty">
          <span class="profile__empty-icon">📋</span>
          <p>No {{ bookingFilter !== 'all' ? bookingFilter : '' }} bookings found</p>
          <router-link to="/rooms" class="profile__empty-cta">Browse Rooms</router-link>
        </div>

        <!-- Booking cards -->
        <div v-else class="profile__booking-list">
          <div
            v-for="b in filteredBookings"
            :key="b.id"
            class="booking-card"
            :class="`booking-card--${b.status}`"
          >
            <!-- Room image -->
            <div class="booking-card__img-wrap">
              <img
                :src="b.room?.images?.[0] ?? FALLBACK"
                :alt="b.room?.name"
                class="booking-card__img"
                @error="e => e.target.src = FALLBACK"
              />
              <span class="booking-card__type" :class="`booking-card__type--${b.room?.type}`">
                {{ b.room?.type }}
              </span>
            </div>

            <!-- Details -->
            <div class="booking-card__body">
              <div class="booking-card__top">
                <div>
                  <p class="booking-card__ref">{{ b.booking_reference }}</p>
                  <h3 class="booking-card__room">{{ b.room?.name ?? 'Room' }}</h3>
                </div>
                <BookingStatusBadge :status="b.status" />
              </div>

              <div class="booking-card__dates">
                <div class="booking-card__date-block">
                  <span class="booking-card__date-label">Check-in</span>
                  <span class="booking-card__date-val">{{ formatDate(b.check_in_date) }}</span>
                </div>
                <div class="booking-card__date-arrow">→</div>
                <div class="booking-card__date-block">
                  <span class="booking-card__date-label">Check-out</span>
                  <span class="booking-card__date-val">{{ formatDate(b.check_out_date) }}</span>
                </div>
                <div class="booking-card__nights">
                  🌙 {{ b.nights }} night{{ b.nights > 1 ? 's' : '' }}
                </div>
              </div>

              <div class="booking-card__footer">
                <div class="booking-card__price-info">
                  <span class="booking-card__guests">👥 {{ b.guests_count }} guest{{ b.guests_count > 1 ? 's' : '' }}</span>
                  <span class="booking-card__amount">ETB {{ formatPrice(b.final_amount) }}</span>
                  <span
                    class="booking-card__payment"
                    :class="`booking-card__payment--${b.payment_status}`"
                  >{{ b.payment_status }}</span>
                </div>

                <div class="booking-card__actions">
                  <button
                    v-if="isCancellable(b)"
                    class="booking-card__btn booking-card__btn--cancel"
                    @click="openCancelModal(b)"
                  >Cancel</button>
                  <router-link
                    v-if="b.payment_status === 'unpaid' && b.status === 'pending'"
                    :to="{ name: 'pay', params: { id: b.room?.id }, query: { booking_id: b.id } }"
                    class="booking-card__btn booking-card__btn--pay"
                  >Pay Now</router-link>
                  <button
                    v-if="['confirmed', 'checked_in'].includes(b.status)"
                    class="booking-card__btn booking-card__btn--qr"
                    @click="openQrModal(b)"
                  >📱 QR Pass</button>
                  <button
                    v-if="isReviewable(b) && !b.review"
                    class="booking-card__btn booking-card__btn--review"
                    @click="openReviewModal(b)"
                  >⭐ Review</button>
                  <span
                    v-if="isReviewable(b) && b.review"
                    class="booking-card__reviewed"
                  >✓ Reviewed</span>
                </div>
              </div>

              <!-- Cancellation reason -->
              <div v-if="b.cancellation_reason" class="booking-card__cancel-reason">
                ℹ️ {{ b.cancellation_reason }}
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="profile__pagination">
          <button :disabled="pagination.current_page === 1" @click="loadBookings(pagination.current_page - 1)" class="profile__page-btn">‹</button>
          <span class="profile__page-info">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button :disabled="pagination.current_page === pagination.last_page" @click="loadBookings(pagination.current_page + 1)" class="profile__page-btn">›</button>
        </div>
      </div>

      <!-- ── Tab: Edit Profile ───────────────────────────────────── -->
      <div v-else-if="activeTab === 'edit'" class="profile__tab-content profile__tab-content--narrow">
        <h2 class="profile__section-title">Personal Information</h2>

        <form class="profile__form" @submit.prevent="saveProfile">
          <div class="pf__field">
            <label class="pf__label" for="pf-name">Full Name</label>
            <input id="pf-name" v-model="profileForm.name" type="text" class="pf__input" :class="{ 'pf__input--error': profileErrors.name }" />
            <span v-if="profileErrors.name" class="pf__error">{{ profileErrors.name }}</span>
          </div>

          <div class="pf__field">
            <label class="pf__label" for="pf-email">Email Address</label>
            <input id="pf-email" :value="auth.user?.email" type="email" class="pf__input pf__input--readonly" readonly />
            <span class="pf__hint">Email cannot be changed here</span>
          </div>

          <div class="pf__field">
            <label class="pf__label" for="pf-phone">Phone Number</label>
            <input id="pf-phone" v-model="profileForm.phone" type="tel" class="pf__input" placeholder="+251 9XX XXX XXX" />
          </div>

          <div class="pf__field">
            <label class="pf__label" for="pf-nationality">Nationality</label>
            <input id="pf-nationality" v-model="profileForm.nationality" type="text" class="pf__input" placeholder="e.g. Ethiopian" />
          </div>

          <div class="pf__field">
            <label class="pf__label" for="pf-address">Address</label>
            <input id="pf-address" v-model="profileForm.address" type="text" class="pf__input" placeholder="City, Country" />
          </div>

          <div v-if="profileSuccess" class="pf__success">✓ {{ profileSuccess }}</div>
          <div v-if="profileError"   class="pf__error-banner">⚠️ {{ profileError }}</div>

          <button type="submit" class="pf__submit" :disabled="profileSaving">
            <span v-if="profileSaving" class="pf__spinner" />
            {{ profileSaving ? 'Saving…' : 'Save Changes' }}
          </button>
        </form>

        <div class="profile__divider" />

        <h2 class="profile__section-title">Change Password</h2>

        <form class="profile__form" @submit.prevent="savePassword">
          <div class="pf__field">
            <label class="pf__label" for="pf-cur-pw">Current Password</label>
            <input id="pf-cur-pw" v-model="pwForm.current_password" type="password" class="pf__input" :class="{ 'pf__input--error': pwErrors.current_password }" />
            <span v-if="pwErrors.current_password" class="pf__error">{{ pwErrors.current_password }}</span>
          </div>
          <div class="pf__field">
            <label class="pf__label" for="pf-new-pw">New Password</label>
            <input id="pf-new-pw" v-model="pwForm.password" type="password" class="pf__input" :class="{ 'pf__input--error': pwErrors.password }" placeholder="Min 8 characters" />
            <span v-if="pwErrors.password" class="pf__error">{{ pwErrors.password }}</span>
          </div>
          <div class="pf__field">
            <label class="pf__label" for="pf-conf-pw">Confirm New Password</label>
            <input id="pf-conf-pw" v-model="pwForm.password_confirmation" type="password" class="pf__input" placeholder="Repeat new password" />
          </div>

          <div v-if="pwSuccess" class="pf__success">✓ {{ pwSuccess }}</div>
          <div v-if="pwError"   class="pf__error-banner">⚠️ {{ pwError }}</div>

          <button type="submit" class="pf__submit pf__submit--danger" :disabled="pwSaving">
            <span v-if="pwSaving" class="pf__spinner" />
            {{ pwSaving ? 'Updating…' : 'Update Password' }}
          </button>
        </form>
      </div>

    </div>

    <!-- ── Review modal ────────────────────────────────────────────── -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="reviewModal.open" class="modal-overlay" @click.self="reviewModal.open = false">
          <div class="review-modal-wrap">
            <ReviewForm
              :booking="reviewModal.booking"
              :existing="reviewModal.existing"
              @submitted="onReviewSubmitted"
              @cancel="reviewModal.open = false"
              @close="reviewModal.open = false"
            />
          </div>
        </div>
      </transition>
    </teleport>

    <!-- ── Cancel modal ───────────────────────────────────────────── -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="cancelModal.open" class="modal-overlay" @click.self="cancelModal.open = false">
          <div class="cancel-modal">
            <div class="cancel-modal__header">
              <h3>Cancel Booking</h3>
              <button @click="cancelModal.open = false">✕</button>
            </div>
            <div class="cancel-modal__body">
              <p class="cancel-modal__ref">{{ cancelModal.booking?.booking_reference }}</p>
              <p class="cancel-modal__warn">
                Are you sure you want to cancel your stay at
                <strong>{{ cancelModal.booking?.room?.name }}</strong>
                ({{ formatDate(cancelModal.booking?.check_in_date) }} → {{ formatDate(cancelModal.booking?.check_out_date) }})?
              </p>
              <div class="pf__field">
                <label class="pf__label" for="cancel-reason">Reason <span style="font-weight:400;text-transform:none">(optional)</span></label>
                <textarea id="cancel-reason" v-model="cancelModal.reason" class="pf__input pf__textarea" rows="3" placeholder="Let us know why you're cancelling…" />
              </div>
            </div>
            <div class="cancel-modal__footer">
              <button class="pf__submit pf__submit--ghost" @click="cancelModal.open = false">Keep Booking</button>
              <button class="pf__submit pf__submit--danger" :disabled="cancelModal.loading" @click="submitCancel">
                <span v-if="cancelModal.loading" class="pf__spinner" />
                {{ cancelModal.loading ? 'Cancelling…' : 'Yes, Cancel' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- ── QR Check-in modal ──────────────────────────────────────────── -->
    <teleport to="body">
      <transition name="modal">
        <div v-if="qrModal.open" class="modal-overlay" @click.self="qrModal.open = false">
          <CheckInQrCode
            :booking="qrModal.booking"
            @close="qrModal.open = false"
          />
        </div>
      </transition>
    </teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import { authService }  from '../services/auth'
import { bookingsService } from '../services/bookings'
import BookingStatusBadge from '../components/admin/BookingStatusBadge.vue'
import ReviewForm from '../components/reviews/ReviewForm.vue'
import CheckInQrCode from '../components/booking/CheckInQrCode.vue'

const FALLBACK = 'https://placehold.co/80x80/e0e7ff/4f46e5?text=Room'

const auth = useAuthStore()

// ── Tabs ───────────────────────────────────────────────────────────
const tabs = [
  { key: 'bookings', label: 'My Bookings', icon: '📋' },
  { key: 'edit',     label: 'Edit Profile', icon: '✏️' },
]
const activeTab = ref('bookings')

// ── Computed user info ─────────────────────────────────────────────
const initials = computed(() =>
  (auth.user?.name ?? 'U').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
)
const memberSince = computed(() => {
  if (!auth.user?.created_at) return '—'
  return new Date(auth.user.created_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

// ── Bookings ───────────────────────────────────────────────────────
const bookings        = ref([])
const bookingsLoading = ref(false)
const pagination      = reactive({ current_page: 1, last_page: 1, total: 0, per_page: 10 })

const bookingFilters = [
  { value: 'all',         label: 'All' },
  { value: 'upcoming',    label: 'Upcoming' },
  { value: 'active',      label: 'Active' },
  { value: 'past',        label: 'Past' },
  { value: 'cancelled',   label: 'Cancelled' },
]
const bookingFilter = ref('all')

const today = new Date().toISOString().split('T')[0]

const filteredBookings = computed(() => {
  if (bookingFilter.value === 'all') return bookings.value
  if (bookingFilter.value === 'upcoming')  return bookings.value.filter(b => b.check_in_date > today && !['cancelled'].includes(b.status))
  if (bookingFilter.value === 'active')    return bookings.value.filter(b => ['confirmed', 'checked_in'].includes(b.status))
  if (bookingFilter.value === 'past')      return bookings.value.filter(b => b.check_out_date < today && b.status !== 'cancelled')
  if (bookingFilter.value === 'cancelled') return bookings.value.filter(b => b.status === 'cancelled')
  return bookings.value
})

const stats = computed(() => {
  const all = bookings.value
  return {
    total:    pagination.total,
    upcoming: all.filter(b => b.check_in_date > today && !['cancelled'].includes(b.status)).length,
    spent:    all.filter(b => b.payment_status === 'paid').reduce((s, b) => s + (b.final_amount ?? 0), 0),
  }
})

async function loadBookings(page = 1) {
  bookingsLoading.value = true
  try {
    const { data } = await bookingsService.list({ page, per_page: 20 })
    bookings.value = data.data.bookings.data ?? data.data.bookings
    Object.assign(pagination, data.data.pagination)
  } finally {
    bookingsLoading.value = false
  }
}

function isCancellable(b) {
  return ['pending', 'confirmed'].includes(b.status) && b.check_in_date >= today
}

function isReviewable(b) {
  if (b.check_out_date >= today) return false // not yet checked out
  return b.status === 'checked_out' || ['confirmed', 'paid'].includes(b.status)
}

// ── Review modal ───────────────────────────────────────────────────
const reviewModal = reactive({ open: false, booking: null, existing: null })

function openReviewModal(booking) {
  reviewModal.booking  = booking
  reviewModal.existing = booking.review ?? null
  reviewModal.open     = true
}

function onReviewSubmitted(review) {
  // Mark the booking card as reviewed in-place
  const idx = bookings.value.findIndex(b => b.id === reviewModal.booking.id)
  if (idx !== -1) bookings.value[idx] = { ...bookings.value[idx], review }
}

// ── Cancel modal ───────────────────────────────────────────────────
const cancelModal = reactive({ open: false, booking: null, reason: '', loading: false })

// ── QR modal ───────────────────────────────────────────────────────
const qrModal = reactive({ open: false, booking: null })

function openQrModal(booking) {
  qrModal.booking = booking
  qrModal.open    = true
}

function openCancelModal(booking) {
  cancelModal.booking = booking
  cancelModal.reason  = ''
  cancelModal.open    = true
}

async function submitCancel() {
  cancelModal.loading = true
  try {
    const { data } = await bookingsService.cancel(cancelModal.booking.id, { reason: cancelModal.reason })
    const idx = bookings.value.findIndex(b => b.id === cancelModal.booking.id)
    if (idx !== -1) bookings.value[idx] = data.data
    cancelModal.open = false
  } finally {
    cancelModal.loading = false
  }
}

// ── Profile form ───────────────────────────────────────────────────
const profileForm = reactive({
  name:        auth.user?.name        ?? '',
  phone:       auth.user?.phone       ?? '',
  nationality: auth.user?.nationality ?? '',
  address:     auth.user?.address     ?? '',
})
const profileErrors  = reactive({})
const profileSaving  = ref(false)
const profileSuccess = ref('')
const profileError   = ref('')

watch(() => auth.user, (u) => {
  if (!u) return
  profileForm.name        = u.name        ?? ''
  profileForm.phone       = u.phone       ?? ''
  profileForm.nationality = u.nationality ?? ''
  profileForm.address     = u.address     ?? ''
}, { immediate: true })

async function saveProfile() {
  profileSaving.value  = true
  profileSuccess.value = ''
  profileError.value   = ''
  Object.keys(profileErrors).forEach(k => delete profileErrors[k])
  try {
    const { data } = await authService.updateProfile({ ...profileForm })
    auth.setAuth(data.data, auth.token)
    profileSuccess.value = 'Profile updated successfully.'
  } catch (e) {
    const errs = e.response?.data?.errors ?? {}
    Object.assign(profileErrors, errs)
    profileError.value = e.response?.data?.message ?? 'Update failed.'
  } finally {
    profileSaving.value = false
  }
}

// ── Password form ──────────────────────────────────────────────────
const pwForm    = reactive({ current_password: '', password: '', password_confirmation: '' })
const pwErrors  = reactive({})
const pwSaving  = ref(false)
const pwSuccess = ref('')
const pwError   = ref('')

async function savePassword() {
  pwSaving.value  = true
  pwSuccess.value = ''
  pwError.value   = ''
  Object.keys(pwErrors).forEach(k => delete pwErrors[k])
  try {
    await authService.changePassword({ ...pwForm })
    pwSuccess.value = 'Password changed successfully.'
    pwForm.current_password = ''
    pwForm.password = ''
    pwForm.password_confirmation = ''
  } catch (e) {
    const errs = e.response?.data?.errors ?? {}
    Object.assign(pwErrors, errs)
    pwError.value = e.response?.data?.message ?? 'Password change failed.'
  } finally {
    pwSaving.value = false
  }
}

// ── Helpers ────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
function formatPrice(n) {
  return Number(n ?? 0).toLocaleString('en-ET', { minimumFractionDigits: 2 })
}

onMounted(loadBookings)
</script>

<style scoped>
/* ── Page ────────────────────────────────────────────────────────── */
.profile { min-height: 100vh; background: var(--bg-soft); }

/* ── Banner ──────────────────────────────────────────────────────── */
.profile__banner {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2b55 100%);
  padding: 36px 20px 28px;
}
.profile__banner-inner {
  max-width: 960px; margin: 0 auto;
  display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}

.profile__avatar-wrap { position: relative; flex-shrink: 0; }
.profile__avatar {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: #fff; font-size: 24px; font-weight: 900;
  display: flex; align-items: center; justify-content: center;
  border: 3px solid rgba(255,255,255,0.2);
}
.profile__verified-dot {
  position: absolute; bottom: 0; right: 0;
  width: 20px; height: 20px; border-radius: 50%;
  background: #10b981; color: #fff;
  font-size: 10px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #1a1a2e;
}

.profile__banner-info { flex: 1; min-width: 0; }
.profile__name  { font-size: 1.4rem; font-weight: 900; color: #fff; margin-bottom: 3px; }
.profile__email { font-size: 13px; color: #a5b4fc; margin-bottom: 8px; }
.profile__meta  { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12.5px; color: #94a3b8; }

.profile__stats {
  display: flex; gap: 24px; flex-shrink: 0;
  background: rgba(255,255,255,0.07); border-radius: 12px;
  padding: 14px 20px;
}
.profile__stat { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.profile__stat-value { font-size: 18px; font-weight: 900; color: #fff; }
.profile__stat-label { font-size: 11px; color: #94a3b8; white-space: nowrap; }

/* ── Tabs bar ────────────────────────────────────────────────────── */
.profile__tabs-bar {
  display: flex; gap: 0;
  background: #fff; border-bottom: 1px solid #f0f0f0;
  padding: 0 20px; max-width: 960px; margin: 0 auto;
  position: sticky; top: 0; z-index: 10;
}
.profile__tab {
  display: flex; align-items: center; gap: 6px;
  padding: 14px 18px; background: none; border: none;
  font-size: 14px; font-weight: 600; color: #6b7280;
  cursor: pointer; border-bottom: 2.5px solid transparent;
  transition: color 0.15s, border-color 0.15s;
  position: relative;
}
.profile__tab:hover { color: #4f46e5; }
.profile__tab--active { color: #4f46e5; border-bottom-color: #4f46e5; }
.profile__tab-badge {
  background: #4f46e5; color: #fff;
  font-size: 10px; font-weight: 800;
  padding: 1px 6px; border-radius: 99px;
}

/* ── Body ────────────────────────────────────────────────────────── */
.profile__body { max-width: 960px; margin: 0 auto; padding: 28px 20px; }
.profile__tab-content { display: flex; flex-direction: column; gap: 16px; }
.profile__tab-content--narrow { max-width: 520px; }

/* Filter pills */
.profile__filter-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.profile__pill {
  padding: 6px 16px; border-radius: 99px;
  border: 1.5px solid #e5e7eb; background: #fff;
  font-size: 13px; font-weight: 600; color: #6b7280;
  cursor: pointer; transition: all 0.15s;
}
.profile__pill:hover { border-color: #4f46e5; color: #4f46e5; }
.profile__pill--active { background: #4f46e5; border-color: #4f46e5; color: #fff; }

/* ── Booking card ────────────────────────────────────────────────── */
.profile__booking-list { display: flex; flex-direction: column; gap: 14px; }

.booking-card {
  background: var(--bg-card); border-radius: 14px;
  border: 1.5px solid var(--border);
  box-shadow: var(--shadow-sm);
  display: flex; gap: 0; overflow: hidden;
  transition: box-shadow 0.15s;
}
.booking-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.09); }
.booking-card--cancelled { opacity: 0.7; }

.booking-card__img-wrap {
  position: relative; width: 110px; flex-shrink: 0;
}
.booking-card__img {
  width: 100%; height: 100%; object-fit: cover; display: block;
}
.booking-card__type {
  position: absolute; top: 8px; left: 8px;
  padding: 2px 8px; border-radius: 99px;
  font-size: 9.5px; font-weight: 800; text-transform: uppercase;
  color: #fff; letter-spacing: 0.4px;
}
.booking-card__type--single    { background: #10b981; }
.booking-card__type--double    { background: #3b82f6; }
.booking-card__type--deluxe    { background: #f59e0b; }
.booking-card__type--suite     { background: #8b5cf6; }
.booking-card__type--penthouse { background: #ef4444; }

.booking-card__body {
  flex: 1; padding: 16px 18px;
  display: flex; flex-direction: column; gap: 10px; min-width: 0;
}
.booking-card__top {
  display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
}
.booking-card__ref  { font-size: 11px; color: #9ca3af; font-family: monospace; margin-bottom: 2px; }
.booking-card__room { font-size: 15px; font-weight: 800; color: var(--text); }

.booking-card__dates {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.booking-card__date-block { display: flex; flex-direction: column; gap: 1px; }
.booking-card__date-label { font-size: 10.5px; color: #9ca3af; font-weight: 600; text-transform: uppercase; }
.booking-card__date-val   { font-size: 13.5px; font-weight: 700; color: #1a202c; }
.booking-card__date-arrow { color: #d1d5db; font-size: 16px; }
.booking-card__nights {
  margin-left: auto; font-size: 12px; font-weight: 700;
  color: #5b21b6; background: #ede9fe;
  padding: 3px 10px; border-radius: 99px;
}

.booking-card__footer {
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 8px;
  padding-top: 8px; border-top: 1px solid #f3f4f6;
}
.booking-card__price-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.booking-card__guests { font-size: 12.5px; color: #6b7280; }
.booking-card__amount { font-size: 15px; font-weight: 800; color: #4f46e5; }
.booking-card__payment {
  font-size: 11px; font-weight: 700; padding: 2px 8px;
  border-radius: 99px; text-transform: capitalize;
}
.booking-card__payment--unpaid   { background: #fef3c7; color: #92400e; }
.booking-card__payment--paid     { background: #d1fae5; color: #065f46; }
.booking-card__payment--refunded { background: #e0e7ff; color: #3730a3; }

.booking-card__actions { display: flex; gap: 8px; }
.booking-card__btn {
  padding: 7px 14px; border-radius: 7px;
  font-size: 13px; font-weight: 700; cursor: pointer;
  border: none; text-decoration: none; display: inline-flex;
  align-items: center; transition: background 0.15s;
}
.booking-card__btn--cancel { background: #fee2e2; color: #991b1b; }
.booking-card__btn--cancel:hover { background: #fecaca; }
.booking-card__btn--pay    { background: #4f46e5; color: #fff; }
.booking-card__btn--pay:hover { background: #4338ca; }
.booking-card__btn--review { background: #fef3c7; color: #92400e; }
.booking-card__btn--review:hover { background: #fde68a; }
.booking-card__btn--qr { background: #ecfdf5; color: #065f46; }
.booking-card__btn--qr:hover { background: #d1fae5; }
.booking-card__reviewed {
  font-size: 12px; font-weight: 700; color: #10b981;
  padding: 7px 10px;
}

.booking-card__cancel-reason {
  font-size: 12px; color: #6b7280;
  background: #f9fafb; border-radius: 6px; padding: 6px 10px;
}

/* Skeleton */
.booking-card--skeleton { padding: 16px; gap: 14px; }
.skel { border-radius: 6px; }
.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* Empty */
.profile__empty {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  padding: 60px 20px; background: #fff; border-radius: 14px;
  border: 1.5px dashed #e5e7eb; text-align: center; color: #9ca3af;
}
.profile__empty-icon { font-size: 2.5rem; }
.profile__empty p    { font-size: 14px; font-weight: 600; }
.profile__empty-cta {
  padding: 9px 20px; background: #4f46e5; color: #fff;
  border-radius: 8px; text-decoration: none; font-size: 13.5px; font-weight: 700;
  transition: background 0.15s;
}
.profile__empty-cta:hover { background: #4338ca; }

/* Pagination */
.profile__pagination {
  display: flex; align-items: center; justify-content: center; gap: 12px;
  padding-top: 8px;
}
.profile__page-btn {
  width: 34px; height: 34px; border-radius: 8px;
  border: 1.5px solid #e5e7eb; background: #fff;
  font-size: 16px; cursor: pointer; transition: all 0.15s;
}
.profile__page-btn:hover:not(:disabled) { border-color: #4f46e5; color: #4f46e5; }
.profile__page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.profile__page-info { font-size: 13px; color: #6b7280; font-weight: 600; }

/* ── Profile form ────────────────────────────────────────────────── */
.profile__section-title { font-size: 15px; font-weight: 800; color: #1a202c; margin-bottom: 16px; }
.profile__divider { border: none; border-top: 1px solid #f0f0f0; margin: 28px 0; }
.profile__form { display: flex; flex-direction: column; gap: 16px; }

.pf__field { display: flex; flex-direction: column; gap: 5px; }
.pf__label {
  font-size: 11.5px; font-weight: 700; color: #6b7280;
  text-transform: uppercase; letter-spacing: 0.4px;
}
.pf__input {
  padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 9px;
  font-size: 14px; color: #1a202c; background: #fafafa;
  outline: none; transition: border-color 0.2s; font-family: inherit;
}
.pf__input:focus { border-color: #4f46e5; background: #fff; }
.pf__input--error    { border-color: #ef4444; }
.pf__input--readonly { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
.pf__textarea { resize: vertical; min-height: 80px; }
.pf__hint  { font-size: 11.5px; color: #9ca3af; }
.pf__error { font-size: 11.5px; color: #ef4444; }
.pf__success      { padding: 10px 14px; background: #d1fae5; border-radius: 8px; color: #065f46; font-size: 13px; font-weight: 600; }
.pf__error-banner { padding: 10px 14px; background: #fee2e2; border-radius: 8px; color: #991b1b; font-size: 13px; }

.pf__submit {
  padding: 11px 20px; background: #4f46e5; color: #fff;
  border: none; border-radius: 9px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: background 0.15s, opacity 0.15s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  align-self: flex-start;
}
.pf__submit:hover:not(:disabled) { background: #4338ca; }
.pf__submit:disabled { opacity: 0.5; cursor: not-allowed; }
.pf__submit--danger { background: #ef4444; }
.pf__submit--danger:hover:not(:disabled) { background: #dc2626; }
.pf__submit--ghost  { background: #f3f4f6; color: #374151; }
.pf__submit--ghost:hover { background: #e5e7eb; }

.pf__spinner {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Cancel modal ────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.cancel-modal {
  background: #fff; border-radius: 16px; width: 100%; max-width: 460px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;
}
.cancel-modal__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 22px; border-bottom: 1px solid #f0f0f0;
}
.cancel-modal__header h3 { font-size: 15px; font-weight: 800; color: #1a202c; }
.cancel-modal__header button {
  background: none; border: none; font-size: 16px; color: #9ca3af;
  cursor: pointer; padding: 4px; border-radius: 6px;
}
.cancel-modal__header button:hover { background: #f3f4f6; }
.cancel-modal__body { padding: 20px 22px; display: flex; flex-direction: column; gap: 14px; }
.cancel-modal__ref  { font-size: 12px; color: #6b7280; font-family: monospace; font-weight: 700; }
.cancel-modal__warn { font-size: 14px; color: #374151; line-height: 1.6; }
.cancel-modal__footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 16px 22px; border-top: 1px solid #f0f0f0;
}

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-active .cancel-modal, .modal-leave-active .cancel-modal { transition: transform 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .cancel-modal, .modal-leave-to .cancel-modal { transform: scale(0.95); }

/* Review modal */
.review-modal-wrap {
  width: 100%; max-width: 580px;
  max-height: 90vh; overflow-y: auto;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

@media (max-width: 640px) {
  .profile__banner-inner { flex-direction: column; align-items: flex-start; }
  .profile__stats { width: 100%; justify-content: space-around; }
  .booking-card__img-wrap { width: 80px; }
  .profile__tab-content--narrow { max-width: 100%; }
}
</style>
