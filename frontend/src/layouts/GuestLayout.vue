<template>
  <div class="layout">

    <!-- ── Navbar ─────────────────────────────────────────────── -->
    <header class="navbar" :class="{ 'navbar--scrolled': scrolled }">
      <div class="navbar__inner">
        <router-link to="/" class="navbar__brand">
          <img src="/logo.png" alt="SATAAB Hotel" class="navbar__brand-logo" />
          <span class="navbar__brand-text">SATAAB<span class="navbar__brand-amp"> Hotel</span></span>
        </router-link>

        <nav class="navbar__links">
          <router-link to="/" class="navbar__link">Home</router-link>
          <router-link to="/rooms" class="navbar__link">Rooms</router-link>
          <a href="/#amenities" class="navbar__link" @click.prevent="goToSection('amenities')">Amenities</a>
          <a href="/#about" class="navbar__link" @click.prevent="goToSection('about')">About</a>
        </nav>

        <div class="navbar__actions">
          <button class="navbar__theme-btn" @click="themeStore.toggle()" :title="themeStore.theme === 'dark' ? 'Switch to light' : 'Switch to dark'">
            {{ themeStore.theme === 'dark' ? '☀️' : '🌙' }}
          </button>
          <template v-if="!auth.isAuthenticated">
            <router-link to="/auth/login" class="navbar__btn navbar__btn--ghost">Sign In</router-link>
            <router-link to="/auth/register" class="navbar__btn navbar__btn--solid">Book Now</router-link>
          </template>
          <template v-else>
            <router-link v-if="!auth.isAdmin" to="/profile" class="navbar__link">My Bookings</router-link>
            <router-link v-if="auth.isAdmin" to="/admin" class="navbar__link">Dashboard</router-link>
            <button class="navbar__btn navbar__btn--ghost" @click="logout">Logout</button>
          </template>
        </div>

        <!-- Mobile hamburger -->
        <button class="navbar__hamburger" @click="mobileOpen = !mobileOpen" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>

      <!-- Mobile menu -->
      <div class="navbar__mobile" :class="{ 'navbar__mobile--open': mobileOpen }">
        <router-link to="/" class="navbar__mobile-link" @click="mobileOpen=false">Home</router-link>
        <router-link to="/rooms" class="navbar__mobile-link" @click="mobileOpen=false">Rooms</router-link>
        <a href="/#amenities" class="navbar__mobile-link" @click.prevent="goToSection('amenities')">Amenities</a>
        <a href="/#about" class="navbar__mobile-link" @click.prevent="goToSection('about')">About</a>
        <template v-if="!auth.isAuthenticated">
          <router-link to="/auth/login" class="navbar__mobile-link" @click="mobileOpen=false">Sign In</router-link>
          <router-link to="/auth/register" class="navbar__mobile-link navbar__mobile-link--cta" @click="mobileOpen=false">Book Now</router-link>
        </template>
        <template v-else>
          <router-link v-if="!auth.isAdmin" to="/profile" class="navbar__mobile-link" @click="mobileOpen=false">My Bookings</router-link>
          <router-link v-if="auth.isAdmin" to="/admin" class="navbar__mobile-link" @click="mobileOpen=false">Dashboard</router-link>
          <button class="navbar__mobile-link" @click="logout">Logout</button>
        </template>
      </div>
    </header>

    <!-- ── Page content ───────────────────────────────────────── -->
    <main class="layout__main">
      <router-view />
    </main>

    <!-- ── Footer ─────────────────────────────────────────────── -->
    <footer class="footer">
      <div class="footer__inner">
        <div class="footer__brand">
          <div class="footer__logo">
            <img src="/logo.png" alt="SATAAB Hotel" class="footer__logo-img" />
            <span>SATAAB Hotel</span>
          </div>
          <p>Luxury stays crafted for every traveler. Experience comfort, elegance, and world-class service.</p>
        </div>
        <div class="footer__col">
          <h4>Quick Links</h4>
          <router-link to="/rooms">Browse Rooms</router-link>
          <template v-if="!auth.isAuthenticated">
            <router-link to="/auth/register">Create Account</router-link>
            <router-link to="/auth/login">Sign In</router-link>
          </template>
          <template v-else>
            <router-link to="/profile">My Bookings</router-link>
          </template>
        </div>
        <div class="footer__col">
          <h4>Legal</h4>
          <router-link to="/terms">Terms of Service</router-link>
          <router-link to="/privacy">Privacy Policy</router-link>
        </div>
        <div class="footer__col">
          <h4>Contact</h4>
          <span>📍 Dessie, Ethiopia</span>
          <span>📞 +251 953 001 17</span>
          <span>✉️ semredemssie83@gmail.com</span>
        </div>
      </div>
      <div class="footer__bottom">
        <span>© {{ new Date().getFullYear() }} SATAAB Hotel. All rights reserved.</span>
        <div class="footer__bottom-links">
          <router-link to="/terms">Terms of Service</router-link>
          <router-link to="/privacy">Privacy Policy</router-link>
        </div>
      </div>
    </footer>

    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useThemeStore } from '../stores/theme'
import { useRouter } from 'vue-router'
import ToastContainer from '../components/ui/ToastContainer.vue'

const auth   = useAuthStore()
const themeStore = useThemeStore()
const router = useRouter()
const scrolled    = ref(false)
const mobileOpen  = ref(false)

