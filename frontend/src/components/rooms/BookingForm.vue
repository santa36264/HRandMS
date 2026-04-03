<template>
  <div class="bf">

    <!-- ── Left panel: form ──────────────────────────────────────── -->
    <div class="bf__form-panel">

      <!-- Room gallery -->
      <div class="bf__gallery">
        <div class="bf__gallery-main">
          <img
            :src="galleryImages[activeImg] ?? FALLBACK"
            :alt="room.name"
            class="bf__gallery-main-img"
            @error="e => e.target.src = FALLBACK"
          />
          <span class="bf__room-type-badge" :class="`bf__room-type-badge--${room.type}`">
            {{ room.type }}
          </span>
          <span v-if="galleryImages.length > 1" class="bf__gallery-counter">
            {{ activeImg + 1 }} / {{ galleryImages.length }}
          </span>
          <button v-if="activeImg > 0" class="bf__gallery-nav bf__gallery-nav--prev" @click="activeImg--">‹</button>
          <button v-if="activeImg < galleryImages.length - 1" class="bf__gallery-nav bf__gallery-nav--next" @click="activeImg++">›</button>
        </div>
        <div v-if="galleryImages.length > 1" class="bf__gallery-thumbs">
          <img
            v-for="(img, i) in galleryImages"
            :key="i"
            :src="img"
            :alt="`Photo ${i + 1}`"
            class="bf__gallery-thumb"
            :class="{ 'bf__gallery-thumb--active': activeImg === i }"
            @click="activeImg = i"
            @error="e => e.target.src = FALLBACK"
          />
        </div>
        <div class="bf__room-info">
          <p class="bf__room-number">Room {{ room.room_number }} · Floor {{ room.floor }}</p>
          <h2 class="bf__room-name">{{ room.name }}</h2>
          <div class="bf__room-meta">
            <span>👥 Up to {{ room.capacity }} guests</span>
            <span v-if="room.average_rating">⭐ {{ room.average_rating }}</span>
          </div>
        </div>
      </div>

      <div class="bf__divider" />

      <!-- Step indicator -->
      <div class="bf__steps">
        <div
          v-for="(step, i) in steps"
          :key="i"
          class="bf__step"
          :class="{
            'bf__step--active':    currentStep === i,
            'bf__step--complete':  currentStep > i,
          }"
          @click="currentStep > i && (currentStep = i)"
        >
          <div class="bf__step-dot">
            <span v-if="currentStep > i">✓</span>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <span class="bf__step-label">{{ step }}</span>
        </div>
        <div class="bf__step-line" />
      </div>

      <!-- ── Step 0: Dates & Guests ─────────────────────────────── -->
      <transition name="step-slide" mode="out-in">
        <div v-if="currentStep === 0" key="step0" class="bf__step-body">
          <h3 class="bf__step-title">Stay Details</h3>

          <div class="bf__grid">
            <!-- Check-in -->
            <div class="bf__field">
              <label class="bf__label" for="bf-checkin">Check-in Date</label>
              <div class="bf__input-wrap">
                <span class="bf__input-icon">📅</span>
                <input
                  id="bf-checkin" type="date"
                  class="bf__input" :class="{ 'bf__input--error': errors.check_in_date }"
                  v-model="form.check_in_date"
                  :min="today"
                  @change="onCheckInChange"
                />
              </div>
              <span v-if="errors.check_in_date" class="bf__field-error">{{ errors.check_in_date }}</span>
            </div>

            <!-- Check-out -->
            <div class="bf__field">
              <label class="bf__label" for="bf-checkout">Check-out Date</label>
              <div class="bf__input-wrap">
                <span class="bf__input-icon">📅</span>
                <input
                  id="bf-checkout" type="date"
                  class="bf__input" :class="{ 'bf__input--error': errors.check_out_date }"
                  v-model="form.check_out_date"
                  :min="minCheckOut"
                  @change="onCheckOutChange"
                />
              </div>
              <span v-if="errors.check_out_date" class="bf__field-error">{{ errors.check_out_date }}</span>
            </div>

            <!-- Guests -->
            <div class="bf__field">
              <label class="bf__label" for="bf-guests">Number of Guests</label>
              <div class="bf__input-wrap">
                <span class="bf__input-icon">👥</span>
                <select id="bf-guests" class="bf__input" v-model="form.guests_count">
                  <option v-for="n in room.capacity" :key="n" :value="n">
                    {{ n }} {{ n === 1 ? 'Guest' : 'Guests' }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Nights display -->
            <div v-if="nights > 0" class="bf__nights-pill">
              🌙 {{ nights }} night{{ nights > 1 ? 's' : '' }}
            </div>
          </div>

          <button
            class="bf__next-btn"
            :disabled="!datesValid"
            @click="goNext"
          >
            Continue to Guest Details →
          </button>
        </div>

        <!-- ── Step 1: Guest Details ──────────────────────────────── -->
        <div v-else-if="currentStep === 1" key="step1" class="bf__step-body">
          <h3 class="bf__step-title">Guest Details</h3>

          <div class="bf__field">
            <label class="bf__label" for="bf-name">Full Name</label>
            <div class="bf__input-wrap">
              <span class="bf__input-icon">👤</span>
              <input
                id="bf-name" type="text"
                class="bf__input" :class="{ 'bf__input--error': errors.guest_name }"
                v-model="guestDetails.name"
                placeholder="As on your ID"
                autocomplete="name"
              />
            </div>
            <span v-if="errors.guest_name" class="bf__field-error">{{ errors.guest_name }}</span>
          </div>

          <div class="bf__field">
            <label class="bf__label" for="bf-email">Email Address</label>
            <div class="bf__input-wrap">
              <span class="bf__input-icon">✉️</span>
              <input
                id="bf-email" type="email"
                class="bf__input" :class="{ 'bf__input--error': errors.guest_email }"
                v-model="guestDetails.email"
                placeholder="Confirmation will be sent here"
                autocomplete="email"
              />
            </div>
            <span v-if="errors.guest_email" class="bf__field-error">{{ errors.guest_email }}</span>
          </div>

          <div class="bf__field">
            <label class="bf__label" for="bf-phone">Phone Number</label>
            <div class="bf__input-wrap">
              <span class="bf__input-icon">📞</span>
              <input
                id="bf-phone" type="tel"
                class="bf__input" :class="{ 'bf__input--error': errors.guest_phone }"
                v-model="guestDetails.phone"
                placeholder="+251 9XX XXX XXX"
                autocomplete="tel"
              />
            </div>
            <span v-if="errors.guest_phone" class="bf__field-error">{{ errors.guest_phone }}</span>
          </div>

          <div class="bf__field">
            <label class="bf__label" for="bf-requests">
              Special Requests
              <span class="bf__label-optional">optional</span>
            </label>
            <textarea
              id="bf-requests"
              class="bf__input bf__textarea"
              v-model="form.special_requests"
              placeholder="e.g. early check-in, extra pillows, ground floor, dietary needs…"
              rows="4"
              maxlength="500"
            />
            <div class="bf__char-count">
              <span :class="{ 'bf__char-count--warn': form.special_requests.length > 450 }">
                {{ form.special_requests.length }}/500
              </span>
            </div>
          </div>

          <div class="bf__step-nav">
            <button class="bf__back-btn" @click="currentStep = 0">← Back</button>
            <button class="bf__next-btn" :disabled="!guestValid" @click="goNext">
              Review Booking →
            </button>
          </div>
        </div>

        <!-- ── Step 2: Review & Confirm ──────────────────────────── -->
        <div v-else key="step2" class="bf__step-body">
          <h3 class="bf__step-title">Review & Confirm</h3>

          <!-- Summary rows -->
          <div class="bf__review">
            <div class="bf__review-row">
              <span>Check-in</span>
              <strong>{{ formatDate(form.check_in_date) }}</strong>
            </div>
            <div class="bf__review-row">
              <span>Check-out</span>
              <strong>{{ formatDate(form.check_out_date) }}</strong>
            </div>
            <div class="bf__review-row">
              <span>Duration</span>
              <strong>{{ nights }} night{{ nights > 1 ? 's' : '' }}</strong>
            </div>
            <div class="bf__review-row">
              <span>Guests</span>
              <strong>{{ form.guests_count }}</strong>
            </div>
            <div class="bf__review-row">
              <span>Guest Name</span>
              <strong>{{ guestDetails.name }}</strong>
            </div>
            <div class="bf__review-row">
              <span>Email</span>
              <strong>{{ guestDetails.email }}</strong>
            </div>
            <div v-if="form.special_requests" class="bf__review-row bf__review-row--block">
              <span>Special Requests</span>
              <p>{{ form.special_requests }}</p>
            </div>
          </div>

          <p v-if="submitError" class="bf__submit-error" role="alert">⚠️ {{ submitError }}</p>

          <div class="bf__step-nav">
            <button class="bf__back-btn" @click="currentStep = 1">← Back</button>
            <button
              class="bf__confirm-btn"
              :disabled="!canSubmit"
              @click="submit"
            >
              <span v-if="submitting" class="bf__spinner" aria-hidden="true" />
              {{ submitting ? 'Confirming…' : 'Confirm Booking' }}
            </button>
          </div>
        </div>
      </transition>

    </div>

    <!-- ── Right panel: live price ───────────────────────────────── -->
    <div class="bf__price-panel">
      <h3 class="bf__price-title">Price Summary</h3>

      <!-- Loading state -->
      <div v-if="previewLoading" class="bf__price-skeleton">
        <div v-for="i in 4" :key="i" class="bf__skel-line shimmer" />
      </div>

      <!-- No dates yet -->
      <div v-else-if="!datesValid" class="bf__price-empty">
        <span class="bf__price-empty-icon">🗓️</span>
        <p>Select your dates to see the price breakdown</p>
      </div>

      <!-- Price breakdown -->
      <transition name="fade" v-else>
        <div v-if="preview" class="bf__price-body">

          <!-- Availability pill -->
          <div
            class="bf__avail-pill"
            :class="preview.is_available ? 'bf__avail-pill--ok' : 'bf__avail-pill--no'"
          >
            <span>{{ preview.is_available ? '✓' : '✗' }}</span>
            {{ preview.is_available ? 'Available for these dates' : 'Not available — choose other dates' }}
          </div>

          <!-- Breakdown rows -->
          <div class="bf__price-rows">
            <div class="bf__price-row">
              <span>ETB {{ formatPrice(room.price_per_night) }} × {{ preview.nights }} night{{ preview.nights > 1 ? 's' : '' }}</span>
              <span>ETB {{ formatPrice(preview.total_amount) }}</span>
            </div>
            <div v-if="preview.discount_amount > 0" class="bf__price-row bf__price-row--discount">
              <span>Discount</span>
              <span>− ETB {{ formatPrice(preview.discount_amount) }}</span>
            </div>
            <div class="bf__price-row bf__price-row--taxes">
              <span>Taxes & fees</span>
              <span>Included</span>
            </div>
          </div>

          <div class="bf__price-divider" />

          <div class="bf__price-total">
            <span>Total</span>
            <span class="bf__price-total-amount">ETB {{ formatPrice(preview.final_amount) }}</span>
          </div>

          <p class="bf__price-note">
            Free cancellation up to 24 hours before check-in
          </p>
        </div>
      </transition>

      <!-- Rate card (always visible) -->
      <div class="bf__rate-card">
        <div class="bf__rate-row">
          <span class="bf__rate-label">Nightly rate</span>
          <span class="bf__rate-value">ETB {{ formatPrice(room.price_per_night) }}</span>
        </div>
        <div v-if="nights > 0" class="bf__rate-row">
          <span class="bf__rate-label">Nights</span>
          <span class="bf__rate-value">{{ nights }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { bookingsService } from '../../services/bookings'

const FALLBACK = 'https://placehold.co/120x120/e0e7ff/4f46e5?text=Room'

const props = defineProps({
  room:            { type: Object, required: true },
  initialCheckIn:  { type: String, default: '' },
  initialCheckOut: { type: String, default: '' },
})
const emit = defineEmits(['booked'])

const auth = useAuthStore()

// ── Gallery ────────────────────────────────────────────────────────
const activeImg = ref(0)
const galleryImages = computed(() =>
  props.room.images?.length ? props.room.images : [FALLBACK]
)

// ── Steps ──────────────────────────────────────────────────────────
const steps       = ['Stay Details', 'Guest Info', 'Confirm']
const currentStep = ref(0)

// ── Form state ─────────────────────────────────────────────────────
const form = reactive({
  room_id:          props.room.id,
  check_in_date:    props.initialCheckIn,
  check_out_date:   props.initialCheckOut,
  guests_count:     1,
  special_requests: '',
  discount_amount:  0,
})

// Pre-fill guest details from auth store
const guestDetails = reactive({
  name:  auth.user?.name  ?? '',
  email: auth.user?.email ?? '',
  phone: auth.user?.phone ?? '',
})

const errors      = reactive({})
const submitError = ref('')
const submitting  = ref(false)

// ── Preview state ──────────────────────────────────────────────────
const preview        = ref(null)
const previewLoading = ref(false)

// ── Computed ───────────────────────────────────────────────────────
const today = computed(() => new Date().toISOString().split('T')[0])

const minCheckOut = computed(() => {
  if (!form.check_in_date) return today.value
  const d = new Date(form.check_in_date)
  d.setDate(d.getDate() + 1)
  return d.toISOString().split('T')[0]
})

const nights = computed(() => {
  if (!form.check_in_date || !form.check_out_date) return 0
  const diff = new Date(form.check_out_date) - new Date(form.check_in_date)
  return Math.max(0, Math.round(diff / 86400000))
})

const datesValid = computed(() =>
  !!form.check_in_date &&
  !!form.check_out_date &&
  form.check_out_date > form.check_in_date
)

const guestValid = computed(() =>
  guestDetails.name.trim().length >= 2 &&
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(guestDetails.email)
)

const canSubmit = computed(() =>
  datesValid.value &&
  guestValid.value &&
  preview.value?.is_available &&
  !submitting.value &&
  !previewLoading.value
)

// ── Date handlers ──────────────────────────────────────────────────
function onCheckInChange() {
  if (form.check_out_date && form.check_out_date <= form.check_in_date) {
    form.check_out_date = minCheckOut.value
  }
  clearFieldErrors()
}

function onCheckOutChange() {
  clearFieldErrors()
}

function clearFieldErrors() {
  delete errors.check_in_date
  delete errors.check_out_date
  submitError.value = ''
}

// ── Step navigation ────────────────────────────────────────────────
function goNext() {
  if (currentStep.value === 0 && datesValid.value) currentStep.value = 1
  else if (currentStep.value === 1 && guestValid.value) currentStep.value = 2
}

// ── Preview fetch (debounced) ──────────────────────────────────────
let previewTimer = null
watch([() => form.check_in_date, () => form.check_out_date], () => {
  clearTimeout(previewTimer)
  preview.value = null
  if (datesValid.value) {
    previewTimer = setTimeout(fetchPreview, 450)
  }
})

async function fetchPreview() {
  previewLoading.value = true
  try {
    const { data } = await bookingsService.preview({
      room_id:        form.room_id,
      check_in_date:  form.check_in_date,
      check_out_date: form.check_out_date,
    })
    preview.value = data.data
  } catch (e) {
    const errs = e.response?.data?.errors ?? {}
    Object.assign(errors, errs)
  } finally {
    previewLoading.value = false
  }
}

// ── Submit ─────────────────────────────────────────────────────────
async function submit() {
  if (!canSubmit.value) return
  submitting.value  = true
  submitError.value = ''

  try {
    const { data } = await bookingsService.store({ ...form })
    emit('booked', data.data)
  } catch (e) {
    const errs = e.response?.data?.errors ?? {}
    Object.assign(errors, errs)
    submitError.value = e.response?.data?.message ?? 'Booking failed. Please try again.'
    // Jump back to the step with the error
    if (errs.check_in_date || errs.check_out_date) currentStep.value = 0
  } finally {
    submitting.value = false
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

// Fetch preview immediately if dates are pre-filled
if (datesValid.value) fetchPreview()
</script>

<style scoped>
/* ── Layout ──────────────────────────────────────────────────────── */
.bf {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 24px;
  align-items: start;
  width: 100%;
  max-width: 900px;
}

/* ── Form panel ──────────────────────────────────────────────────── */
.bf__form-panel {
  background: var(--bg-card);
  border-radius: 16px;
  padding: 28px;
  box-shadow: var(--shadow-md);
}

/* Room gallery */
.bf__gallery { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }

.bf__gallery-main {
  position: relative; width: 100%; height: 240px;
  border-radius: 12px; overflow: hidden; background: #f0f0f0;
}
.bf__gallery-main-img {
  width: 100%; height: 100%; object-fit: cover; display: block;
  transition: opacity 0.2s;
}
.bf__gallery-counter {
  position: absolute; bottom: 10px; right: 10px;
  background: rgba(0,0,0,0.55); color: #fff;
  font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 99px;
}
.bf__gallery-nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  background: rgba(0,0,0,0.45); color: #fff; border: none;
  width: 32px; height: 32px; border-radius: 50%;
  font-size: 18px; cursor: pointer; display: flex;
  align-items: center; justify-content: center;
  transition: background 0.15s;
}
.bf__gallery-nav:hover { background: rgba(0,0,0,0.7); }
.bf__gallery-nav--prev { left: 10px; }
.bf__gallery-nav--next { right: 10px; }

.bf__gallery-thumbs {
  display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px;
}
.bf__gallery-thumb {
  width: 60px; height: 48px; object-fit: cover; border-radius: 7px;
  cursor: pointer; flex-shrink: 0; opacity: 0.6;
  border: 2px solid transparent; transition: opacity 0.15s, border-color 0.15s;
}
.bf__gallery-thumb:hover { opacity: 0.85; }
.bf__gallery-thumb--active { opacity: 1; border-color: #c9a84c; }

.bf__room-info { flex: 1; min-width: 0; }
.bf__room-number { font-size: 11px; color: #9ca3af; margin-bottom: 3px; }
.bf__room-name   { font-size: 17px; font-weight: 800; color: var(--text); margin-bottom: 6px; }
.bf__room-meta   { display: flex; gap: 12px; font-size: 12px; color: #6b7280; }

.bf__room-type-badge {
  position: absolute; top: 10px; left: 10px;
  padding: 3px 12px; border-radius: 99px;
  font-size: 10px; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.5px; color: #fff; white-space: nowrap;
}
.bf__room-type-badge--single    { background: rgba(16,185,129,0.9); }
.bf__room-type-badge--double    { background: rgba(59,130,246,0.9); }
.bf__room-type-badge--deluxe    { background: rgba(245,158,11,0.9); }
.bf__room-type-badge--suite     { background: rgba(139,92,246,0.9); }
.bf__room-type-badge--penthouse { background: rgba(239,68,68,0.9); }

.bf__divider { border: none; border-top: 1px solid #f3f4f6; margin: 0 0 24px; }

/* Steps */
.bf__steps {
  display: flex; align-items: center; gap: 0;
  margin-bottom: 28px; position: relative;
}
.bf__step-line {
  position: absolute; top: 14px; left: 14px; right: 14px; height: 2px;
  background: #e5e7eb; z-index: 0;
}
.bf__step {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  flex: 1; position: relative; z-index: 1; cursor: default;
}
.bf__step--complete { cursor: pointer; }
.bf__step-dot {
  width: 28px; height: 28px; border-radius: 50%;
  background: #e5e7eb; color: #9ca3af;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700;
  transition: background 0.2s, color 0.2s;
}
.bf__step--active .bf__step-dot   { background: #4f46e5; color: #fff; }
.bf__step--complete .bf__step-dot { background: #10b981; color: #fff; }
.bf__step-label {
  font-size: 11px; font-weight: 600; color: #9ca3af;
  text-align: center; white-space: nowrap;
}
.bf__step--active .bf__step-label   { color: #4f46e5; }
.bf__step--complete .bf__step-label { color: #10b981; }

/* Step body */
.bf__step-body { display: flex; flex-direction: column; gap: 18px; }
.bf__step-title { font-size: 15px; font-weight: 800; color: #1a202c; margin-bottom: 2px; }

/* Grid */
.bf__grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
}

/* Fields */
.bf__field { display: flex; flex-direction: column; gap: 5px; }
.bf__label {
  font-size: 11.5px; font-weight: 700; color: #6b7280;
  text-transform: uppercase; letter-spacing: 0.4px;
  display: flex; align-items: center; gap: 6px;
}
.bf__label-optional {
  font-size: 10.5px; font-weight: 400; color: #9ca3af;
  text-transform: none; letter-spacing: 0;
}
.bf__input-wrap { position: relative; display: flex; align-items: center; }
.bf__input-icon { position: absolute; left: 10px; font-size: 14px; pointer-events: none; z-index: 1; }
.bf__input {
  width: 100%; padding: 10px 12px 10px 34px;
  border: 1.5px solid var(--border); border-radius: 9px;
  font-size: 14px; color: var(--text); background: var(--bg-input);
  outline: none; transition: border-color 0.2s, background 0.2s;
  font-family: inherit;
}
.bf__input:focus { border-color: var(--indigo); background: var(--bg-card); }
.bf__input--error { border-color: #ef4444; }
.bf__textarea {
  padding: 10px 12px; resize: vertical; min-height: 90px;
}
.bf__field-error { font-size: 11.5px; color: #ef4444; }
.bf__char-count  { text-align: right; font-size: 11px; color: #9ca3af; }
.bf__char-count--warn { color: #f59e0b; font-weight: 700; }

/* Nights pill */
.bf__nights-pill {
  grid-column: 1 / -1;
  display: inline-flex; align-items: center; gap: 6px;
  background: #ede9fe; color: #5b21b6;
  padding: 6px 14px; border-radius: 99px;
  font-size: 13px; font-weight: 700;
  width: fit-content;
}

/* Review */
.bf__review {
  background: #f9fafb; border-radius: 10px; padding: 16px;
  display: flex; flex-direction: column; gap: 0;
}
.bf__review-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 9px 0; border-bottom: 1px solid #f0f0f0;
  font-size: 13.5px;
}
.bf__review-row:last-child { border-bottom: none; }
.bf__review-row span:first-child { color: #6b7280; font-weight: 600; }
.bf__review-row strong { color: #1a202c; }
.bf__review-row--block { flex-direction: column; align-items: flex-start; gap: 4px; }
.bf__review-row--block p { font-size: 13px; color: #374151; margin: 0; }

/* Nav buttons */
.bf__step-nav { display: flex; gap: 10px; }
.bf__back-btn {
  padding: 11px 18px; background: #f3f4f6; color: #374151;
  border: none; border-radius: 9px; font-size: 14px; font-weight: 600;
  cursor: pointer; transition: background 0.15s;
}
.bf__back-btn:hover { background: #e5e7eb; }

.bf__next-btn {
  flex: 1; padding: 11px 18px; background: #4f46e5; color: #fff;
  border: none; border-radius: 9px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: background 0.15s, opacity 0.15s;
}
.bf__next-btn:hover:not(:disabled) { background: #4338ca; }
.bf__next-btn:disabled { opacity: 0.45; cursor: not-allowed; }

.bf__confirm-btn {
  flex: 1; padding: 13px 18px; background: #10b981; color: #fff;
  border: none; border-radius: 9px; font-size: 15px; font-weight: 800;
  cursor: pointer; transition: background 0.15s, opacity 0.15s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.bf__confirm-btn:hover:not(:disabled) { background: #059669; }
.bf__confirm-btn:disabled { opacity: 0.45; cursor: not-allowed; }

.bf__submit-error {
  padding: 10px 14px; background: #fee2e2; border-radius: 8px;
  color: #991b1b; font-size: 13px;
}

/* Spinner */
.bf__spinner {
  width: 16px; height: 16px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite; flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Price panel ─────────────────────────────────────────────────── */
.bf__price-panel {
  background: var(--bg-card); border-radius: 16px; padding: 24px;
  box-shadow: var(--shadow-md);
  position: sticky; top: 20px;
}
.bf__price-title {
  font-size: 15px; font-weight: 800; color: var(--text); margin-bottom: 18px;
}

/* Skeleton */
.bf__price-skeleton { display: flex; flex-direction: column; gap: 12px; }
.bf__skel-line {
  height: 16px; border-radius: 6px;
}
.bf__skel-line:nth-child(2) { width: 75%; }
.bf__skel-line:nth-child(3) { width: 55%; }
.bf__skel-line:nth-child(4) { width: 85%; height: 22px; }
.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* Empty */
.bf__price-empty {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 24px 0; color: #9ca3af; text-align: center;
}
.bf__price-empty-icon { font-size: 2rem; }
.bf__price-empty p    { font-size: 13px; }

/* Availability pill */
.bf__avail-pill {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 12px; border-radius: 8px;
  font-size: 12.5px; font-weight: 700; margin-bottom: 16px;
}
.bf__avail-pill--ok { background: #d1fae5; color: #065f46; }
.bf__avail-pill--no { background: #fee2e2; color: #991b1b; }

/* Price rows */
.bf__price-rows { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
.bf__price-row {
  display: flex; justify-content: space-between;
  font-size: 13.5px; color: #374151;
}
.bf__price-row--discount { color: #10b981; font-weight: 600; }
.bf__price-row--taxes    { color: #9ca3af; font-size: 12px; }

.bf__price-divider { border: none; border-top: 1.5px solid #f0f0f0; margin: 4px 0 14px; }

.bf__price-total {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 12px;
}
.bf__price-total span:first-child { font-size: 14px; font-weight: 700; color: #1a202c; }
.bf__price-total-amount { font-size: 22px; font-weight: 900; color: #4f46e5; }

.bf__price-note {
  font-size: 11.5px; color: #10b981; font-weight: 600;
  padding: 8px 10px; background: #f0fdf4; border-radius: 7px;
  margin-bottom: 16px;
}

/* Rate card */
.bf__rate-card {
  background: #f9fafb; border-radius: 10px; padding: 14px;
  display: flex; flex-direction: column; gap: 8px;
  border: 1px solid #f0f0f0;
}
.bf__rate-row { display: flex; justify-content: space-between; font-size: 13px; }
.bf__rate-label { color: #6b7280; }
.bf__rate-value { font-weight: 700; color: #1a202c; }

/* Step transitions */
.step-slide-enter-active, .step-slide-leave-active { transition: opacity 0.2s, transform 0.2s; }
.step-slide-enter-from { opacity: 0; transform: translateX(16px); }
.step-slide-leave-to   { opacity: 0; transform: translateX(-16px); }

/* Fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Responsive */
@media (max-width: 720px) {
  .bf { grid-template-columns: 1fr; }
  .bf__price-panel { position: static; order: -1; }
  .bf__grid { grid-template-columns: 1fr; }
}
</style>
