<template>
  <article class="room-card" :class="{ 'room-card--unavailable': !isAvailable }">

    <!-- ── Image gallery ─────────────────────────────── -->
    <div class="room-card__gallery" @click="handleBooking">
      <img
        :src="currentImage"
        :alt="`${room.name} — image ${activeImg + 1}`"
        class="room-card__img"
        loading="lazy"
        @error="onImgError"
      />

      <!-- Type badge -->
      <span class="room-card__type-badge" :class="`room-card__type-badge--${room.type}`">
        {{ typeLabel }}
      </span>

      <!-- Status badge -->
      <span v-if="!isAvailable" class="room-card__status-badge">
        {{ statusLabel }}
      </span>

      <!-- Dot navigation (only when multiple images) -->
      <div v-if="images.length > 1" class="room-card__dots" @click.stop>
        <button
          v-for="(_, i) in images"
          :key="i"
          class="room-card__dot"
          :class="{ 'room-card__dot--active': i === activeImg }"
          :aria-label="`Image ${i + 1}`"
          @click="activeImg = i"
        />
      </div>

      <!-- Prev / next arrows -->
      <template v-if="images.length > 1">
        <button class="room-card__arrow room-card__arrow--prev" aria-label="Previous image" @click.stop="prevImg">‹</button>
        <button class="room-card__arrow room-card__arrow--next" aria-label="Next image"     @click.stop="nextImg">›</button>
      </template>

      <!-- Nights overlay (when dates selected) -->
      <div v-if="room.nights" class="room-card__nights-badge">
        {{ room.nights }} night{{ room.nights > 1 ? 's' : '' }}
      </div>
    </div>

    <!-- ── Body ──────────────────────────────────────── -->
    <div class="room-card__body">

      <!-- Header row -->
      <div class="room-card__header">
        <div class="room-card__info">
          <p class="room-card__number">Room {{ room.room_number }} · Floor {{ room.floor }}</p>
          <h3 class="room-card__name">{{ room.name }}</h3>
        </div>
        <div class="room-card__pricing">
          <span class="room-card__price">ETB {{ formatPrice(room.price_per_night) }}</span>
          <span class="room-card__per">/night</span>
        </div>
      </div>

      <!-- Description (truncated) -->
      <p v-if="room.description" class="room-card__desc">{{ truncated }}</p>

      <!-- Amenities -->
      <div class="room-card__amenities">
        <span
          v-for="a in visibleAmenities"
          :key="a"
          class="room-card__chip"
          :title="amenityLabel(a)"
        >{{ amenityIcon(a) }} {{ amenityLabel(a) }}</span>
        <span v-if="extraAmenities > 0" class="room-card__chip room-card__chip--more">
          +{{ extraAmenities }} more
        </span>
      </div>

      <!-- Meta row -->
      <div class="room-card__meta">
        <span class="room-card__meta-item">
          <span class="room-card__meta-icon">👥</span> {{ room.capacity }} guest{{ room.capacity > 1 ? 's' : '' }}
        </span>
        <span v-if="room.average_rating" class="room-card__meta-item">
          <span class="room-card__meta-icon">⭐</span> {{ room.average_rating }}
        </span>
        <span class="room-card__meta-item room-card__meta-item--type">
          {{ typeLabel }}
        </span>
      </div>

      <!-- Total price row (when nights available) -->
      <div v-if="room.nights && room.total_price" class="room-card__total">
        <span>{{ room.nights }} nights total</span>
        <strong class="room-card__total-price">ETB {{ formatPrice(room.total_price) }}</strong>
      </div>

      <!-- CTA -->
      <div class="room-card__cta">
        <button
          class="room-card__book-btn"
          :class="{ 'room-card__book-btn--disabled': !isAvailable }"
          :disabled="!isAvailable"
          @click.stop="handleBooking"
        >
          <span v-if="isAvailable">Book Now</span>
          <span v-else>{{ statusLabel }}</span>
        </button>
        <button class="room-card__detail-btn" @click.stop="$emit('view', room)" aria-label="View details">
          Details →
        </button>
      </div>

    </div>
  </article>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  room: { type: Object, required: true },
  // Pass check-in/out so the card can show "Book" vs navigate to detail
  checkIn:  { type: String, default: '' },
  checkOut: { type: String, default: '' },
})

const emit = defineEmits(['book', 'view'])

