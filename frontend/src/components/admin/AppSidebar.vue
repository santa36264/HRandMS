<template>
  <aside class="sidebar" :class="{ 'sidebar--collapsed': collapsed }">

    <!-- Logo -->
    <div class="sidebar__logo">
      <img src="/logo.png" alt="SATAAB" class="sidebar__logo-img" />
      <transition name="fade-text">
        <span v-if="!collapsed" class="sidebar__logo-text">SATAAB</span>
      </transition>
    </div>

    <!-- Nav -->
    <nav class="sidebar__nav">
      <div v-for="group in navGroups" :key="group.label" class="sidebar__group">
        <p v-if="!collapsed" class="sidebar__group-label">{{ group.label }}</p>
        <router-link
          v-for="item in group.items"
          :key="item.to"
          :to="item.to"
          class="sidebar__link"
          :class="{ 'sidebar__link--active': isActive(item) }"
          :title="collapsed ? item.label : ''"
        >
          <span class="sidebar__link-icon">{{ item.icon }}</span>
          <transition name="fade-text">
            <span v-if="!collapsed" class="sidebar__link-label">{{ item.label }}</span>
          </transition>
          <span v-if="!collapsed && item.badge" class="sidebar__badge">{{ item.badge }}</span>
        </router-link>
      </div>
    </nav>

    <!-- Collapse toggle -->
    <button class="sidebar__toggle" @click="$emit('toggle')" :title="collapsed ? 'Expand' : 'Collapse'">
      {{ collapsed ? '→' : '←' }}
    </button>

  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router'

defineProps({ collapsed: { type: Boolean, default: false } })
defineEmits(['toggle'])

const route = useRoute()

const navGroups = [
  {
    label: 'Overview',
    items: [
      { to: '/admin',            icon: '🏠', label: 'Dashboard' },
      { to: '/admin/analytics',  icon: '📊', label: 'Analytics' },
    ],
  },
  {
    label: 'Operations',
    items: [
      { to: '/admin/bookings',   icon: '📋', label: 'Bookings' },
      { to: '/admin/rooms',      icon: '🛏️', label: 'Rooms' },
      { to: '/admin/guests',     icon: '👥', label: 'Guests' },
    ],
  },
  {
    label: 'Finance',
    items: [
      { to: '/admin/payments',   icon: '💳', label: 'Payments' },
      { to: '/admin/refunds',    icon: '↩️',  label: 'Refunds' },
    ],
  },
  {
    label: 'Property',
    items: [
      { to: '/admin/maintenance', icon: '🔧', label: 'Maintenance' },
      { to: '/admin/reviews',     icon: '⭐', label: 'Reviews' },
    ],
  },
  {
    label: 'System',
    items: [
      { to: '/admin/settings',   icon: '⚙️', label: 'Settings' },
    ],
  },
]

function isActive(item) {
  if (item.to === '/admin') return route.path === '/admin'
  return route.path.startsWith(item.to)
}
</script>

<style scoped>
.sidebar {
  width: 240px; min-height: 100vh;
  background: #1a1a2e; color: #e2e8f0;
  display: flex; flex-direction: column;
  transition: width 0.25s ease;
  flex-shrink: 0; position: relative;
}
.sidebar--collapsed { width: 64px; }

/* Logo */
.sidebar__logo {
  display: flex; align-items: center; gap: 10px;
  padding: 20px 16px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sidebar__logo-img {
  width: 36px; height: 36px; border-radius: 8px;
  object-fit: contain; flex-shrink: 0; background: #fff; padding: 2px;
}
.sidebar__logo-text { font-size: 18px; font-weight: 800; color: #fff; white-space: nowrap; }

/* Nav */
.sidebar__nav { flex: 1; padding: 12px 8px; overflow-y: auto; overflow-x: hidden; }
.sidebar__group { margin-bottom: 8px; }
.sidebar__group-label {
  font-size: 10px; font-weight: 700; color: #4a5568;
  text-transform: uppercase; letter-spacing: 1px;
  padding: 8px 10px 4px; white-space: nowrap;
}

.sidebar__link {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px; border-radius: 8px;
  color: #94a3b8; text-decoration: none; font-size: 13.5px;
  transition: background 0.15s, color 0.15s;
  white-space: nowrap; overflow: hidden;
  position: relative;
}
.sidebar__link:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }
.sidebar__link--active { background: rgba(79,70,229,0.25); color: #a5b4fc; font-weight: 600; }
.sidebar__link--active::before {
  content: ''; position: absolute; left: 0; top: 20%; bottom: 20%;
  width: 3px; background: #4f46e5; border-radius: 0 3px 3px 0;
}

.sidebar__link-icon { font-size: 16px; flex-shrink: 0; width: 20px; text-align: center; }
.sidebar__link-label { flex: 1; }
.sidebar__badge {
  background: #4f46e5; color: #fff; font-size: 10px;
  font-weight: 700; padding: 1px 6px; border-radius: 99px;
}

/* Toggle */
.sidebar__toggle {
  margin: 12px 8px; padding: 8px;
  background: rgba(255,255,255,0.05); border: none;
  border-radius: 8px; color: #64748b; cursor: pointer;
  font-size: 14px; transition: background 0.15s;
  align-self: stretch;
}
.sidebar__toggle:hover { background: rgba(255,255,255,0.1); color: #e2e8f0; }

/* Transitions */
.fade-text-enter-active, .fade-text-leave-active { transition: opacity 0.15s, width 0.25s; }
.fade-text-enter-from, .fade-text-leave-to { opacity: 0; width: 0; }
</style>
