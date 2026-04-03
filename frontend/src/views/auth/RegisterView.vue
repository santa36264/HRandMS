<template>
  <form class="auth-form" @submit.prevent="submit">
    <h2 class="auth-form__title">Create Account</h2>

    <!-- Google button -->
    <a class="auth-form__google" href="#" @click.prevent="loginWithGoogle">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" width="20" height="20" />
      Continue with Google
    </a>

    <div class="auth-form__divider"><span>or</span></div>

    <div class="auth-form__field">
      <label>Full Name</label>
      <input v-model="form.name" type="text" required placeholder="John Doe" />
    </div>
    <div class="auth-form__field">
      <label>Email</label>
      <input v-model="form.email" type="email" required placeholder="you@example.com" />
    </div>
    <div class="auth-form__field">
      <label>Password</label>
      <input v-model="form.password" type="password" required placeholder="Min 8 characters" />
    </div>
    <div class="auth-form__field">
      <label>Confirm Password</label>
      <input v-model="form.password_confirmation" type="password" required placeholder="Repeat password" />
    </div>

    <p v-if="error" class="auth-form__error">{{ error }}</p>

    <button type="submit" class="auth-form__btn" :disabled="loading">
      {{ loading ? 'Creating account…' : 'Register' }}
    </button>

    <p class="auth-form__footer">
      Already have an account? <router-link to="/auth/login">Sign in</router-link>
    </p>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { authService } from '../../services/auth'

const auth    = useAuthStore()
const router  = useRouter()
const loading = ref(false)
const error   = ref('')
const form    = ref({ name: '', email: '', password: '', password_confirmation: '' })

async function loginWithGoogle() {
  try {
    const res = await fetch('/api/auth/google/redirect', {
      headers: { Accept: 'application/json' }
    })
    const json = await res.json()
    window.location.href = json.data.url
  } catch {
    error.value = 'Could not connect to Google. Please try again.'
  }
}

async function submit() {
  loading.value = true
  error.value   = ''
  try {
    const { data } = await authService.register(form.value)
    auth.setAuth(data.data.user, data.data.token)
    router.push('/verify-email')
  } catch (e) {
    const errs = e.response?.data?.errors
    error.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Registration failed.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-form__title  { font-size: 1.4rem; font-weight: 800; color: var(--text); margin-bottom: 20px; text-align: center; }

.auth-form__google {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; padding: 11px 16px;
  border: 1.5px solid var(--border); border-radius: 8px;
  background: var(--bg-card); color: var(--text-soft);
  font-size: 14px; font-weight: 600; text-decoration: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  margin-bottom: 16px;
}
.auth-form__google:hover {
  border-color: #4285f4;
  box-shadow: 0 0 0 3px rgba(66,133,244,0.12);
}

.auth-form__divider {
  display: flex; align-items: center; gap: 10px;
  color: var(--text-faint); font-size: 12px; margin-bottom: 16px;
}
.auth-form__divider::before,
.auth-form__divider::after {
  content: ''; flex: 1; height: 1px; background: #e5e7eb;
}

.auth-form__field  { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.auth-form__field label { font-size: 13px; font-weight: 600; color: #374151; }
.auth-form__field input {
  padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px;
  font-size: 14px; outline: none; transition: border-color 0.15s;
  background: var(--bg-input); color: var(--text);
}
.auth-form__field input:focus { border-color: var(--indigo); }
.auth-form__error  { color: #ef4444; font-size: 13px; margin-bottom: 12px; }
.auth-form__btn {
  width: 100%; padding: 11px; background: #4f46e5; color: #fff;
  border: none; border-radius: 8px; font-size: 15px; font-weight: 700;
  cursor: pointer; transition: background 0.15s;
}
.auth-form__btn:hover:not(:disabled) { background: #4338ca; }
.auth-form__btn:disabled { opacity: 0.6; cursor: not-allowed; }
.auth-form__footer { text-align: center; font-size: 13px; color: #6b7280; margin-top: 16px; }
.auth-form__footer a { color: #4f46e5; font-weight: 600; text-decoration: none; }
</style>
