<template>
  <teleport to="body">
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="modal">
        <div class="modal__header">
          <h3 class="modal__title">Update Booking Status</h3>
          <button class="modal__close" @click="$emit('close')">✕</button>
        </div>

        <div class="modal__body">
          <p class="modal__ref">{{ booking?.booking_reference }}</p>

          <div class="modal__field">
            <label>New Status</label>
            <select v-model="selectedStatus">
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>

          <div v-if="selectedStatus === 'cancelled'" class="modal__field">
            <label>Cancellation Reason</label>
            <textarea v-model="reason" rows="3" placeholder="Reason for cancellation…" />
          </div>
        </div>

        <div class="modal__footer">
          <button class="modal__btn modal__btn--cancel" @click="$emit('close')">Cancel</button>
          <button class="modal__btn modal__btn--confirm" :disabled="loading" @click="submit">
            {{ loading ? 'Saving…' : 'Update Status' }}
          </button>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  booking: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'confirm'])

const statuses = [
  { value: 'pending',     label: 'Pending' },
  { value: 'confirmed',   label: 'Confirmed' },
  { value: 'checked_in',  label: 'Checked In' },
  { value: 'checked_out', label: 'Checked Out' },
  { value: 'cancelled',   label: 'Cancelled' },
]

const selectedStatus = ref('')
const reason         = ref('')

watch(() => props.booking, (b) => {
  selectedStatus.value = b?.status ?? 'pending'
  reason.value = ''
}, { immediate: true })

function submit() {
  emit('confirm', { status: selectedStatus.value, cancellation_reason: reason.value })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal {
  background: #fff; border-radius: 14px; width: 100%; max-width: 440px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;
}
.modal__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 22px; border-bottom: 1px solid #f0f0f0;
}
.modal__title { font-size: 15px; font-weight: 700; color: #1a202c; }
.modal__close {
  background: none; border: none; font-size: 16px; color: #9ca3af;
  cursor: pointer; padding: 4px; border-radius: 6px; transition: background 0.15s;
}
.modal__close:hover { background: #f3f4f6; color: #374151; }

.modal__body { padding: 20px 22px; display: flex; flex-direction: column; gap: 16px; }
.modal__ref  { font-size: 13px; color: #6b7280; font-weight: 600; }

.modal__field { display: flex; flex-direction: column; gap: 6px; }
.modal__field label { font-size: 13px; font-weight: 600; color: #374151; }
.modal__field select,
.modal__field textarea {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; outline: none; transition: border-color 0.15s;
  font-family: inherit; resize: vertical;
}
.modal__field select:focus,
.modal__field textarea:focus { border-color: #4f46e5; }

.modal__footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 16px 22px; border-top: 1px solid #f0f0f0;
}
.modal__btn {
  padding: 9px 20px; border-radius: 8px; font-size: 14px;
  font-weight: 600; cursor: pointer; border: none; transition: background 0.15s;
}
.modal__btn--cancel  { background: #f3f4f6; color: #374151; }
.modal__btn--cancel:hover { background: #e5e7eb; }
.modal__btn--confirm { background: #4f46e5; color: #fff; }
.modal__btn--confirm:hover:not(:disabled) { background: #4338ca; }
.modal__btn--confirm:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
