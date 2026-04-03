<template>
  <div class="home">

    <!-- HERO -->
    <section class="hero">
      <div class="hero__bg">
        <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1600&q=80" alt="Hotel" class="hero__img" />
        <div class="hero__overlay"></div>
      </div>
      <div class="hero__content">
        <p class="hero__eyebrow">Welcome to SATAAB Hotel</p>
        <h1 class="hero__title">Experience Luxury<br><span class="hero__gold">Like Never Before</span></h1>
        <p class="hero__sub">World-class comfort and unforgettable hospitality in the heart of Dessie. </p>
        <div class="hero__search">
          <div class="hero__field">
            <label>Check-in</label>
            <input type="date" v-model="checkIn" :min="today" />
          </div>
          <div class="hero__sep"></div>
          <div class="hero__field">
            <label>Check-out</label>
            <input type="date" v-model="checkOut" :min="checkIn || today" />
          </div>
          <div class="hero__sep"></div>
          <div class="hero__field">
            <label>Guests</label>
            <select v-model="guests">
              <option v-for="n in 6" :key="n" :value="n">{{ n }} Guest{{ n > 1 ? 's' : '' }}</option>
            </select>
          </div>
          <button class="hero__search-btn" @click="searchRooms">Check Availability</button>
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section class="stats">
      <div class="stats__inner">
        <div class="stats__item" v-for="s in stats" :key="s.label">
          <span class="stats__num">{{ s.value }}</span>
          <span class="stats__label">{{ s.label }}</span>
        </div>
      </div>
    </section>

    <!-- ROOMS -->
    <section class="sec">
      <div class="sec__inner">
        <p class="eyebrow">Our Accommodations</p>
        <h2 class="sec__title">Rooms &amp; Suites</h2>
        <p class="sec__sub">Each room is thoughtfully designed for ultimate comfort and style.</p>
        <div v-if="loadingRooms" class="rooms-grid">
          <div v-for="n in 3" :key="n" class="room-skel"></div>
        </div>
        <div v-else class="rooms-grid">
          <article v-for="room in featuredRooms" :key="room.id" class="rcard" @click="goBook(room)">
            <div class="rcard__img-wrap">
              <img :src="roomImg(room)" :alt="room.name" class="rcard__img" @error="e => e.target.src=fallbackImg" />
              <span class="rcard__badge" :class="`rcard__badge--${room.type}`">{{ room.type }}</span>
              <div class="rcard__hover"><button>View &amp; Book</button></div>
            </div>
            <div class="rcard__body">
              <div class="rcard__row">
                <div>
                  <p class="rcard__num">Room {{ room.room_number }}</p>
                  <h3 class="rcard__name">{{ room.name }}</h3>
                </div>
                <div class="rcard__price-wrap">
                  <span class="rcard__price">ETB {{ fmt(room.price_per_night) }}</span>
                  <span class="rcard__per">/night</span>
                </div>
              </div>
              <p class="rcard__desc">{{ truncate(room.description) }}</p>
              <div class="rcard__chips">
                <span v-for="a in (room.amenities||[]).slice(0,4)" :key="a" class="chip">{{ amenityIcon(a) }} {{ amenityLabel(a) }}</span>
              </div>
              <div class="rcard__foot">
                <span>👥 Up to {{ room.capacity }} guests</span>
                <span v-if="room.average_rating">⭐ {{ room.average_rating }}</span>
              </div>
            </div>
          </article>
          <div v-if="!featuredRooms.length" class="rooms-empty">No rooms available right now.</div>
        </div>
        <div class="sec__cta"><router-link to="/rooms" class="btn-gold">View All Rooms →</router-link></div>
      </div>
    </section>

    <!-- AMENITIES -->
    <section class="sec sec--gray" id="amenities">
      <div class="sec__inner">
        <p class="eyebrow">What We Offer</p>
        <h2 class="sec__title">Hotel Amenities</h2>
        <div class="amen-grid">
          <div class="amen-card" v-for="a in amenities" :key="a.title">
            <div class="amen-card__icon">{{ a.icon }}</div>
            <h3>{{ a.title }}</h3>
            <p>{{ a.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ABOUT SPLIT -->
    <section class="about" id="about">
      <div class="about__imgs">
        <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=700&q=80" alt="Pool" />
        <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=700&q=80" alt="Dining" class="about__img2" />
      </div>
      <div class="about__text">
        <p class="eyebrow">Our Story</p>
        <h2 class="sec__title">A Legacy of<br>Exceptional Hospitality</h2>
        <p class="about__body">Nestled in the vibrant heart of Addis Ababa, SATAAB Hotel blends Ethiopian warmth with international standards to create an experience that is truly one of a kind.</p>
        <ul class="about__list">
          <li v-for="f in features" :key="f">✦ {{ f }}</li>
        </ul>
        <router-link to="/rooms" class="btn-dark">Explore Rooms</router-link>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="sec sec--gray">
      <div class="sec__inner">
        <p class="eyebrow">Guest Reviews</p>
        <h2 class="sec__title">What Our Guests Say</h2>
        <div class="testi-grid">
          <div class="testi" v-for="t in testimonials" :key="t.name">
            <div class="testi__stars">★★★★★</div>
            <p class="testi__text">"{{ t.text }}"</p>
            <div class="testi__author">
              <div class="testi__avatar">{{ t.name[0] }}</div>
              <div>
                <p class="testi__name">{{ t.name }}</p>
                <p class="testi__origin">{{ t.origin }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA BANNER -->
    <section class="cta-banner">
      <div class="cta-banner__inner">
        <h2>Ready for an Unforgettable Stay?</h2>
        <p>Book directly and enjoy exclusive rates, complimentary breakfast, and early check-in.</p>
        <router-link to="/rooms" class="btn-gold">Reserve Your Room</router-link>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { roomService } from '../services/rooms'

const router = useRouter()
const today  = new Date().toISOString().split('T')[0]
const checkIn  = ref('')
const checkOut = ref('')
const guests   = ref(1)
const loadingRooms  = ref(true)
const featuredRooms = ref([])
const fallbackImg   = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80'

const ROOM_FALLBACKS = [
  'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
  'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=600&q=80',
  'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=600&q=80',
]

function roomImg(room) {
  if (room.images?.length) return room.images[0]
  const idx = room.id % ROOM_FALLBACKS.length
  return ROOM_FALLBACKS[idx]
}

onMounted(async () => {
  loadStats()
  try {
    const { data } = await roomService.list({ per_page: 6, status: 'available' }, { _silent: true })
    const raw = data.data?.rooms
    featuredRooms.value = raw?.data ?? raw ?? data.data?.data ?? data.data ?? []
  } catch { featuredRooms.value = [] }
  finally { loadingRooms.value = false }
})

function searchRooms() {
  router.push({ path: '/rooms', query: { check_in: checkIn.value, check_out: checkOut.value, guests: guests.value } })
}

function goBook(room) {
  router.push({ name: 'book', params: { id: room.id } })
}

function fmt(n) { return Number(n||0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function truncate(s) { return s && s.length > 80 ? s.slice(0,80).trimEnd()+'…' : (s||'') }

const AMENITY_META = {
  wifi:'📶',tv:'📺',minibar:'🍷',bathtub:'🛁',balcony:'🌅',safe:'🔒',
  air_conditioning:'❄️',jacuzzi:'♨️',kitchen:'🍳',nespresso:'☕',
  private_bathroom:'🚿',garden_view:'🌿',parking:'🅿️',gym:'🏋️',pool:'🏊',
}
const AMENITY_LABELS = {
  wifi:'WiFi',tv:'TV',minibar:'Minibar',bathtub:'Bathtub',balcony:'Balcony',
  safe:'Safe',air_conditioning:'A/C',jacuzzi:'Jacuzzi',kitchen:'Kitchen',
  nespresso:'Coffee',private_bathroom:'Bathroom',garden_view:'Garden View',
  parking:'Parking',gym:'Gym',pool:'Pool',
}
function amenityIcon(k)  { return AMENITY_META[k]  ?? '✦' }
function amenityLabel(k) { return AMENITY_LABELS[k] ?? k.replace(/_/g,' ') }

const stats = ref([
  { value: '…',  label: 'Luxury Rooms' },
  { value: '…',  label: 'Happy Guests' },
  { value: '…',  label: 'Total Bookings' },
  { value: '…',  label: 'Average Rating' },
])

async function loadStats() {
  try {
    const { data } = await roomService.stats({ _silent: true })
    const d = data.data
    stats.value = [
      { value: d.total_rooms,                                    label: 'Luxury Rooms' },
      { value: d.total_guests > 999 ? Math.floor(d.total_guests/1000)+'K+' : d.total_guests+'+', label: 'Happy Guests' },
      { value: d.total_bookings+'+',                             label: 'Total Bookings' },
      { value: d.average_rating ? d.average_rating + '★' : 'N/A', label: 'Average Rating' },
    ]
  } catch {
    stats.value = [
      { value: '—', label: 'Luxury Rooms' },
      { value: '—', label: 'Happy Guests' },
      { value: '—', label: 'Total Bookings' },
      { value: '—', label: 'Average Rating' },
    ]
  }
}

const amenities = [
  { icon: '🏊', title: 'Swimming Pool',    desc: 'Relax in our heated outdoor pool with panoramic city views.' },
  { icon: '🍽️', title: 'Fine Dining',      desc: 'Savor authentic Ethiopian and international cuisine.' },
  { icon: '💆', title: 'Spa & Wellness',   desc: 'Rejuvenate with our full-service spa and wellness center.' },
  { icon: '🏋️', title: 'Fitness Center',   desc: 'State-of-the-art gym open 24/7 for all guests.' },
  { icon: '🚗', title: 'Valet Parking',    desc: 'Complimentary valet parking for all hotel guests.' },
  { icon: '📶', title: 'High-Speed WiFi',  desc: 'Blazing-fast internet throughout the entire property.' },
  { icon: '🛎️', title: '24/7 Concierge',  desc: 'Our team is always available to fulfill your every need.' },
  { icon: '✈️', title: 'Airport Transfer', desc: 'Seamless transfers to and from Bole International Airport.' },
]

const features = [
  'Award-winning Ethiopian & international cuisine',
  'Rooftop pool with panoramic city views',
  'Personalized butler service for suite guests',
  'Eco-friendly practices and sustainable operations',
]

const testimonials = [
  { name: 'Sarah M.',   origin: 'London, UK',       text: 'Absolutely stunning hotel. The staff went above and beyond to make our anniversary special. Will definitely return!' },
  { name: 'Ahmed K.',   origin: 'Dubai, UAE',        text: 'The suite was immaculate and the view was breathtaking. Best hotel experience I have had in Africa.' },
  { name: 'Meron T.',   origin: 'Addis Ababa, ET',   text: 'Perfect for a staycation. The spa is world-class and the food is incredible. Highly recommend the deluxe suite.' },
]
</script>

<style scoped>
/* ── HERO ──────────────────────────────────────────────────────── */
.hero {
  position: relative; min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.hero__bg { position: absolute; inset: 0; }
.hero__img { width: 100%; height: 100%; object-fit: cover; }
.hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(10,10,20,0.75) 0%, rgba(10,10,20,0.45) 100%);
}
.hero__content {
  position: relative; z-index: 2;
  text-align: center; padding: 120px 24px 80px;
  max-width: 860px;
}
.hero__eyebrow {
  color: #c9a84c; font-size: 13px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 3px; margin-bottom: 16px;
}
.hero__title {
  font-size: clamp(2.4rem, 6vw, 4.2rem); font-weight: 900;
  color: #fff; line-height: 1.1; margin-bottom: 20px;
}
.hero__gold { color: #c9a84c; }
.hero__sub  { color: rgba(255,255,255,0.8); font-size: 1.1rem; margin-bottom: 40px; line-height: 1.7; }

/* Search bar */
.hero__search {
  display: flex; align-items: stretch;
  background: rgba(255,255,255,0.97);
  border-radius: 14px; overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  max-width: 780px; margin: 0 auto;
}
.hero__field {
  flex: 1; display: flex; flex-direction: column;
  padding: 14px 20px; gap: 4px;
}
.hero__field label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
.hero__field input, .hero__field select {
  border: none; outline: none; font-size: 14px; font-weight: 600;
  color: #1a1a2e; background: transparent; cursor: pointer;
  font-family: inherit;
}
.hero__sep { width: 1px; background: #e5e7eb; margin: 12px 0; }
.hero__search-btn {
  padding: 0 32px; background: #c9a84c; color: #1a1a2e;
  border: none; font-size: 15px; font-weight: 800; cursor: pointer;
  transition: background 0.2s; white-space: nowrap;
}
.hero__search-btn:hover { background: #f0d080; }

/* ── STATS ─────────────────────────────────────────────────────── */
.stats { background: #1a1a2e; padding: 40px 24px; }
.stats__inner {
  max-width: 900px; margin: 0 auto;
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; text-align: center;
}
.stats__num   { display: block; font-size: 2rem; font-weight: 900; color: #c9a84c; }
.stats__label { display: block; font-size: 12.5px; color: rgba(255,255,255,0.6); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

/* ── SECTIONS ──────────────────────────────────────────────────── */
.sec { padding: 90px 24px; }
.sec--gray { background: #f9fafb; }
.sec__inner { max-width: 1200px; margin: 0 auto; }
.eyebrow { color: #c9a84c; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 2.5px; margin-bottom: 10px; }
.sec__title { font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 900; color: #1a1a2e; margin-bottom: 14px; line-height: 1.2; }
.sec__sub   { color: #6b7280; font-size: 15px; max-width: 520px; line-height: 1.7; margin-bottom: 48px; }
.sec__cta   { text-align: center; margin-top: 48px; }

/* ── ROOM CARDS ────────────────────────────────────────────────── */
.rooms-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px;
}
.room-skel {
  height: 420px; border-radius: 16px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

.rcard {
  border-radius: 16px; overflow: hidden; background: #fff;
  box-shadow: 0 4px 20px rgba(0,0,0,0.07); cursor: pointer;
  transition: transform 0.25s, box-shadow 0.25s;
  display: flex; flex-direction: column;
}
.rcard:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.13); }

.rcard__img-wrap { position: relative; height: 220px; overflow: hidden; }
.rcard__img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.rcard:hover .rcard__img { transform: scale(1.06); }

.rcard__badge {
  position: absolute; top: 12px; left: 12px;
  padding: 4px 12px; border-radius: 99px;
  font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #fff;
}
.rcard__badge--single    { background: rgba(16,185,129,0.9); }
.rcard__badge--double    { background: rgba(59,130,246,0.9); }
.rcard__badge--deluxe    { background: rgba(245,158,11,0.9); }
.rcard__badge--suite     { background: rgba(139,92,246,0.9); }
.rcard__badge--penthouse { background: rgba(239,68,68,0.9); }

.rcard__hover {
  position: absolute; inset: 0; background: rgba(26,26,46,0.55);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.25s;
}
.rcard:hover .rcard__hover { opacity: 1; }
.rcard__hover button {
  padding: 10px 24px; background: #c9a84c; color: #1a1a2e;
  border: none; border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer;
}

.rcard__body { padding: 18px 20px 20px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.rcard__row  { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.rcard__num  { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
.rcard__name { font-size: 16px; font-weight: 800; color: #1a1a2e; }
.rcard__price-wrap { text-align: right; flex-shrink: 0; }
.rcard__price { display: block; font-size: 18px; font-weight: 900; color: #c9a84c; }
.rcard__per   { font-size: 11px; color: #9ca3af; }
.rcard__desc  { font-size: 12.5px; color: #6b7280; line-height: 1.6; }
.rcard__chips { display: flex; flex-wrap: wrap; gap: 5px; }
.chip { background: #f3f4f6; border-radius: 6px; padding: 3px 9px; font-size: 11.5px; color: #374151; }
.rcard__foot  { display: flex; justify-content: space-between; font-size: 12px; color: #9ca3af; margin-top: auto; }

.rooms-empty { text-align: center; color: #9ca3af; padding: 60px; grid-column: 1/-1; }

/* ── AMENITIES ─────────────────────────────────────────────────── */
.amen-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 48px;
}
.amen-card {
  background: #fff; border-radius: 14px; padding: 28px 22px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05); text-align: center;
  transition: transform 0.2s, box-shadow 0.2s;
}
.amen-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.1); }
.amen-card__icon { font-size: 2.2rem; margin-bottom: 14px; }
.amen-card h3    { font-size: 15px; font-weight: 800; color: #1a1a2e; margin-bottom: 8px; }
.amen-card p     { font-size: 13px; color: #6b7280; line-height: 1.6; }

/* ── ABOUT SPLIT ───────────────────────────────────────────────── */
.about {
  display: grid; grid-template-columns: 1fr 1fr; gap: 80px;
  max-width: 1200px; margin: 0 auto; padding: 90px 24px; align-items: center;
}
.about__imgs { position: relative; height: 520px; }
.about__imgs img:first-child {
  width: 75%; height: 420px; object-fit: cover; border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}
.about__img2 {
  position: absolute; bottom: 0; right: 0;
  width: 55%; height: 280px; object-fit: cover; border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
  border: 6px solid #fff;
}
.about__body { color: #6b7280; font-size: 15px; line-height: 1.8; margin: 20px 0 24px; }
.about__list { list-style: none; display: flex; flex-direction: column; gap: 10px; margin-bottom: 32px; }
.about__list li { font-size: 14px; color: #374151; font-weight: 500; }

/* ── TESTIMONIALS ──────────────────────────────────────────────── */
.testi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 48px; }
.testi {
  background: #fff; border-radius: 14px; padding: 28px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.testi__stars  { color: #c9a84c; font-size: 16px; margin-bottom: 14px; }
.testi__text   { font-size: 14px; color: #4b5563; line-height: 1.75; margin-bottom: 20px; font-style: italic; }
.testi__author { display: flex; align-items: center; gap: 12px; }
.testi__avatar {
  width: 42px; height: 42px; border-radius: 50%;
  background: #1a1a2e; color: #c9a84c;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 800; flex-shrink: 0;
}
.testi__name   { font-size: 14px; font-weight: 700; color: #1a1a2e; }
.testi__origin { font-size: 12px; color: #9ca3af; }

/* ── CTA BANNER ────────────────────────────────────────────────── */
.cta-banner {
  background: linear-gradient(135deg, #1a1a2e 0%, #2d2d5e 100%);
  padding: 90px 24px; text-align: center;
}
.cta-banner__inner { max-width: 640px; margin: 0 auto; }
.cta-banner h2 { font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 900; color: #fff; margin-bottom: 16px; }
.cta-banner p  { color: rgba(255,255,255,0.7); font-size: 15px; line-height: 1.7; margin-bottom: 36px; }

/* ── BUTTONS ───────────────────────────────────────────────────── */
.btn-gold {
  display: inline-block; padding: 14px 36px;
  background: #c9a84c; color: #1a1a2e;
  border-radius: 10px; font-size: 15px; font-weight: 800;
  text-decoration: none; transition: background 0.2s, transform 0.15s;
}
.btn-gold:hover { background: #f0d080; transform: translateY(-2px); }

.btn-dark {
  display: inline-block; padding: 14px 36px;
  background: #1a1a2e; color: #fff;
  border-radius: 10px; font-size: 15px; font-weight: 800;
  text-decoration: none; transition: background 0.2s;
}
.btn-dark:hover { background: #2d2d5e; }

/* ── RESPONSIVE ────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .rooms-grid  { grid-template-columns: repeat(2, 1fr); }
  .amen-grid   { grid-template-columns: repeat(2, 1fr); }
  .testi-grid  { grid-template-columns: repeat(2, 1fr); }
  .about       { grid-template-columns: 1fr; gap: 40px; }
  .about__imgs { height: 300px; }
  .about__imgs img:first-child { width: 80%; height: 280px; }
  .about__img2 { width: 50%; height: 200px; }
}
@media (max-width: 640px) {
  .hero__search { flex-direction: column; border-radius: 12px; }
  .hero__sep    { width: 100%; height: 1px; margin: 0; }
  .hero__search-btn { padding: 16px; }
  .stats__inner { grid-template-columns: repeat(2, 1fr); }
  .rooms-grid   { grid-template-columns: 1fr; }
  .amen-grid    { grid-template-columns: repeat(2, 1fr); }
  .testi-grid   { grid-template-columns: 1fr; }
}
</style>