function onScroll() { scrolled.value = window.scrollY > 40 }
onMounted(() => window.addEventListener('scroll', onScroll))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

async function logout() {
  mobileOpen.value = false
  await auth.logout()
  router.push('/')
}

async function goToSection(id) {
  mobileOpen.value = false
  if (router.currentRoute.value.path !== '/') {
    await router.push('/')
    // Wait for the home page to render before scrolling
    setTimeout(() => {
      document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
    }, 300)
  } else {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<style scoped>
.layout { display: flex; flex-direction: column; min-height: 100vh; }
.layout__main { flex: 1; }

/* ── Navbar ──────────────────────────────────────────────────────── */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  transition: background 0.3s, box-shadow 0.3s;
  background: var(--navbar-bg);
  backdrop-filter: blur(8px);
}
.navbar--scrolled {
  background: var(--navbar-bg-scrolled);
  box-shadow: 0 2px 20px rgba(0,0,0,0.3);
  backdrop-filter: blur(12px);
}
.navbar__inner {
  max-width: 1200px; margin: 0 auto;
  display: flex; align-items: center; gap: 32px;
  padding: 18px 24px;
}
.navbar__brand {
  display: flex; align-items: center; gap: 10px;
  font-size: 20px; font-weight: 900; color: #fff;
  text-decoration: none; flex-shrink: 0;
}
.navbar__brand-logo { height: 36px; width: auto; object-fit: contain; }
.navbar__brand-amp  { color: #c9a84c; }

.navbar__links {
  display: flex; gap: 28px; flex: 1;
}
.navbar__link {
  color: rgba(255,255,255,0.85); font-size: 14px; font-weight: 500;
  text-decoration: none; transition: color 0.2s;
}
.navbar__link:hover { color: #c9a84c; }

.navbar__actions { display: flex; gap: 10px; align-items: center; margin-left: auto; }
.navbar__theme-btn {
  background: rgba(255,255,255,0.1); border: 1.5px solid rgba(255,255,255,0.2);
  border-radius: 8px; padding: 7px 10px; font-size: 16px;
  cursor: pointer; transition: background 0.2s; color: #fff; line-height: 1;
}
.navbar__theme-btn:hover { background: rgba(255,255,255,0.2); }
.navbar__btn {
  padding: 9px 20px; border-radius: 8px;
  font-size: 14px; font-weight: 600; cursor: pointer;
  text-decoration: none; border: none; transition: all 0.2s;
}
.navbar__btn--ghost {
  background: transparent; color: #fff;
  border: 1.5px solid rgba(255,255,255,0.4);
}
.navbar__btn--ghost:hover { border-color: #c9a84c; color: #c9a84c; }
.navbar__btn--solid {
  background: #c9a84c; color: #1a1a2e;
}
.navbar__btn--solid:hover { background: #f0d080; }

/* Hamburger */
.navbar__hamburger {
  display: none; flex-direction: column; gap: 5px;
  background: none; border: none; cursor: pointer; padding: 4px; margin-left: auto;
}
.navbar__hamburger span {
  display: block; width: 24px; height: 2px; background: #fff; border-radius: 2px;
}

/* Mobile menu */
.navbar__mobile {
  display: none; flex-direction: column;
  background: rgba(26,26,46,0.98); padding: 0 24px;
  max-height: 0; overflow: hidden; transition: max-height 0.3s, padding 0.3s;
}
.navbar__mobile--open { max-height: 400px; padding: 16px 24px 24px; }
.navbar__mobile-link {
  color: rgba(255,255,255,0.85); font-size: 15px; font-weight: 500;
  padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.08);
  text-decoration: none; background: none; border-left: none; border-right: none;
  border-top: none; cursor: pointer; text-align: left; font-family: inherit;
}
.navbar__mobile-link--cta { color: #c9a84c; font-weight: 700; }

/* ── Footer ──────────────────────────────────────────────────────── */
.footer { background: var(--footer-bg); color: rgba(255,255,255,0.7); padding: 60px 24px 0; }
.footer__inner {
  max-width: 1200px; margin: 0 auto;
  display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 48px;
  padding-bottom: 48px; border-bottom: 1px solid rgba(255,255,255,0.08);
}
.footer__logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 900; color: #fff; margin-bottom: 12px; }
.footer__logo-img { height: 40px; width: auto; object-fit: contain; }
.footer__brand p { font-size: 13.5px; line-height: 1.7; max-width: 280px; }
.footer__col h4 { color: #c9a84c; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.footer__col { display: flex; flex-direction: column; gap: 10px; }
.footer__col a, .footer__col span { font-size: 13.5px; color: rgba(255,255,255,0.65); text-decoration: none; transition: color 0.2s; }
.footer__col a:hover { color: #c9a84c; }
.footer__bottom {
  max-width: 1200px; margin: 0 auto;
  padding: 20px 0; font-size: 12.5px; color: rgba(255,255,255,0.35);
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
}
.footer__bottom-links { display: flex; gap: 16px; }
.footer__bottom-links a { color: rgba(255,255,255,0.35); text-decoration: none; font-size: 12.5px; transition: color 0.2s; }
.footer__bottom-links a:hover { color: #c9a84c; }

@media (max-width: 768px) {
  .navbar__links, .navbar__actions { display: none; }
  .navbar__hamburger { display: flex; }
  .navbar__mobile { display: flex; }
  .footer__inner { grid-template-columns: 1fr; gap: 32px; }
}
</style>
