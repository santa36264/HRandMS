<template>
  <div class="review-form">

    <!-- Header -->
    <div class="review-form__header">
      <div class="review-form__room-info">
        <img
          :src="booking.room?.images?.[0] ?? FALLBACK"
          :alt="booking.room?.name"
          class="review-form__room-img"
          @error="e => e.target.src = FALLBACK"
        />
        <div>
          <p class="review-form__room-label">Reviewing your stay at</p>
          <h3 class="review-form__room-name">{{ booking.room?.name }}</h3>
          <p class="review-form__stay-dates">
            {{ formatDate(booking.check_in_date) }} → {{ formatDate(booking.check_out_date) }}
            · {{ booking.nights }} night{{ booking.nights > 1 ? 's' : '' }}
          </p>
        </div>
      </div>
    </div>

    <div class="review-form__divider" />

    <!-- Overall rating — hero section -->
    <div class="review-form__overall">
      <p class="review-form__section-label">Overall Rating <span class="review-form__required">*</span></p>
      <StarRating
        v-model="form.rating"
        size="lg"
        show-label
        label="Overall rating"
      />
      <p v-if="errors.rating" class="review-form__field-error">{{ errors.rating }}</p>
    </div>

    <!-- Sub-ratings -->
    <div class="review-form__sub-ratings">
      <div
        v-for="sub in subRatings"
        :key="sub.key"
        class="review-form__sub-row"
      >
        <div class="review-form__sub-info">
          <span class="review-form__sub-icon">{{ sub.icon }}</span>
          <span class="review-form__sub-label">{{ sub.label }}</span>
        </div>
        <StarRating
          v-model="form[sub.key]"
          size="sm"
          :label="sub.label"
        />
        <span class="review-form__sub-score">
          {{ form[sub.key] ? subScoreLabel(form[sub.key]) : '—' }}
        </span>
      </div>
    </div>

    <div class="review-form__divider" />

    <!-- Title -->
    <div class="review-form__field">
      <label class="review-form__label" for="rv-title">
        Review Title
        <span class="review-form__optional">optional</span>
      </label>
      <input
        id="rv-title"
        v-model="form.title"
        type="text"
        class="review-form__input"
        :class="{ 'review-form__input--error': errors.title }"
        placeholder="Summarise your experience in a few words"
        maxlength="120"
      />
      <div class="review-form__input-footer">
        <span v-if="errors.title" class="review-form__field-error">{{ errors.title }}</span>
        <span class="review-form__char-count" :class="{ 'review-form__char-count--warn': form.title.length > 100 }">
          {{ form.title.length }}/120
        </span>
      </div>
    </div>

    <!-- Comment -->
    <div class="review-form__field">
      <label class="review-form__label" for="rv-comment">
        Your Review <span class="review-form__optional">optional</span>
      </label>
      <textarea
        id="rv-comment"
        v-model="form.comment"
        class="review-form__input review-form__textarea"
        :class="{ 'review-form__input--error': errors.comment }"
        placeholder="Tell future guests about your experience — what did you love? What could be improved?"
        rows="5"
        maxlength="1000"
      />
      <div class="review-form__input-footer">
        <span v-if="errors.comment" class="review-form__field-error">{{ errors.comment }}</span>
        <span
          class="review-form__char-count"
          :class="{ 'review-form__char-count--warn': form.comment.length > 900 }"
        >{{ form.comment.length }}/1000</span>
      </div>
    </div>

    <!-- Rating summary preview -->
    <transition name="fade">
      <div v-if="form.rating > 0" class="review-form__preview">
        <div class="review-form__preview-stars">
          <StarRating :model-value="form.rating" size="sm" readonly />
          <span class="review-form__preview-score">{{ overallLabel }}</span>
        </div>
        <p v-if="form.title" class="review-form__preview-title">"{{ form.title }}"</p>
        <div v-if="hasSubRatings" class="review-form__preview-subs">
          <span v-for="sub in filledSubRatings" :key="sub.key" class="review-form__preview-sub">
            {{ sub.icon }} {{ sub.label }}: {{ form[sub.key] }}/5
          </span>
        </div>
      </div>
    </transition>

    <!-- Disclaimer -->
    <p class="review-form__disclaimer">
      ℹ️ Reviews are moderated and will appear publicly after approval.
    </p>

    <!-- Error banner -->
    <div v-if="submitError" class="review-form__error-banner">⚠️ {{ submitError }}</div>

    <!-- Success state -->
    <transition name="fade">
      <div v-if="submitted" class="review-form__success">
        <span class="review-form__success-icon">🎉</span>
        <h4>Thank you for your review!</h4>
        <p>It will appear after our team approves it.</p>
        <button class="review-form__success-btn" @click="$emit('close')">Done</button>
      </div>
    </transition>

    <!-- Actions -->
    <div v-if="!submitted" class="review-form__actions">
      <button
        v-if="showCancel"
        type="button"
        class="review-form__btn review-form__btn--ghost"
        @click="$emit('cancel')"
      >Cancel</button>
      <button
        type="button"
        class="review-form__btn review-form__btn--submit"
        :disabled="!canSubmit"
        @click="submit"
      >
        <span v-if="submitting" class="review-form__spinner" />
        {{ submitting ? 'Submitting…' : (isEdit ? 'Update Review' : 'Submit Review') }}
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import StarRating from './StarRating.vue'
import { reviewsService } from '../../services/reviews'

