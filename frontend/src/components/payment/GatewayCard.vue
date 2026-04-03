<template>
  <button
    class="gateway-card"
    :class="{
      'gateway-card--selected': selected,
      [`gateway-card--${gateway.name}`]: true,
    }"
    :aria-pressed="selected"
    @click="$emit('select', gateway)"
  >
    <!-- Logo area -->
    <div class="gateway-card__logo">
      <div class="gateway-card__icon gateway-card__icon--chapa">
        <!-- Chapa SVG-style logo mark -->
        <svg width="28" height="28" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="20" cy="20" r="20" fill="#F5A623"/>
          <path d="M12 20C12 15.58 15.58 12 20 12C22.76 12 25.2 13.34 26.72 15.42L29.6 13.2C27.44 10.48 24.12 8.8 20 8.8C13.8 8.8 8.8 13.8 8.8 20C8.8 26.2 13.8 31.2 20 31.2C24.12 31.2 27.44 29.52 29.6 26.8L26.72 24.58C25.2 26.66 22.76 28 20 28C15.58 28 12 24.42 12 20Z" fill="white"/>
          <path d="M22 16H18V19H15V21H18V24H22V21H25V19H22V16Z" fill="white"/>
        </svg>
      </div>
    </div>

    <!-- Info -->
    <div class="gateway-card__info">
      <p class="gateway-card__name">{{ gateway.label }}</p>
      <p class="gateway-card__sub">Pay securely with Chapa — Ethiopia's leading payment gateway</p>
      <div class="gateway-card__badges">
        <span class="gateway-card__badge">{{ gateway.currencies.join(', ') }}</span>
        <span class="gateway-card__badge gateway-card__badge--secure">🔒 Secure</span>
        <span class="gateway-card__badge gateway-card__badge--methods">Telebirr · CBE · Bank</span>
      </div>
    </div>

    <!-- Selected indicator -->
    <div class="gateway-card__check" :class="{ 'gateway-card__check--visible': selected }">
      <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
      </svg>
    </div>
  </button>
</template>

<script setup>
defineProps({
  gateway:  { type: Object,  required: true },
  selected: { type: Boolean, default: false },
})
defineEmits(['select'])
</script>

<style scoped>
.gateway-card {
  display: flex; align-items: center; gap: 14px;
  width: 100%; padding: 16px;
  background: #fff; border: 2px solid #e5e7eb;
  border-radius: 12px; cursor: pointer; text-align: left;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
  position: relative;
}
.gateway-card:hover { border-color: #fbbf24; background: #fffbf0; }
.gateway-card--selected {
  border-color: #F5A623;
  background: #fffbf0;
  box-shadow: 0 0 0 3px rgba(245,166,35,0.18);
}

.gateway-card__icon {
  width: 52px; height: 52px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.gateway-card__icon--chapa { background: #fff8ec; border: 1.5px solid #fde68a; }

.gateway-card__info { flex: 1; min-width: 0; }
.gateway-card__name { font-size: 15px; font-weight: 700; color: #1a202c; margin-bottom: 2px; }
.gateway-card__sub  { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
.gateway-card__badges { display: flex; gap: 6px; flex-wrap: wrap; }
.gateway-card__badge {
  font-size: 11px; font-weight: 600; padding: 2px 8px;
  border-radius: 99px; background: #f3f4f6; color: #374151;
}
.gateway-card__badge--secure  { background: #d1fae5; color: #065f46; }
.gateway-card__badge--methods { background: #fef3c7; color: #92400e; }

.gateway-card__check {
  width: 24px; height: 24px; border-radius: 50%;
  background: #F5A623; color: #fff;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; opacity: 0; transform: scale(0.6);
  transition: opacity 0.2s, transform 0.2s;
}
.gateway-card__check--visible { opacity: 1; transform: scale(1); }
</style>
