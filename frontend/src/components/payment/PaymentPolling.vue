<template>
  <div class="polling">
    <div class="polling__animation">
      <div class="polling__ring polling__ring--1" />
      <div class="polling__ring polling__ring--2" />
      <div class="polling__ring polling__ring--3" />
      <div class="polling__icon">{{ gatewayIcon }}</div>
    </div>

    <h3 class="polling__title">Waiting for payment confirmation</h3>
    <p class="polling__sub">
      Complete the payment in the {{ gatewayLabel }} window that opened.<br />
      This page will update automatically.
    </p>

    <!-- Progress bar -->
    <div class="polling__progress-wrap">
      <div class="polling__progress-bar" :style="{ width: progressPct + '%' }" />
    </div>
    <p class="polling__timer">Checking… {{ pollCount }}/{{ maxPolls }}</p>

    <div class="polling__actions">
      <a
        v-if="paymentUrl"
        :href="paymentUrl"
        target="_blank"
        rel="noopener"
        class="polling__link"
      >
        Reopen payment page ↗
      </a>
      <button class="polling__cancel" @click="$emit('cancel')">Cancel</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  gateway:    { type: Object,  required: true },
  paymentUrl: { type: String,  default: '' },
  pollCount:  { type: Number,  default: 0 },
  maxPolls:   { type: Number,  default: 24 },
})
defineEmits(['cancel'])

const gatewayIcon  = computed(() => '💳')
const gatewayLabel = computed(() => props.gateway?.label ?? 'payment')
const progressPct  = computed(() => Math.min((props.pollCount / props.maxPolls) * 100, 100))
</script>

<style scoped>
.polling { text-align: center; padding: 32px 16px; }

/* Pulsing rings */
.polling__animation {
  position: relative; width: 96px; height: 96px;
  margin: 0 auto 24px;
}
.polling__ring {
  position: absolute; inset: 0; border-radius: 50%;
  border: 3px solid #F5A623; opacity: 0;
  animation: ripple 2s ease-out infinite;
}
.polling__ring--2 { animation-delay: 0.6s; }
.polling__ring--3 { animation-delay: 1.2s; }
@keyframes ripple {
  0%   { transform: scale(0.6); opacity: 0.8; }
  100% { transform: scale(1.8); opacity: 0; }
}
.polling__icon {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.2rem;
  background: #fff8ec; border-radius: 50%;
  border: 2px solid #fde68a;
}

.polling__title { font-size: 1.1rem; font-weight: 700; color: #1a202c; margin-bottom: 8px; }
.polling__sub   { font-size: 13.5px; color: #6b7280; line-height: 1.6; margin-bottom: 20px; }

/* Progress */
.polling__progress-wrap {
  height: 4px; background: #e5e7eb; border-radius: 99px;
  overflow: hidden; margin-bottom: 6px;
}
.polling__progress-bar {
  height: 100%; background: #4f46e5; border-radius: 99px;
  transition: width 0.5s ease;
}
.polling__timer { font-size: 12px; color: #9ca3af; margin-bottom: 20px; }

.polling__actions { display: flex; flex-direction: column; align-items: center; gap: 10px; }
.polling__link {
  font-size: 13px; color: #4f46e5; font-weight: 600;
  text-decoration: underline; cursor: pointer;
}
.polling__cancel {
  font-size: 13px; color: #9ca3af; background: none;
  border: none; cursor: pointer; text-decoration: underline;
}
.polling__cancel:hover { color: #ef4444; }
</style>
