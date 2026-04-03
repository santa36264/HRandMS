<template>
  <div class="room-detail" v-if="room">

    <!-- Gallery -->
    <div class="gallery">
      <div class="gallery__main">
        <img
          :src="room.images?.[activeImg] ?? FALLBACK"
          :alt="room.name"
          class="gallery__main-img"
          @error="e => e.target.src = FALLBACK"
        />
        <span class="gallery__badge" :class="`gallery__badge--${room.type}`">{{ room.type }}</span>
        <span v-if="room.images?.length > 1" class="gallery__counter">{{ activeImg + 1 }} / {{ room.images.length }}</span>
        <button v-if="activeImg > 0" class="gallery__nav gallery__nav--prev" @click="activeImg--">‹</button>
        <button v-if="activeImg < (room.images?.length ?? 1) - 1" class="gallery__nav gallery__nav--next" @click="activeImg++">›</button>
      </div>
      <div v-if="room.images?.length > 1" class="gallery__thumbs">
        <img
          v-for="(img, i) in room.images" :key="i"
          :src="img" :alt="`Photo ${i+1}`"
          class="gallery__thumb" :class="{ 'gallery__thumb--active': activeImg === i }"
          @click="activeImg = i"
          @error="e => e.target.src = FALLBACK"
        />
      </div>
    </div>

    <!-- Content -->
    <div class="room-detail__body">

      <!-- Left: info -->
      <div class="room-detail__info">
        <div class="room-detail__meta">
          <span>Room {{ room.room_number }} · Floor {{ room.floor }}</span>
          <span v-if="room.average_rating" class="room-detail__rating">⭐ {{ room.average_rating }}</span>
        </div>
        <h1 class="room-detail__name">{{ room.name }}</h1>

        <div class="room-detail__highlights">
          <div class="highlight">
            <span class="highlight__icon">👥</span>
            <div><strong>{{ room.capacity }}</strong><span>Guests</span></div>
          </div>
          <div class="highlight">
            <span class="highlight__icon">🛏️</span>
            <div><strong>{{ cap(room.type) }}</strong><span>Room Type</span></div>
          </div>
          <div class="highlight">
            <span class="highlight__icon">📐</span>
            <div><strong>Floor {{ room.floor }}</strong><span>Location</span></div>
          </div>
          <div class="highlight">
            <span class="highlight__icon">💰</span>
            <div><strong>ETB {{ fmt(room.price_per_night) }}</strong><span>Per Night</span></div>
          </div>
        </div>

        <div class="room-detail__divider" />

        <h2 class="room-detail__section-title">About This Room</h2>
        <p class="room-detail__desc">{{ room.description || 'A beautifully appointed room designed for your comfort.' }}</p>

        <div class="room-detail__divider" />

        <h2 class="room-detail__section-title">Amenities</h2>
        <div class="amenities-grid">
          <div v-for="a in (room.amenities ?? [])" :key="a" class="amenity">
            <span class="amenity__icon">{{ amenityIcon(a) }}</span>
            <span class="amenity__label">{{ amenityLabel(a) }}</span>
          </div>
          <div v-if="!room.amenities?.length" class="amenity amenity--empty">No amenities listed.</div>
        </div>

        <!-- Reviews -->
        <template v-if="reviews.length">
          <div class="room-detail__divider" />
          <h2 class="room-detail__section-title">Guest Reviews</h2>
          <div class="reviews-list">
            <div v-for="r in reviews" :key="r.id" class="review-card">
              <div class="review-card__top">
                <div class="review-card__avatar">{{ r.user_name?.[0] ?? 'G' }}</div>
                <div>
                  <p class="review-card__name">{{ r.user_name ?? 'Guest' }}</p>
                  <p class="review-card__date">{{ fmtDate(r.created_at) }}</p>
                </div>
                <div class="review-card__stars">
                  <span v-for="s in 5" :key="s" :class="s <= r.rating ? 'star--on' : 'star--off'">★</span>
                </div>
              </div>
              <p v-if="r.title" class="review-card__title">"{{ r.title }}"</p>
              <p v-if="r.comment" class="review-card__comment">{{ r.comment }}</p>
            </div>
          </div>
        </template>
      </div>

      <!-- Right: booking card -->
      <div class="room-detail__sidebar">
        <div class="booking-card">
          <div class="booking-card__price">
            <span class="booking-card__amount">ETB {{ fmt(room.price_per_night) }}</span>
            <span class="booking-card__per">/ night</span>
          </div>

          <div class="booking-card__dates">
            <div class="booking-card__date-field">
              <label>Check-in</label>
              <input type="date" v-model="checkIn" :min="today" @change="onCheckInChange" />
            </div>
            <div class="booking-card__date-sep">→</div>
            <div class="booking-card__date-field">
              <label>Check-out</label>
              <input type="date" v-model="checkOut" :min="minCheckOut" />
            </div>
          </div>

          <div v-if="nights > 0" class="booking-card__nights">
            🌙 {{ nights }} night{{ nights > 1 ? 's' : '' }} ·
            <strong>ETB {{ fmt(room.price_per_night * nights) }}</strong>
          </div>

          <div class="booking-card__guests">
            <label>Guests</label>
            <select v-model="guests">
              <option v-for="n in room.capacity" :key="n" :value="n">{{ n }} Guest{{ n > 1 ? 's' : '' }}</option>
            </select>
          </div>

          <button
            class="booking-card__btn"
            :disabled="room.status !== 'available'"
            @click="goBook"
          >
            {{ room.status === 'available' ? 'Book Now' : cap(room.status) }}
          </button>

          <p class="booking-card__note">Free cancellation up to 24h before check-in</p>
        </div>
      </div>

    </div>
  </div>

  <!-- Loading -->
  <div v-else-if="loading" class="room-detail__loading">
    <div class="room-detail__spinner"></div>
    <p>Loading room details…</p>
  </div>

  <!-- Error -->
  <div v-else class="room-detail__error">
    <span>⚠️</span>
    <p>Room not found.</p>
    <router-link to="/rooms">← Back to Rooms</router-link>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { roomService } from '../../services/rooms'
