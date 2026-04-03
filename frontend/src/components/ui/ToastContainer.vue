<template>
  <teleport to="body">
    <div class="toast-stack" role="region" aria-label="Notifications" aria-live="polite">
      <transition-group name="toast" tag="div" class="toast-stack__inner">
        <div
          v-for="toast in store.toasts"
          :key="toast.id"
          class="toast"
          :class="`toast--${toast.type}`"
          role="alert"
        >
          <span class="toast__icon">{{ ICONS[toast.type] }}</span>
          <span class="toast__msg">{{ toast.message }}</span>
          <button class="toast__close" @click="store.remove(toast.id)" aria-label="Dismiss">✕</button>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
import { useToastStore } from '../../stores/toast'

const store = useToastStore()

const ICONS = {
  success: '✓',
  error:   '✕',
  warning: '⚠',
  info:    'ℹ',
}
</script>

<style scoped>
.toast-stack {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  pointer-events: none;
}
.toast-stack__inner {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-end;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  min-width: 280px;
  max-width: 420px;
  padding: 13px 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.5;
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);
  pointer-events: all;
  word-break: break-word;
}

.toast--success { background: #f0fdf4; border: 1.5px solid #86efac; color: #166534; }
.toast--error   { background: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b; }
.toast--warning { background: #fffbeb; border: 1.5px solid #fcd34d; color: #92400e; }
.toast--info    { background: #eff6ff; border: 1.5px solid #93c5fd; color: #1e40af; }

.toast__icon {
  font-size: 15px;
  font-weight: 800;
  flex-shrink: 0;
  margin-top: 1px;
}
.toast__msg  { flex: 1; }
.toast__close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 13px;
  opacity: 0.5;
  padding: 0;
  flex-shrink: 0;
  color: inherit;
  line-height: 1;
}
.toast__close:hover { opacity: 1; }

/* Transitions */
.toast-enter-active { transition: all 0.25s ease; }
.toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(40px); }
.toast-leave-to     { opacity: 0; transform: translateX(40px); }
.toast-move         { transition: transform 0.2s ease; }

@media (max-width: 480px) {
  .toast-stack { top: auto; bottom: 16px; right: 12px; left: 12px; }
  .toast { min-width: unset; max-width: 100%; }
}
</style>
