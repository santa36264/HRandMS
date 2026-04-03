<template>
  <div class="otp-wrapper">
    <div class="otp-card">

      <!-- Header -->
      <div class="otp-header">
        <div class="otp-icon">✉️</div>
        <h2>Verify Your Email</h2>
        <p>We sent a 6-digit code to <strong>{{ email }}</strong></p>
      </div>

      <!-- OTP Inputs -->
      <div class="otp-inputs" @paste.prevent="onPaste">
        <input
          v-for="(_, i) in digits"
          :key="i"
          :ref="el => { if (el) inputRefs[i] = el }"
          v-model="digits[i]"
          type="text"
          inputmode="numeric"
          maxlength="1"
          class="otp-input"
          :class="{
            'otp-input--filled': digits[i],
            'otp-input--error': hasError,
          }"
          :aria-label="`Digit ${i + 1}`"
          autocomplete="one-time-code"
          @keydown="onKeydown($event, i)"
          @input="onInput($event, i)"
          @focus="onFocus(i)"
        />
      </div>

      <!-- Error message -->
      <p v-if="errorMessage" class="otp-error" role="alert">{{ errorMessage }}</p>

      <!-- Countdown timer -->
      <div class="otp-timer">
        <template v-if="secondsLeft > 0">
          <span class="timer-text">Code expires in</span>
          <span class="timer-count" :class="{ 'timer-count--urgent': secondsLeft <= 30 }">
            {{ formattedTime }}
          </span>
        </template>
        <span v-else class="timer-expired">Code expired</span>
      </div>

      <!-- Submit -->
      <button
        class="otp-btn otp-btn--primary"
        :disabled="!isComplete || loading || secondsLeft === 0"
        @click="submitOtp"
      >
        <span v-if="loading" class="spinner" aria-hidden="true" />
        {{ loading ? 'Verifying...' : 'Verify Email' }}
      </button>

      <!-- Resend -->
      <div class="otp-resend">
        <span>Didn't receive the code?</span>
        <button
          class="otp-btn otp-btn--link"
          :disabled="resendCooldown > 0 || resendLoading"
          @click="resendOtp"
        >
          <template v-if="resendCooldown > 0">Resend in {{ resendCooldown }}s</template>
          <template v-else-if="resendLoading">Sending...</template>
          <template v-else>Resend Code</template>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { authService } from '../../services/auth'

// ── Props & Emits ──────────────────────────────────
const props = defineProps({
  email: { type: String, required: true },
})
const emit = defineEmits(['verified', 'resent'])

// ── State ──────────────────────────────────────────
const digits       = ref(Array(6).fill(''))
const inputRefs    = ref([])
const loading      = ref(false)
const resendLoading = ref(false)
const errorMessage = ref('')
const hasError     = ref(false)
const secondsLeft  = ref(120) // 2 minutes
const resendCooldown = ref(0)

let countdownTimer = null
let resendTimer    = null

// ── Computed ───────────────────────────────────────
const isComplete = computed(() => digits.value.every(d => d !== ''))

const otpValue = computed(() => digits.value.join(''))

const formattedTime = computed(() => {
  const m = Math.floor(secondsLeft.value / 60)
  const s = secondsLeft.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

// ── Lifecycle ──────────────────────────────────────
onMounted(() => {
  focusInput(0)
  startCountdown()
})

onUnmounted(() => {
  clearInterval(countdownTimer)
  clearInterval(resendTimer)
})

// Clear error when user starts typing again
watch(digits, () => {
  if (hasError.value) {
    hasError.value = false
    errorMessage.value = ''
  }
}, { deep: true })

// ── Countdown ──────────────────────────────────────
function startCountdown() {
  clearInterval(countdownTimer)
  secondsLeft.value = 120
  countdownTimer = setInterval(() => {
    if (secondsLeft.value > 0) secondsLeft.value--
    else clearInterval(countdownTimer)
  }, 1000)
}

function startResendCooldown(seconds = 60) {
  resendCooldown.value = seconds
  clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    if (resendCooldown.value > 0) resendCooldown.value--
    else clearInterval(resendTimer)
  }, 1000)
}

// ── Input Handlers ─────────────────────────────────
function onInput(event, index) {
  const val = event.target.value.replace(/\D/g, '') // digits only
  digits.value[index] = val.slice(-1)               // keep last char
  if (digits.value[index] && index < 5) focusInput(index + 1)
}