import api from '../../plugins/axios'

const FALLBACK = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80'

const route  = useRoute()
const router = useRouter()

const room     = ref(null)
const loading  = ref(true)
const reviews  = ref([])
const activeImg = ref(0)

const today = new Date().toISOString().split('T')[0]
const checkIn  = ref(route.query.check_in  || '')
const checkOut = ref(route.query.check_out || '')
const guests   = ref(Number(route.query.guests) || 1)

const minCheckOut = computed(() => {
  if (!checkIn.value) return today
  const d = new Date(checkIn.value); d.setDate(d.getDate() + 1)
  return d.toISOString().split('T')[0]
})

const nights = computed(() => {
  if (!checkIn.value || !checkOut.value) return 0
  return Math.max(0, Math.round((new Date(checkOut.value) - new Date(checkIn.value)) / 86400000))
})

function onCheckInChange() {
  if (checkOut.value && checkOut.value <= checkIn.value) checkOut.value = minCheckOut.value
}

function goBook() {
  router.push({
    name: 'book',
    params: { id: room.value.id },
    query: { check_in: checkIn.value, check_out: checkOut.value },
  })
}

onMounted(async () => {
  try {
    const { data } = await roomService.show(route.params.id)
    room.value = data.data
    // Load approved reviews for this room
    const rv = await api.get('/guest/reviews', { params: { room_id: route.params.id, per_page: 10 } })
    const rawReviews = rv.data.data?.reviews?.data ?? rv.data.data?.reviews ?? []
    reviews.value = rawReviews.map(r => ({
      ...r,
      user_name: r.user?.name ?? 'Guest',
    }))
  } catch {
    room.value = null
  } finally {
    loading.value = false
  }
})