const FALLBACK = 'https://placehold.co/64x64/e0e7ff/4f46e5?text=Room'

const props = defineProps({
  booking:    { type: Object, required: true },
  existing:   { type: Object, default: null },   // pass to edit an existing review
  showCancel: { type: Boolean, default: true },
})
const emit = defineEmits(['submitted', 'cancel', 'close'])

const isEdit = computed(() => !!props.existing)

// ── Form ───────────────────────────────────────────────────────────
const form = reactive({
  booking_id:          props.booking.id,
  rating:              props.existing?.rating              ?? 0,
  cleanliness_rating:  props.existing?.cleanliness_rating  ?? 0,
  service_rating:      props.existing?.service_rating      ?? 0,
  location_rating:     props.existing?.location_rating     ?? 0,
  title:               props.existing?.title               ?? '',
  comment:             props.existing?.comment             ?? '',
})

const errors      = reactive({})
const submitError = ref('')
const submitting  = ref(false)
const submitted   = ref(false)

// ── Sub-ratings config ─────────────────────────────────────────────
const subRatings = [
  { key: 'cleanliness_rating', icon: '🧹', label: 'Cleanliness' },
  { key: 'service_rating',     icon: '🛎️', label: 'Service' },
  { key: 'location_rating',    icon: '📍', label: 'Location' },
]

const filledSubRatings = computed(() =>
  subRatings.filter(s => form[s.key] > 0)
)
const hasSubRatings = computed(() => filledSubRatings.value.length > 0)

// ── Computed ───────────────────────────────────────────────────────
const LABELS = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent']
const overallLabel  = computed(() => form.rating ? LABELS[form.rating - 1] : '')
const subScoreLabel = (n) => LABELS[n - 1] ?? ''

const canSubmit = computed(() =>
  form.rating > 0 &&
  (form.comment.trim().length === 0 || form.comment.trim().length >= 10) &&
  !submitting.value
)

// ── Submit ─────────────────────────────────────────────────────────
async function submit() {
  if (!canSubmit.value) return
  submitting.value  = true
  submitError.value = ''
  Object.keys(errors).forEach(k => delete errors[k])

  const payload = {
    ...form,
    cleanliness_rating: form.cleanliness_rating || undefined,
    service_rating:     form.service_rating     || undefined,
    location_rating:    form.location_rating    || undefined,
    title:              form.title.trim()       || undefined,
    comment:            form.comment.trim()     || undefined,
  }

  try {
    let data
    if (isEdit.value) {
      ;({ data } = await reviewsService.update(props.existing.id, payload))
    } else {
      ;({ data } = await reviewsService.store(payload))
    }
    submitted.value = true
    emit('submitted', data.data)
  } catch (e) {
    const errs = e.response?.data?.errors ?? {}
    Object.assign(errors, errs)
    submitError.value = e.response?.data?.message ?? 'Submission failed. Please try again.'
  } finally {
    submitting.value = false
  }
}

