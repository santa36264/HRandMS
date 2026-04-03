<template>
  <div class="settings-view">

    <div class="page-header">
      <h1 class="page-header__title">Settings</h1>
      <p class="page-header__sub">Manage your account profile and security</p>
    </div>

    <div class="settings-grid">

      <!-- Profile Card -->
      <div class="settings-card">
        <div class="settings-card__header">
          <span class="settings-card__icon">👤</span>
          <h2>Profile Information</h2>
        </div>

        <div class="avatar-section">
          <div class="avatar-circle">{{ initials }}</div>
          <div>
            <p class="avatar-name">{{ auth.user?.name }}</p>
            <p class="avatar-role">{{ cap(auth.user?.role) }}</p>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-field">
            <label>Full Name</label>
            <input v-model="profileForm.name" placeholder="Your name" />
            <span class="form-err" v-if="profileErrors.name">{{ profileErrors.name[0] }}</span>
          </div>
          <div class="form-field">
            <label>Email</label>
            <input v-model="profileForm.email" type="email" placeholder="your@email.com" />
            <span class="form-err" v-if="profileErrors.email">{{ profileErrors.email[0] }}</span>
          </div>
          <div class="form-field">
            <label>Phone</label>
            <input v-model="profileForm.phone" placeholder="+251 9xx xxx xxx" />
          </div>
          <div class="form-field">
            <label>Nationality</label>
            <input v-model="profileForm.nationality" placeholder="e.g. Ethiopian" />
          </div>
          <div class="form-field form-field--full">
            <label>Address</label>
            <input v-model="profileForm.address" placeholder="Your address" />
          </div>
        </div>

        <div class="settings-card__footer">
          <span v-if="profileSuccess" class="success-msg">✅ Profile updated successfully.</span>
          <button class="btn-primary" @click="saveProfile" :disabled="profileSaving">
            <span v-if="profileSaving" class="spinner spinner--sm"></span>
            {{ profileSaving ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <!-- Password Card -->
      <div class="settings-card">
        <div class="settings-card__header">
          <span class="settings-card__icon">🔒</span>
          <h2>Change Password</h2>
        </div>

        <div class="form-grid form-grid--single">
          <div class="form-field">
            <label>Current Password</label>
            <div class="input-wrap">
              <input :type="showCurrent ? 'text' : 'password'" v-model="pwForm.current_password" placeholder="Current password" />
              <button class="eye-btn" @click="showCurrent = !showCurrent">{{ showCurrent ? '🙈' : '👁️' }}</button>
            </div>
            <span class="form-err" v-if="pwErrors.current_password">{{ pwErrors.current_password[0] }}</span>
          </div>
          <div class="form-field">
            <label>New Password</label>
            <div class="input-wrap">
              <input :type="showNew ? 'text' : 'password'" v-model="pwForm.password" placeholder="New password (min 8 chars)" />
              <button class="eye-btn" @click="showNew = !showNew">{{ showNew ? '🙈' : '👁️' }}</button>
            </div>
            <span class="form-err" v-if="pwErrors.password">{{ pwErrors.password[0] }}</span>
          </div>
          <div class="form-field">
            <label>Confirm New Password</label>
            <div class="input-wrap">
              <input :type="showConfirm ? 'text' : 'password'" v-model="pwForm.password_confirmation" placeholder="Repeat new password" />
              <button class="eye-btn" @click="showConfirm = !showConfirm">{{ showConfirm ? '🙈' : '👁️' }}</button>
            </div>
          </div>
        </div>

        <div class="settings-card__footer">
          <span v-if="pwSuccess" class="success-msg">✅ Password changed successfully.</span>
          <button class="btn-primary" @click="savePassword" :disabled="pwSaving">
            <span v-if="pwSaving" class="spinner spinner--sm"></span>
            {{ pwSaving ? 'Updating…' : 'Update Password' }}
          </button>
        </div>
      </div>

      <!-- Hotel Info Card (read-only context) -->
      <div class="settings-card settings-card--info">
        <div class="settings-card__header">
          <span class="settings-card__icon">🏨</span>
          <h2>System Information</h2>
        </div>
        <div class="info-grid">
          <div class="info-item"><span>System</span><strong>SATAAB Hotel Reservation &amp; Management</strong></div>
          <div class="info-item"><span>Location</span><strong>Addis Ababa, Ethiopia</strong></div>
          <div class="info-item"><span>Currency</span><strong>ETB (Ethiopian Birr)</strong></div>
          <div class="info-item"><span>Payment Gateways</span><strong>Telebirr · CBE Birr</strong></div>
          <div class="info-item"><span>Backend</span><strong>Laravel 10 + Sanctum</strong></div>
          <div class="info-item"><span>Frontend</span><strong>Vue 3 + Vite + Pinia</strong></div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../plugins/axios'

const auth = useAuthStore()

const initials = computed(() => {
  const n = auth.user?.name ?? ''
  return n.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
})

// ── Profile ──────────────────────────────────────
const profileForm = reactive({ name: '', email: '', phone: '', nationality: '', address: '' })
const profileErrors = ref({})
const profileSaving = ref(false)
const profileSuccess = ref(false)

onMounted(() => {
  const u = auth.user
  if (u) {
    profileForm.name        = u.name        ?? ''
    profileForm.email       = u.email       ?? ''
    profileForm.phone       = u.phone       ?? ''
    profileForm.nationality = u.nationality ?? ''
    profileForm.address     = u.address     ?? ''
  }
})

async function saveProfile() {
  profileSaving.value = true; profileErrors.value = {}; profileSuccess.value = false
  try {
    const { data } = await api.put('/profile', profileForm)
    auth.user = { ...auth.user, ...data.data }
    profileSuccess.value = true
    setTimeout(() => { profileSuccess.value = false }, 3000)
  } catch (e) {
    if (e.response?.status === 422) profileErrors.value = e.response.data.errors ?? {}
  } finally { profileSaving.value = false }
}

// ── Password ─────────────────────────────────────
const pwForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const pwErrors  = ref({})
const pwSaving  = ref(false)
const pwSuccess = ref(false)
const showCurrent = ref(false)
const showNew     = ref(false)
const showConfirm = ref(false)

async function savePassword() {
  pwSaving.value = true; pwErrors.value = {}; pwSuccess.value = false
  try {
    await api.put('/profile/password', pwForm)
    pwForm.current_password = ''; pwForm.password = ''; pwForm.password_confirmation = ''
    pwSuccess.value = true
    setTimeout(() => { pwSuccess.value = false }, 3000)
  } catch (e) {
    if (e.response?.status === 422) pwErrors.value = e.response.data.errors ?? {}
    else if (e.response?.status === 403) pwErrors.value = { current_password: ['Current password is incorrect.'] }
  } finally { pwSaving.value = false }
}

function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
</script>

<style scoped>
.settings-view { padding: 28px 32px; max-width: 1000px; }

.page-header { margin-bottom: 28px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.page-header__sub   { font-size: 13px; color: #9ca3af; }

.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

.settings-card {
  background: #fff; border-radius: 16px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  border: 1px solid #f0f0f0;
  display: flex; flex-direction: column;
}
.settings-card--info { grid-column: 1 / -1; }

.settings-card__header {
  display: flex; align-items: center; gap: 10px;
  padding: 20px 24px; border-bottom: 1px solid #f0f0f0;
}
.settings-card__icon { font-size: 1.3rem; }
.settings-card__header h2 { font-size: 15px; font-weight: 800; color: #1a202c; }

.avatar-section {
  display: flex; align-items: center; gap: 16px;
  padding: 20px 24px 0;
}
.avatar-circle {
  width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
  background: #1a1a2e; color: #c9a84c;
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: 800;
}
.avatar-name { font-size: 16px; font-weight: 800; color: #1a202c; }
.avatar-role { font-size: 12px; color: #9ca3af; margin-top: 2px; text-transform: capitalize; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 20px 24px; }
.form-grid--single { grid-template-columns: 1fr; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field--full { grid-column: 1 / -1; }
.form-field label { font-size: 11.5px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.4px; }
.form-field input {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #1a202c; outline: none; font-family: inherit; transition: border-color 0.15s;
}
.form-field input:focus { border-color: #4f46e5; }
.form-err { font-size: 12px; color: #ef4444; }

.input-wrap { position: relative; display: flex; }
.input-wrap input { flex: 1; padding-right: 40px; }
.eye-btn {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; font-size: 16px; padding: 2px;
}

.settings-card__footer {
  display: flex; justify-content: flex-end; align-items: center; gap: 12px;
  padding: 16px 24px; border-top: 1px solid #f0f0f0; margin-top: auto;
}
.success-msg { font-size: 13px; color: #065f46; font-weight: 600; }

.btn-primary {
  padding: 10px 22px; background: #4f46e5; color: #fff;
  border: none; border-radius: 8px; font-size: 14px; font-weight: 700;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s;
}
.btn-primary:hover:not(:disabled) { background: #4338ca; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

/* Info grid */
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; padding: 8px 0; }
.info-item {
  display: flex; flex-direction: column; gap: 3px;
  padding: 14px 24px; border-bottom: 1px solid #f9fafb;
}
.info-item:nth-child(odd) { border-right: 1px solid #f9fafb; }
.info-item span  { font-size: 11.5px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
.info-item strong { font-size: 13.5px; color: #1a202c; font-weight: 700; }

.spinner { width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: spin 0.7s linear infinite; display: inline-block; }
.spinner--sm { width: 12px; height: 12px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .settings-view { padding: 16px; }
  .settings-grid { grid-template-columns: 1fr; }
  .settings-card--info { grid-column: 1; }
  .form-grid { grid-template-columns: 1fr; }
  .info-grid { grid-template-columns: 1fr; }
  .info-item:nth-child(odd) { border-right: none; }
}
</style>
