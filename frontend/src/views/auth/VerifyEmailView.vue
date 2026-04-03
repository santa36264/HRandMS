<template>
  <OtpVerification
    :email="userEmail"
    @verified="onVerified"
    @resent="onResent"
  />
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import OtpVerification from '../../components/auth/OtpVerification.vue'
import { useAuthStore } from '../../stores/auth'

// Replace with your auth store, e.g. useAuthStore()
const userEmail = computed(() => localStorage.getItem('pending_email') ?? useAuthStore().user?.email ?? 'your@email.com')
const auth = useAuthStore()
const router = useRouter()

function onVerified(user) {
  if (user) auth.user = user
  router.push(auth.isAdmin ? '/admin' : '/')
}

function onResent() {
  console.log('OTP resent')
}
</script>