function onKeydown(event, index) {
  if (event.key === 'Backspace') {
    if (digits.value[index]) {
      digits.value[index] = ''
    } else if (index > 0) {
      digits.value[index - 1] = ''
      focusInput(index - 1)
    }
    event.preventDefault()
  } else if (event.key === 'ArrowLeft' && index > 0) {
    focusInput(index - 1)
  } else if (event.key === 'ArrowRight' && index < 5) {
    focusInput(index + 1)
  } else if (event.key === 'Enter' && isComplete.value) {
    submitOtp()
  }
}

function onFocus(index) {
  // Select content on focus for easy replacement
  nextTick(() => inputRefs.value[index]?.select())
}

function onPaste(event) {
  const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  if (!pasted) return
  pasted.split('').forEach((char, i) => { digits.value[i] = char })
  focusInput(Math.min(pasted.length, 5))
}

function focusInput(index) {
  nextTick(() => inputRefs.value[index]?.focus())
}

function resetInputs() {
  digits.value = Array(6).fill('')
  focusInput(0)
}

// ── API Calls ──────────────────────────────────────
async function submitOtp() {
  if (!isComplete.value || loading.value) return
  loading.value = true
  errorMessage.value = ''
  hasError.value = false

  try {
    const { data } = await authService.verifyOtp(otpValue.value)
    emit('verified', data.data)
  } catch (err) {
    hasError.value = true
    errorMessage.value = err.response?.data?.errors?.otp?.[0]
      ?? err.response?.data?.message
      ?? 'Verification failed. Please try again.'
    resetInputs()
  } finally {
    loading.value = false
  }
}

async function resendOtp() {
  if (resendCooldown.value > 0 || resendLoading.value) return
  resendLoading.value = true
  errorMessage.value = ''

  try {
    await authService.sendOtp()
    startCountdown()
    startResendCooldown(60)
    emit('resent')
  } catch (err) {
    errorMessage.value = err.response?.data?.errors?.otp?.[0]
      ?? err.response?.data?.message
      ?? 'Failed to resend code.'
  } finally {
    resendLoading.value = false
  }
}
</script>

<style scoped>
.otp-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: #f5f6fa;
  padding: 1rem;
}

.otp-card {
  background: #fff;
  border-radius: 16px;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}

/* Header */
.otp-header { text-align: center; }
.otp-icon   { font-size: 2.5rem; margin-bottom: 0.5rem; }
.otp-header h2 { margin: 0 0 0.4rem; font-size: 1.4rem; color: #1a1a2e; }
.otp-header p  { margin: 0; color: #6b7280; font-size: 0.9rem; }
.otp-header strong { color: #1a1a2e; }

/* Inputs */
.otp-inputs {
  display: flex;
  gap: 0.6rem;
}

.otp-input {
  width: 48px;
  height: 56px;
  text-align: center;
  font-size: 1.4rem;
  font-weight: 600;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  color: #1a1a2e;
  background: #fafafa;
  caret-color: transparent;
}

.otp-input:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
  background: #fff;
}

.otp-input--filled  { border-color: #4f46e5; background: #fff; }
.otp-input--error   { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important; }

/* Error */
.otp-error {
  color: #ef4444;
  font-size: 0.85rem;
  text-align: center;
  margin: 0;
}

/* Timer */
.otp-timer {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.timer-count {
  font-weight: 700;
  color: #4f46e5;
  font-variant-numeric: tabular-nums;
  min-width: 2.5rem;
}

.timer-count--urgent { color: #ef4444; }
.timer-expired       { color: #ef4444; font-weight: 600; }

/* Buttons */
.otp-btn {
  cursor: pointer;
  border: none;
  font-size: 0.95rem;
  font-weight: 600;
  transition: opacity 0.2s, background 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.otp-btn--primary {
  width: 100%;
  padding: 0.85rem;
  background: #4f46e5;
  color: #fff;
  border-radius: 10px;
  justify-content: center;
}

.otp-btn--primary:hover:not(:disabled) { background: #4338ca; }
.otp-btn--primary:disabled { opacity: 0.5; cursor: not-allowed; }

.otp-btn--link {
  background: none;
  color: #4f46e5;
  padding: 0;
  text-decoration: underline;
  font-size: 0.875rem;
}

.otp-btn--link:disabled { color: #9ca3af; cursor: not-allowed; text-decoration: none; }

/* Resend */
.otp-resend {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Spinner */
.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Mobile */
@media (max-width: 400px) {
  .otp-input { width: 40px; height: 48px; font-size: 1.2rem; }
  .otp-inputs { gap: 0.4rem; }
}
</style>
