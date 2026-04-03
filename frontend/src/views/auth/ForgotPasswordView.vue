<template>
  <form class="auth-form" @submit.prevent="submit">
    <h2 class="auth-form__title">Forgot Password</h2>
    <p class="auth-form__desc">Enter your email and we'll send you a reset link.</p>

    <div v-if="sent" class="auth-form__success">
      ✅ Reset link sent! Check your inbox at <strong>{{ form.email }}</strong>
    </div>

    <template v-else>
      <div class="auth-form__field">
        <label>Email Address</label>
        <input v-model="form.email" type="email" required placeholder="you@example.com" />
      </div>
      <p v-if="error" class="auth-form__error">{{ error }}</p>
      <button type="submit" class="auth-form__btn" :disabled="loading">
        {{ loading ? 'Sending…' : 'Send Reset Link' }}
      </button>
    </template>

    <p class="auth-form__footer">
      <router-link to="/auth/login">← Back to Sign In</router-link>
    </p>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import api from '../../plugins/axios'

const loading = ref(false)
const error   = ref('')
const sent    = ref(false)
const form    = ref({ email: '' })

async function submit() {
  loading.value = true
  error.value   = ''
  try {
    await api.post('/forgot-password', form.value)
    sent.value = true
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to send reset link.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-form__title { font-size: 1.4rem; font-weight: 800; color: var(--text); margin-bottom: 6px; text-align: center; }
.auth-form__desc  { font-size: 13.5px; color: var(--text-muted); text-align: center; margin-bottom: 20px; }
.auth-form__success {
  background: #d1fae5; color: #065f46; border-radius: 10px;
  padding: 14px 16px; font-size: 14px; margin-bottom: 16px; line-height: 1.6;
}
.auth-form__field  { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.auth-form__field label { font-size: 13px; font-weight: 600; color: var(--text-soft); }
.auth-form__field input {
  padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px;
  font-size: 14px; outline: none; background: var(--bg-input); color: var(--text);
  transition: border-color 0.15s;
}
.auth-form__field input:focus { border-color: var(--indigo); }
.auth-form__error { color: #ef4444; font-size: 13px; margin-bottom: 12px; }
.auth-form__btn {
  width: 100%; padding: 11px; background: var(--indigo); color: #fff;
  border: none; border-radius: 8px; font-size: 15px; font-weight: 700;
  cursor: pointer; transition: background 0.15s;
}
.auth-form__btn:hover:not(:disabled) { background: var(--indigo-dark); }
.auth-form__btn:disabled { opacity: 0.6; cursor: not-allowed; }
.auth-form__footer { text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 16px; }
.auth-form__footer a { color: var(--indigo); font-weight: 600; text-decoration: none; }
</style>
