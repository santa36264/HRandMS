<template>
  <header class="topbar">
    <div class="topbar__left">
      <button class="topbar__menu-btn" @click="$emit('toggle-sidebar')" aria-label="Toggle sidebar">☰</button>
      <div class="topbar__breadcrumb">
        <span class="topbar__breadcrumb-root">Admin</span>
        <span class="topbar__breadcrumb-sep">/</span>
        <span class="topbar__breadcrumb-current">{{ pageTitle }}</span>
      </div>
    </div>

    <div class="topbar__right">
      <!-- Date -->
      <span class="topbar__date">{{ formattedDate }}</span>

      <!-- Notifications -->
      <button class="topbar__icon-btn" title="Notifications">
        🔔
        <span v-if="notifCount" class="topbar__notif-dot">{{ notifCount }}</span>
      </button>

      <!-- User menu -->
      <div class="topbar__user" @click="menuOpen = !menuOpen" ref="userMenuRef">
        <div class="topbar__avatar">{{ initials }}</div>
        <div class="topbar__user-info">
          <span class="topbar__user-name">{{ user?.name ?? 'Admin' }}</span>
          <span class="topbar__user-role">{{ user?.role ?? 'admin' }}</span>
        </div>
        <span class="topbar__chevron">{{ menuOpen ? '▲' : '▼' }}</span>

        <!-- Dropdown -->
        <transition name="dropdown">
          <div v-if="menuOpen" class="topbar__dropdown">
            <router-link to="/admin/settings" class="topbar__dropdown-item">⚙️ Settings</router-link>
            <router-link to="/profile"        class="topbar__dropdown-item">👤 Profile</router-link>
            <hr class="topbar__dropdown-divider" />
            <button class="topbar__dropdown-item topbar__dropdown-item--danger" @click="$emit('logout')">
              🚪 Logout
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  user:       { type: Object, default: null },
  notifCount: { type: Number, default: 0 },
})
defineEmits(['toggle-sidebar', 'logout'])

const route      = useRoute()
const menuOpen   = ref(false)
const userMenuRef = ref(null)

const pageTitles = {
  '/admin':             'Dashboard',
  '/admin/analytics':   'Analytics',
  '/admin/bookings':    'Bookings',
  '/admin/rooms':       'Rooms',
  '/admin/guests':      'Guests',
  '/admin/payments':    'Payments',
  '/admin/maintenance': 'Maintenance',
  '/admin/reviews':     'Reviews',
  '/admin/settings':    'Settings',
}

const pageTitle    = computed(() => pageTitles[route.path] ?? 'Admin')
const initials     = computed(() => (props.user?.name ?? 'A').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase())
const formattedDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }))

function onClickOutside(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) menuOpen.value = false
}
onMounted(()  => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>

<style scoped>
.topbar {
  height: 60px; background: #fff;
  border-bottom: 1px solid #f0f0f0;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 20px; gap: 16px; position: sticky; top: 0; z-index: 100;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}

.topbar__left  { display: flex; align-items: center; gap: 14px; }
.topbar__right { display: flex; align-items: center; gap: 12px; }

.topbar__menu-btn {
  background: none; border: none; font-size: 18px;
  cursor: pointer; color: #6b7280; padding: 4px 6px;
  border-radius: 6px; transition: background 0.15s;
}
.topbar__menu-btn:hover { background: #f3f4f6; }

.topbar__breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 14px; }
.topbar__breadcrumb-root    { color: #9ca3af; }
.topbar__breadcrumb-sep     { color: #d1d5db; }
.topbar__breadcrumb-current { color: #1a202c; font-weight: 600; }

.topbar__date { font-size: 12.5px; color: #9ca3af; white-space: nowrap; }

.topbar__icon-btn {
  position: relative; background: none; border: none;
  font-size: 18px; cursor: pointer; padding: 4px 6px;
  border-radius: 6px; transition: background 0.15s;
}
.topbar__icon-btn:hover { background: #f3f4f6; }
.topbar__notif-dot {
  position: absolute; top: 0; right: 0;
  background: #ef4444; color: #fff;
  font-size: 9px; font-weight: 700;
  width: 16px; height: 16px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}

.topbar__user {
  display: flex; align-items: center; gap: 8px;
  cursor: pointer; padding: 6px 10px; border-radius: 8px;
  transition: background 0.15s; position: relative;
}
.topbar__user:hover { background: #f3f4f6; }

.topbar__avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: #fff; font-size: 12px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.topbar__user-info { display: flex; flex-direction: column; }
.topbar__user-name { font-size: 13px; font-weight: 600; color: #1a202c; line-height: 1.2; }
.topbar__user-role { font-size: 11px; color: #9ca3af; text-transform: capitalize; }
.topbar__chevron   { font-size: 10px; color: #9ca3af; }

/* Dropdown */
.topbar__dropdown {
  position: absolute; top: calc(100% + 6px); right: 0;
  background: #fff; border: 1px solid #e5e7eb;
  border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  min-width: 180px; padding: 6px; z-index: 200;
}
.topbar__dropdown-item {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 12px; border-radius: 7px;
  font-size: 13.5px; color: #374151; text-decoration: none;
  cursor: pointer; background: none; border: none; width: 100%; text-align: left;
  transition: background 0.15s;
}
.topbar__dropdown-item:hover { background: #f3f4f6; }
.topbar__dropdown-item--danger { color: #ef4444; }
.topbar__dropdown-item--danger:hover { background: #fee2e2; }
.topbar__dropdown-divider { border: none; border-top: 1px solid #f3f4f6; margin: 4px 0; }

.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s, transform 0.15s; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
