import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const theme = ref(localStorage.getItem('theme') ?? 'light')

  function apply(t) {
    document.documentElement.setAttribute('data-theme', t)
    localStorage.setItem('theme', t)
  }

  function toggle() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
    apply(theme.value)
  }

  function init() {
    apply(theme.value)
  }

  return { theme, toggle, init }
})
