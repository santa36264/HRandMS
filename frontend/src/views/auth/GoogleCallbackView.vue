<template>
  <div class="google-cb">
    <div v-if="error" class="google-cb__error">
      <span>⚠️</span>
      <p>{{ error }}</p>
      <router-link to="/auth/login">Back to Login</router-link>
    </div>
    <div v-else class="google-cb__loading">
      <div class="google-cb__spinner"></div>
      <p>Signing you in…</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()
const error  = ref('')

onMounted(() => {
  const token = route.query.token
  const userRaw = route.query.user

  if (!token || !userRaw) {
    const errParam = route.query.error
    error.value = errParam === 'google_failed'
      ? 'Google sign-in failed. Please try again.'
      : 'Invalid callback. Please try again.'
    return
  }

  try {
    const user = JSON.parse(decodeURIComponent(userRaw))
    auth.setAuth(user, token)
    router.replace(auth.isAdmin ? '/admin' : '/')
  } catch {
    error.value = 'Could not process sign-in. Please try again.'
  }
})
</script>

<style scoped>
.google-cb {
  min-height: 100vh; display: flex;
  align-items: center; justify-content: center;
  background: #f8f9fc;
}
.google-cb__loading {
  display: flex; flex-direction: column; align-items: center; gap: 16px;
  color: #6b7280; font-size: 15px;
}
.google-cb__spinner {
  width: 40px; height: 40px; border-radius: 50%;
  border: 3px solid #e5e7eb; border-top-color: #4f46e5;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.google-cb__error {
  display: flex; flex-direction: column; align-items: center; gap: 12px;
  text-align: center; color: #374151;
}
.google-cb__error span { font-size: 2.5rem; }
.google-cb__error a {
  color: #4f46e5; font-weight: 600; text-decoration: none;
}
</style>
