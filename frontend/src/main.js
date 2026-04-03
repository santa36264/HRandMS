import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App    from './App.vue'
import router from './router/index.js'

const pinia = createPinia()
const app   = createApp(App)

app.use(pinia)
app.use(router)

// Init theme before mount so there's no flash
import { useThemeStore } from './stores/theme'
const themeStore = useThemeStore()
themeStore.init()

// Global Vue error handler — catches errors not caught by ApiErrorBoundary
app.config.errorHandler = async (err, instance, info) => {
  console.error('[Vue error]', info, err)

  try {
    const { useToastStore } = await import('./stores/toast')
    const toast = useToastStore()
    toast.add('error', 'An unexpected error occurred. Please refresh the page.', 6000)
  } catch {
    // Store not ready yet — silently ignore
  }
}

app.mount('#app')

// Register service worker for PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {})
  })
}
