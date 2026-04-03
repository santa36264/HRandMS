import { defineStore } from 'pinia'
import { ref } from 'vue'

let _id = 0

/**
 * Global toast store.
 * Use the `useToast()` composable in components — don't call this directly.
 */
export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  /**
   * @param {'success'|'error'|'warning'|'info'} type
   * @param {string} message
   * @param {number} duration  ms before auto-dismiss (0 = sticky)
   */
  function add(type, message, duration = 4000) {
    const id = ++_id
    toasts.value.push({ id, type, message, duration })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }

    return id
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  function clear() {
    toasts.value = []
  }

  return { toasts, add, remove, clear }
})