// ── Image carousel ─────────────────────────────────────────────────
const FALLBACK = 'https://placehold.co/600x360/e0e7ff/4f46e5?text=No+Image'
const activeImg = ref(0)

const images = computed(() => {
  const imgs = props.room.images ?? []
  return imgs.length ? imgs : [FALLBACK]
})

const currentImage = computed(() => images.value[activeImg.value] ?? FALLBACK)

function prevImg() { activeImg.value = (activeImg.value - 1 + images.value.length) % images.value.length }
function nextImg() { activeImg.value = (activeImg.value + 1) % images.value.length }
function onImgError(e) { e.target.src = FALLBACK }

// ── Availability ───────────────────────────────────────────────────
const isAvailable = computed(() =>
  props.room.status === 'available' && props.room.is_active !== false
)

const statusLabels = {
  available:   'Available',
  occupied:    'Occupied',
  maintenance: 'Maintenance',
  reserved:    'Reserved',
}
const statusLabel = computed(() => statusLabels[props.room.status] ?? props.room.status)

// ── Type ───────────────────────────────────────────────────────────
const typeLabels = {
  single: 'Single', double: 'Double', deluxe: 'Deluxe',
  suite: 'Suite', penthouse: 'Penthouse',
}
const typeLabel = computed(() => typeLabels[props.room.type] ?? props.room.type)

// ── Description ────────────────────────────────────────────────────
const truncated = computed(() => {
  const d = props.room.description ?? ''
  return d.length > 90 ? d.slice(0, 90).trimEnd() + '…' : d
})

// ── Amenities ──────────────────────────────────────────────────────
const MAX_CHIPS = 4
const amenities = computed(() => props.room.amenities ?? [])
const visibleAmenities = computed(() => amenities.value.slice(0, MAX_CHIPS))
const extraAmenities   = computed(() => Math.max(0, amenities.value.length - MAX_CHIPS))

const AMENITY_META = {
  wifi:             { icon: '📶', label: 'WiFi' },
  tv:               { icon: '📺', label: 'TV' },
  minibar:          { icon: '🍷', label: 'Minibar' },
  bathtub:          { icon: '🛁', label: 'Bathtub' },
  balcony:          { icon: '🌅', label: 'Balcony' },
  safe:             { icon: '🔒', label: 'Safe' },
  air_conditioning: { icon: '❄️', label: 'A/C' },
  jacuzzi:          { icon: '♨️', label: 'Jacuzzi' },
  kitchen:          { icon: '🍳', label: 'Kitchen' },
  nespresso:        { icon: '☕', label: 'Coffee' },
  private_bathroom: { icon: '🚿', label: 'Bathroom' },
  garden_view:      { icon: '🌿', label: 'Garden View' },
  parking:          { icon: '🅿️', label: 'Parking' },
  gym:              { icon: '🏋️', label: 'Gym' },
  pool:             { icon: '🏊', label: 'Pool' },
}

function amenityIcon(key)  { return AMENITY_META[key]?.icon  ?? '✦' }
function amenityLabel(key) { return AMENITY_META[key]?.label ?? key.replace(/_/g, ' ') }

// ── Price ──────────────────────────────────────────────────────────
function formatPrice(n) {
  return Number(n ?? 0).toLocaleString('en-ET', { minimumFractionDigits: 2 })
}

// ── Booking ────────────────────────────────────────────────────────
function handleBooking() {
  if (!isAvailable.value) return
  emit('book', { room: props.room, checkIn: props.checkIn, checkOut: props.checkOut })
}
</script>

<style scoped>
/* ── Card shell ──────────────────────────────────────────────────── */
.room-card {
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.room-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0,0,0,0.11);
}
.room-card--unavailable { opacity: 0.75; }
.room-card--unavailable:hover { transform: none; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

/* ── Gallery ─────────────────────────────────────────────────────── */
.room-card__gallery {
  position: relative;
  height: 200px;
  overflow: hidden;
  cursor: pointer;
  background: #f3f4f6;
  flex-shrink: 0;
}
.room-card__img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
  display: block;
}
.room-card:hover .room-card__img { transform: scale(1.04); }
.room-card--unavailable .room-card__img { filter: grayscale(40%); }

