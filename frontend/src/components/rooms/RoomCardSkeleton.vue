<template>
  <div class="rcs">
    <div class="rcs__img shimmer" />
    <div class="rcs__body">
      <div class="rcs__line rcs__line--sm shimmer" />
      <div class="rcs__line rcs__line--lg shimmer" />
      <div class="rcs__line rcs__line--md shimmer" />
      <div class="rcs__chips">
        <div v-for="i in 4" :key="i" class="rcs__chip shimmer" />
      </div>
      <div class="rcs__meta shimmer" />
      <div class="rcs__btn shimmer" />
    </div>
  </div>
</template>

<style scoped>
.rcs {
  background: #fff; border-radius: 16px; overflow: hidden;
  border: 1px solid #f0f0f0; box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.rcs__img  { height: 200px; }
.rcs__body { padding: 16px 18px 18px; display: flex; flex-direction: column; gap: 10px; }

.rcs__line { height: 14px; border-radius: 6px; }
.rcs__line--sm { width: 40%; }
.rcs__line--md { width: 65%; }
.rcs__line--lg { width: 80%; height: 18px; }

.rcs__chips { display: flex; gap: 6px; }
.rcs__chip  { height: 24px; width: 72px; border-radius: 6px; }
.rcs__meta  { height: 14px; width: 55%; border-radius: 6px; }
.rcs__btn   { height: 40px; border-radius: 9px; margin-top: 4px; }

.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }
</style>
