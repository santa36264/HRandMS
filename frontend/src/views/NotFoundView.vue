<template>
  <div class="not-found">
    <div class="not-found__inner">
      <div class="not-found__code">404</div>
      <h1 class="not-found__title">Page Not Found</h1>
      <p class="not-found__desc">The page you're looking for doesn't exist or has been moved.</p>
      <div class="not-found__actions">
        <router-link to="/" class="not-found__btn not-found__btn--primary">Go Home</router-link>
        <router-link to="/rooms" class="not-found__btn not-found__btn--ghost">Browse Rooms</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.not-found {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: var(--bg-soft); padding: 40px 20px;
}
.not-found__inner { text-align: center; max-width: 480px; }
.not-found__code {
  font-size: clamp(6rem, 20vw, 10rem); font-weight: 900;
  color: var(--accent); line-height: 1; margin-bottom: 16px;
  text-shadow: 0 4px 24px rgba(201,168,76,0.3);
}
.not-found__title {
  font-size: clamp(1.4rem, 4vw, 2rem); font-weight: 800;
  color: var(--text); margin-bottom: 12px;
}
.not-found__desc {
  font-size: 15px; color: var(--text-muted); margin-bottom: 32px; line-height: 1.6;
}
.not-found__actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.not-found__btn {
  padding: 12px 28px; border-radius: 10px;
  font-size: 14px; font-weight: 700; text-decoration: none;
  transition: all 0.2s;
}
.not-found__btn--primary {
  background: var(--accent); color: #1a1a2e;
}
.not-found__btn--primary:hover { background: var(--accent-light); }
.not-found__btn--ghost {
  background: transparent; color: var(--text);
  border: 1.5px solid var(--border);
}
.not-found__btn--ghost:hover { border-color: var(--accent); color: var(--accent); }
</style>