/* Type badge */
.room-card__type-badge {
  position: absolute; top: 12px; left: 12px;
  padding: 4px 11px; border-radius: 99px;
  font-size: 10.5px; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.6px; color: #fff;
  backdrop-filter: blur(4px);
}
.room-card__type-badge--single    { background: rgba(16,185,129,0.9); }
.room-card__type-badge--double    { background: rgba(59,130,246,0.9); }
.room-card__type-badge--deluxe    { background: rgba(245,158,11,0.9); }
.room-card__type-badge--suite     { background: rgba(139,92,246,0.9); }
.room-card__type-badge--penthouse { background: rgba(239,68,68,0.9); }

/* Status badge */
.room-card__status-badge {
  position: absolute; top: 12px; right: 12px;
  padding: 4px 11px; border-radius: 99px;
  font-size: 10.5px; font-weight: 700;
  background: rgba(0,0,0,0.55); color: #fff;
  backdrop-filter: blur(4px);
}

/* Nights overlay */
.room-card__nights-badge {
  position: absolute; bottom: 12px; right: 12px;
  padding: 4px 10px; border-radius: 8px;
  font-size: 11px; font-weight: 700;
  background: rgba(79,70,229,0.9); color: #fff;
  backdrop-filter: blur(4px);
}

/* Arrows */
.room-card__arrow {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(255,255,255,0.85); border: none;
  font-size: 18px; line-height: 1; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s;
  color: #1a202c; font-weight: 700;
}
.room-card__gallery:hover .room-card__arrow { opacity: 1; }
.room-card__arrow--prev { left: 10px; }
.room-card__arrow--next { right: 10px; }
.room-card__arrow:hover { background: #fff; }

/* Dots */
.room-card__dots {
  position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 5px;
}
.room-card__dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(255,255,255,0.5); border: none; cursor: pointer;
  padding: 0; transition: background 0.2s, transform 0.2s;
}
.room-card__dot--active { background: #fff; transform: scale(1.3); }

/* ── Body ────────────────────────────────────────────────────────── */
.room-card__body {
  padding: 16px 18px 18px;
  display: flex; flex-direction: column; gap: 10px;
  flex: 1;
}

/* Header */
.room-card__header { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.room-card__info   { flex: 1; min-width: 0; }
.room-card__number { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
.room-card__name   { font-size: 15.5px; font-weight: 800; color: #1a202c; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.room-card__pricing { text-align: right; flex-shrink: 0; }
.room-card__price  { font-size: 19px; font-weight: 900; color: #4f46e5; display: block; }
.room-card__per    { font-size: 11px; color: #9ca3af; }

/* Description */
.room-card__desc { font-size: 12.5px; color: #6b7280; line-height: 1.55; margin: 0; }

/* Amenity chips */
.room-card__amenities { display: flex; flex-wrap: wrap; gap: 5px; }
.room-card__chip {
  background: #f3f4f6; border-radius: 6px;
  padding: 3px 9px; font-size: 11.5px; color: #374151;
  white-space: nowrap;
}
.room-card__chip--more { background: #ede9fe; color: #5b21b6; font-weight: 700; }

/* Meta */
.room-card__meta { display: flex; flex-wrap: wrap; gap: 10px; }
.room-card__meta-item { font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 3px; }
.room-card__meta-icon { font-size: 13px; }
.room-card__meta-item--type {
  margin-left: auto; font-size: 11px; font-weight: 700;
  color: #9ca3af; text-transform: uppercase; letter-spacing: 0.4px;
}

/* Total price */
.room-card__total {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 12px; background: #f5f3ff; border-radius: 8px;
  font-size: 12.5px; color: #6b7280;
}
.room-card__total-price { font-size: 15px; font-weight: 800; color: #4f46e5; }

/* CTA */
.room-card__cta { display: flex; gap: 8px; margin-top: auto; padding-top: 4px; }

.room-card__book-btn {
  flex: 1; padding: 10px 16px;
  background: #4f46e5; color: #fff;
  border: none; border-radius: 9px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  transition: background 0.15s, transform 0.1s;
}
.room-card__book-btn:hover:not(:disabled) { background: #4338ca; transform: translateY(-1px); }
.room-card__book-btn:active:not(:disabled) { transform: translateY(0); }
.room-card__book-btn--disabled {
  background: #e5e7eb; color: #9ca3af; cursor: not-allowed;
}

.room-card__detail-btn {
  padding: 10px 14px;
  background: #f3f4f6; color: #374151;
  border: none; border-radius: 9px;
  font-size: 13.5px; font-weight: 600; cursor: pointer;
  transition: background 0.15s; white-space: nowrap;
}
.room-card__detail-btn:hover { background: #e5e7eb; }
</style>