// ── Helpers ────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<style scoped>
/* ── Shell ───────────────────────────────────────────────────────── */
.review-form {
  background: #fff;
  border-radius: 16px;
  padding: 28px;
  max-width: 560px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── Header ──────────────────────────────────────────────────────── */
.review-form__header { }
.review-form__room-info {
  display: flex; gap: 14px; align-items: center;
}
.review-form__room-img {
  width: 64px; height: 64px; border-radius: 10px;
  object-fit: cover; flex-shrink: 0;
}
.review-form__room-label { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
.review-form__room-name  { font-size: 16px; font-weight: 800; color: #1a202c; margin-bottom: 3px; }
.review-form__stay-dates { font-size: 12px; color: #6b7280; }

.review-form__divider { border: none; border-top: 1px solid #f3f4f6; }

/* ── Overall rating ──────────────────────────────────────────────── */
.review-form__overall {
  display: flex; flex-direction: column; gap: 10px; align-items: flex-start;
}
.review-form__section-label {
  font-size: 12px; font-weight: 700; color: #6b7280;
  text-transform: uppercase; letter-spacing: 0.5px;
}
.review-form__required { color: #ef4444; }
.review-form__optional {
  font-size: 11px; font-weight: 400; color: #9ca3af;
  text-transform: none; letter-spacing: 0; margin-left: 4px;
}

/* ── Sub-ratings ─────────────────────────────────────────────────── */
.review-form__sub-ratings {
  display: flex; flex-direction: column; gap: 10px;
  background: #f9fafb; border-radius: 10px; padding: 14px 16px;
}
.review-form__sub-row {
  display: flex; align-items: center; gap: 12px;
}
.review-form__sub-info {
  display: flex; align-items: center; gap: 6px;
  width: 120px; flex-shrink: 0;
}
.review-form__sub-icon  { font-size: 15px; }
.review-form__sub-label { font-size: 13px; font-weight: 600; color: #374151; }
.review-form__sub-score {
  margin-left: auto; font-size: 12px; font-weight: 700;
  color: #f59e0b; min-width: 60px; text-align: right;
}

/* ── Fields ──────────────────────────────────────────────────────── */
.review-form__field { display: flex; flex-direction: column; gap: 6px; }
.review-form__label {
  font-size: 12px; font-weight: 700; color: #6b7280;
  text-transform: uppercase; letter-spacing: 0.4px;
  display: flex; align-items: center; gap: 4px;
}
.review-form__input {
  padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 9px;
  font-size: 14px; color: #1a202c; background: #fafafa;
  outline: none; transition: border-color 0.2s; font-family: inherit;
}
.review-form__input:focus { border-color: #4f46e5; background: #fff; }
.review-form__input--error { border-color: #ef4444; }
.review-form__textarea { resize: vertical; min-height: 110px; line-height: 1.6; }

.review-form__input-footer {
  display: flex; justify-content: space-between; align-items: center;
}
.review-form__field-error { font-size: 11.5px; color: #ef4444; }
.review-form__char-count  { font-size: 11px; color: #9ca3af; margin-left: auto; }
.review-form__char-count--warn { color: #f59e0b; font-weight: 700; }
.review-form__char-count--ok   { color: #10b981; }

/* ── Preview card ────────────────────────────────────────────────── */
.review-form__preview {
  background: linear-gradient(135deg, #fefce8, #fffbeb);
  border: 1.5px solid #fde68a; border-radius: 10px;
  padding: 14px 16px; display: flex; flex-direction: column; gap: 6px;
}
.review-form__preview-stars {
  display: flex; align-items: center; gap: 8px;
}
.review-form__preview-score {
  font-size: 13px; font-weight: 800; color: #92400e;
}
.review-form__preview-title {
  font-size: 13.5px; font-style: italic; color: #374151; margin: 0;
}
.review-form__preview-subs {
  display: flex; flex-wrap: wrap; gap: 8px; margin-top: 2px;
}
.review-form__preview-sub {
  font-size: 11.5px; color: #6b7280; font-weight: 600;
  background: #fff; padding: 2px 8px; border-radius: 6px;
  border: 1px solid #fde68a;
}

/* ── Disclaimer ──────────────────────────────────────────────────── */
.review-form__disclaimer {
  font-size: 12px; color: #9ca3af; line-height: 1.5;
}

/* ── Error banner ────────────────────────────────────────────────── */
.review-form__error-banner {
  padding: 10px 14px; background: #fee2e2; border-radius: 8px;
  color: #991b1b; font-size: 13px;
}

/* ── Success state ───────────────────────────────────────────────── */
.review-form__success {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  padding: 24px 0; text-align: center;
}
.review-form__success-icon { font-size: 3rem; }
.review-form__success h4   { font-size: 17px; font-weight: 800; color: #1a202c; }
.review-form__success p    { font-size: 13.5px; color: #6b7280; }
.review-form__success-btn {
  padding: 10px 28px; background: #4f46e5; color: #fff;
  border: none; border-radius: 9px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: background 0.15s; margin-top: 4px;
}
.review-form__success-btn:hover { background: #4338ca; }

/* ── Actions ─────────────────────────────────────────────────────── */
.review-form__actions {
  display: flex; gap: 10px; justify-content: flex-end;
  padding-top: 4px;
}
.review-form__btn {
  padding: 11px 22px; border-radius: 9px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  border: none; transition: background 0.15s, opacity 0.15s;
  display: flex; align-items: center; gap: 8px;
}
.review-form__btn--ghost  { background: #f3f4f6; color: #374151; }
.review-form__btn--ghost:hover { background: #e5e7eb; }
.review-form__btn--submit { background: #4f46e5; color: #fff; }
.review-form__btn--submit:hover:not(:disabled) { background: #4338ca; }
.review-form__btn--submit:disabled { opacity: 0.45; cursor: not-allowed; }

/* Spinner */
.review-form__spinner {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff;
  animation: spin 0.7s linear infinite; flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
