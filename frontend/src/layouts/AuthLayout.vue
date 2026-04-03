<template>
  <div class="auth-layout">
    <div class="auth-layout__card">
      <router-link to="/" class="auth-layout__brand">
        <img src="/logo.png" alt="SATAAB Hotel" style="height:40px;object-fit:contain;margin-bottom:4px;" />
        <span>SATAAB Hotel</span>
      </router-link>
      <router-view />
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: var(--bg-soft);
}
.auth-layout__card {
  background: var(--bg-card); border-radius: 16px; padding: 40px;
  box-shadow: var(--shadow-md); width: 100%; max-width: 420px;
  border: 1px solid var(--border);
}
.auth-layout__brand {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  text-align: center; font-size: 18px; font-weight: 900;
  color: var(--text); text-decoration: none; margin-bottom: 28px;
}
</style>
