<template>
  <div id="app-root">
    <div class="route-progress" :class="{ 'route-progress--active': navigating }"></div>
    <router-view />
    <ToastContainer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import ToastContainer from './components/ui/ToastContainer.vue'

const router     = useRouter()
const navigating = ref(false)

router.beforeEach(() => { navigating.value = true })
router.afterEach(()  => { setTimeout(() => { navigating.value = false }, 300) })
</script>

<style>
.route-progress {
  position: fixed; top: 0; left: 0; z-index: 9999;
  height: 3px; width: 0; background: #c9a84c;
  transition: width 0.3s ease, opacity 0.3s ease;
  opacity: 0;
}
.route-progress--active {
  width: 70%; opacity: 1;
  transition: width 2s cubic-bezier(0.1, 0.05, 0, 1);
}
</style>
