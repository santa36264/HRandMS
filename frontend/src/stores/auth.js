import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '../services/auth'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(JSON.parse(localStorage.getItem('user') ?? 'null'))
  const token = ref(localStorage.getItem('token') ?? null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isGuest         = computed(() => user.value?.role === 'guest')

  function setAuth(userData, tokenValue) {
    user.value  = userData
    token.value = tokenValue
    localStorage.setItem('user',  JSON.stringify(userData))
    localStorage.setItem('token', tokenValue)
  }

  function clearAuth() {
    user.value  = null
    token.value = null
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }

  async function fetchMe() {
    try {
      const { data } = await authService.me()
      user.value = data.data
      localStorage.setItem('user', JSON.stringify(data.data))
      return true
    } catch {
      clearAuth()
      return false
    }
  }

  async function logout() {
    try { await authService.logout() } catch { /* ignore */ }
    clearAuth()
  }

  return { user, token, isAuthenticated, isAdmin, isGuest, setAuth, clearAuth, fetchMe, logout }
})
