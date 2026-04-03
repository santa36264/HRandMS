<template>
  <div class="admin-layout" :class="{ 'admin-layout--collapsed': sidebarCollapsed }">
    <AppSidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />

    <div class="admin-layout__main">
      <AppTopbar
        :user="auth.user"
        :notif-count="notifCount"
        @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed"
        @logout="handleLogout"
      />
      <main class="admin-layout__content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AppSidebar from '../components/admin/AppSidebar.vue'
import AppTopbar  from '../components/admin/AppTopbar.vue'

const auth             = useAuthStore()
const router           = useRouter()
const sidebarCollapsed = ref(false)
const notifCount       = ref(0)

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #f8f9fc;
}

.admin-layout__main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  transition: margin-left 0.25s ease;
}

.admin-layout__content {
  flex: 1;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .admin-layout { position: relative; }
}
</style>