const AMENITY_ICONS = { wifi:'📶',tv:'📺',minibar:'🍷',bathtub:'🛁',balcony:'🌅',safe:'🔒',air_conditioning:'❄️',jacuzzi:'♨️',kitchen:'🍳',nespresso:'☕',private_bathroom:'🚿',garden_view:'🌿',parking:'🅿️',gym:'🏋️',pool:'🏊' }
const AMENITY_LABELS = { wifi:'WiFi',tv:'TV',minibar:'Minibar',bathtub:'Bathtub',balcony:'Balcony',safe:'Safe',air_conditioning:'A/C',jacuzzi:'Jacuzzi',kitchen:'Kitchen',nespresso:'Coffee',private_bathroom:'Bathroom',garden_view:'Garden View',parking:'Parking',gym:'Gym',pool:'Pool' }
function amenityIcon(k)  { return AMENITY_ICONS[k]  ?? '✦' }
function amenityLabel(k) { return AMENITY_LABELS[k] ?? k.replace(/_/g,' ') }
function fmt(n) { return Number(n||0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '' }
</script>

<style scoped>
.room-detail { max-width: 1100px; margin: 0 auto; padding: 32px 20px 60px; }

/* Gallery */
.gallery { margin-bottom: 36px; }
.gallery__main {
  position: relative; width: 100%; height: 460px;
  border-radius: 16px; overflow: hidden; background: var(--bg-soft);
}
.gallery__main-img { width: 100%; height: 100%; object-fit: cover; }
.gallery__badge {
  position: absolute; top: 16px; left: 16px;
  padding: 4px 14px; border-radius: 99px;
  font-size: 11px; font-weight: 800; text-transform: uppercase; color: #fff;
}
.gallery__badge--single    { background: rgba(16,185,129,0.9); }
.gallery__badge--double    { background: rgba(59,130,246,0.9); }
.gallery__badge--deluxe    { background: rgba(245,158,11,0.9); }
.gallery__badge--suite     { background: rgba(139,92,246,0.9); }
.gallery__badge--penthouse { background: rgba(239,68,68,0.9); }
.gallery__counter {
  position: absolute; bottom: 14px; right: 14px;
  background: rgba(0,0,0,0.55); color: #fff;
  font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 99px;
}
.gallery__nav {
  position: absolute; top: 50%; transform: translateY(-50%);
  background: rgba(0,0,0,0.45); color: #fff; border: none;
  width: 40px; height: 40px; border-radius: 50%; font-size: 22px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background 0.15s;
}
.gallery__nav:hover { background: rgba(0,0,0,0.7); }
.gallery__nav--prev { left: 14px; }
.gallery__nav--next { right: 14px; }
.gallery__thumbs { display: flex; gap: 10px; margin-top: 10px; overflow-x: auto; padding-bottom: 4px; }
.gallery__thumb {
  width: 80px; height: 60px; object-fit: cover; border-radius: 8px;
  cursor: pointer; flex-shrink: 0; opacity: 0.6;
  border: 2px solid transparent; transition: opacity 0.15s, border-color 0.15s;
}
.gallery__thumb:hover { opacity: 0.85; }
.gallery__thumb--active { opacity: 1; border-color: var(--accent); }

/* Body layout */
.room-detail__body {
  display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start;
}

/* Info */
.room-detail__meta { font-size: 13px; color: var(--text-muted); margin-bottom: 6px; display: flex; gap: 12px; }
.room-detail__rating { color: #f59e0b; font-weight: 700; }
.room-detail__name { font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 900; color: var(--text); margin-bottom: 20px; }

.room-detail__highlights {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px;
}
.highlight {
  background: var(--bg-soft); border-radius: 12px; padding: 14px;
  display: flex; align-items: center; gap: 10px;
  border: 1px solid var(--border);
}
.highlight__icon { font-size: 1.4rem; }
.highlight div { display: flex; flex-direction: column; }
.highlight strong { font-size: 14px; font-weight: 800; color: var(--text); }
.highlight span { font-size: 11px; color: var(--text-muted); }

.room-detail__divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
.room-detail__section-title { font-size: 1.1rem; font-weight: 800; color: var(--text); margin-bottom: 14px; }
.room-detail__desc { font-size: 14.5px; color: var(--text-soft); line-height: 1.8; }

/* Amenities */
.amenities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
.amenity {
  display: flex; align-items: center; gap: 8px;
  background: var(--bg-soft); border-radius: 8px; padding: 10px 12px;
  border: 1px solid var(--border);
}
.amenity__icon  { font-size: 16px; }
.amenity__label { font-size: 13px; font-weight: 600; color: var(--text-soft); }
.amenity--empty { color: var(--text-muted); font-size: 13px; grid-column: 1/-1; }

/* Reviews */
.reviews-list { display: flex; flex-direction: column; gap: 16px; }
.review-card {
  background: var(--bg-soft); border-radius: 12px; padding: 16px;
  border: 1px solid var(--border);
}
.review-card__top { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.review-card__avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--indigo); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800; flex-shrink: 0;
}
.review-card__name  { font-size: 13.5px; font-weight: 700; color: var(--text); }
.review-card__date  { font-size: 11.5px; color: var(--text-muted); }
.review-card__stars { margin-left: auto; font-size: 14px; }
.star--on  { color: #f59e0b; }
.star--off { color: var(--border); }
.review-card__title   { font-size: 13.5px; font-weight: 700; color: var(--text); font-style: italic; margin-bottom: 4px; }
.review-card__comment { font-size: 13.5px; color: var(--text-soft); line-height: 1.6; }

/* Booking sidebar card */
.room-detail__sidebar { position: sticky; top: 90px; }
.booking-card {
  background: var(--bg-card); border-radius: 16px; padding: 24px;
  box-shadow: var(--shadow-md); border: 1px solid var(--border);
  display: flex; flex-direction: column; gap: 16px;
}
.booking-card__price { display: flex; align-items: baseline; gap: 6px; }
.booking-card__amount { font-size: 1.6rem; font-weight: 900; color: var(--accent); }
.booking-card__per    { font-size: 13px; color: var(--text-muted); }
.booking-card__dates {
  display: flex; align-items: center; gap: 8px;
  background: var(--bg-soft); border-radius: 10px; padding: 12px;
  border: 1px solid var(--border);
}
.booking-card__date-field { flex: 1; display: flex; flex-direction: column; gap: 3px; }
.booking-card__date-field label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); }
.booking-card__date-field input {
  border: none; background: transparent; font-size: 13.5px; font-weight: 600;
  color: var(--text); outline: none; cursor: pointer; font-family: inherit;
}
.booking-card__date-sep { color: var(--text-muted); font-size: 16px; }
.booking-card__nights {
  background: #ede9fe; color: #5b21b6; border-radius: 8px;
  padding: 8px 12px; font-size: 13px; font-weight: 600; text-align: center;
}
.booking-card__guests { display: flex; flex-direction: column; gap: 4px; }
.booking-card__guests label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); }
.booking-card__guests select {
  padding: 9px 12px; border: 1.5px solid var(--border); border-radius: 8px;
  font-size: 14px; color: var(--text); background: var(--bg-input);
  outline: none; cursor: pointer; font-family: inherit;
}
.booking-card__btn {
  width: 100%; padding: 14px; border: none; border-radius: 10px;
  background: var(--accent); color: #1a1a2e;
  font-size: 15px; font-weight: 800; cursor: pointer; transition: background 0.2s;
}
.booking-card__btn:hover:not(:disabled) { background: var(--accent-light); }
.booking-card__btn:disabled { background: var(--border); color: var(--text-muted); cursor: not-allowed; }
.booking-card__note { font-size: 12px; color: #10b981; text-align: center; font-weight: 600; }

/* Loading / Error */
.room-detail__loading, .room-detail__error {
  min-height: 60vh; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 12px;
  color: var(--text-muted);
}
.room-detail__spinner {
  width: 40px; height: 40px; border-radius: 50%;
  border: 3px solid var(--border); border-top-color: var(--accent);
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.room-detail__error span { font-size: 2.5rem; }
.room-detail__error a { color: var(--indigo); font-weight: 600; }

@media (max-width: 900px) {
  .room-detail__body { grid-template-columns: 1fr; }
  .room-detail__sidebar { position: static; }
  .room-detail__highlights { grid-template-columns: repeat(2, 1fr); }
  .gallery__main { height: 280px; }
}
</style>
