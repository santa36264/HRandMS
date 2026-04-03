<template>
  <div class="rooms-page">

    <!-- Page Hero -->
    <div class="rooms-hero">
      <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1600&q=80" alt="Rooms" class="rooms-hero__img" />
      <div class="rooms-hero__overlay"></div>
      <div class="rooms-hero__content">
        <p class="rooms-hero__eyebrow">Our Accommodations</p>
        <h1 class="rooms-hero__title">Rooms &amp; Suites</h1>
        <p class="rooms-hero__sub">Choose from our collection of thoughtfully designed rooms</p>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar-wrap">
      <div class="search-bar">
        <div class="search-bar__field">
          <label>Check-in</label>
          <input type="date" v-model="filters.check_in" :min="today" @change="onCheckInChange" />
        </div>
        <div class="search-bar__sep"></div>
        <div class="search-bar__field">
          <label>Check-out</label>
          <input type="date" v-model="filters.check_out" :min="minCheckOut" />
        </div>
        <div class="search-bar__sep"></div>
        <div class="search-bar__field">
          <label>Guests</label>
          <select v-model="filters.guests">
            <option v-for="n in 8" :key="n" :value="n">{{ n }} Guest{{ n > 1 ? 's' : '' }}</option>
          </select>
        </div>
        <button class="search-bar__btn" @click="doSearch" :disabled="loading">
          <span v-if="loading" class="spin"></span>
          {{ loading ? 'Searching…' : 'Search' }}
        </button>
      </div>
    </div>

    <!-- Body: Filters + Grid -->
    <div class="rooms-body">

      <!-- Sidebar Filters -->
      <aside class="filters">
        <h3 class="filters__title">Filter Rooms</h3>

        <div class="filters__group">
          <label class="filters__label">Room Type</label>
          <div class="filters__types">
            <button
              v-for="t in roomTypes" :key="t.value"
              class="type-btn"
              :class="{ 'type-btn--active': filters.type === t.value }"
              @click="filters.type = filters.type === t.value ? '' : t.value; doSearch()"
            >{{ t.label }}</button>
          </div>
        </div>

        <div class="filters__group">
          <label class="filters__label">Price per Night (ETB)</label>
          <div class="price-range">
            <input type="range" v-model.number="filters.max_price" :min="500" :max="20000" :step="500" @change="doSearch" />
            <div class="price-range__vals">
              <span>ETB 0</span>
              <span>ETB {{ filters.max_price.toLocaleString() }}</span>
            </div>
          </div>
        </div>

        <div class="filters__group">
          <label class="filters__label">Sort By</label>
          <select class="filters__select" v-model="filters.sort_by" @change="doSearch">
            <option value="price_per_night">Price</option>
            <option value="capacity">Capacity</option>
            <option value="floor">Floor</option>
          </select>
          <div class="sort-dir">
            <button :class="{ active: filters.sort_dir === 'asc' }"  @click="filters.sort_dir='asc';  doSearch()">↑ Low to High</button>
            <button :class="{ active: filters.sort_dir === 'desc' }" @click="filters.sort_dir='desc'; doSearch()">↓ High to Low</button>
          </div>
        </div>

        <button class="filters__reset" @click="resetFilters">Reset Filters</button>
      </aside>

      <!-- Room Grid -->
      <div class="rooms-main">

        <!-- Results info -->
        <div class="results-bar" v-if="!loading">
          <span class="results-bar__count">
            <template v-if="searchMode">
              {{ rooms.length }} room{{ rooms.length !== 1 ? 's' : '' }} available
              · {{ meta.nights }} night{{ meta.nights !== 1 ? 's' : '' }}
            </template>
            <template v-else>
              {{ rooms.length }} room{{ rooms.length !== 1 ? 's' : '' }}
            </template>
          </span>
          <span v-if="searchMode" class="results-bar__dates">
            {{ fmtDate(filters.check_in) }} → {{ fmtDate(filters.check_out) }}
          </span>
        </div>

        <!-- Skeletons -->
        <div v-if="loading" class="rooms-grid">
          <div v-for="n in 6" :key="n" class="room-skel shimmer"></div>
        </div>

        <!-- Cards -->
        <div v-else-if="rooms.length" class="rooms-grid">
          <article v-for="room in rooms" :key="room.id" class="rcard" @click="goBook(room)">
            <div class="rcard__img-wrap">
              <img
                :src="roomImg(room)"
                :alt="room.name"
                class="rcard__img"
                @error="e => e.target.src = fallbacks[room.id % fallbacks.length]"
              />
              <span class="rcard__badge" :class="`rcard__badge--${room.type}`">{{ room.type }}</span>
              <span v-if="room.status !== 'available'" class="rcard__unavail">{{ room.status }}</span>
              <span v-if="(room.images?.length ?? 0) > 1" class="rcard__photo-count">
                📷 {{ room.images.length }}
              </span>
              <div class="rcard__hover-overlay">
                <button>View &amp; Book →</button>
              </div>
              <div v-if="room.nights" class="rcard__nights">{{ room.nights }} nights</div>
            </div>
            <div class="rcard__body">
              <div class="rcard__top">
                <div>
                  <p class="rcard__num">Room {{ room.room_number }} · Floor {{ room.floor }}</p>
                  <h3 class="rcard__name">{{ room.name }}</h3>
                </div>
                <div class="rcard__pricing">
                  <span class="rcard__price">ETB {{ fmt(room.price_per_night) }}</span>
                  <span class="rcard__per">/night</span>
                </div>
              </div>
              <p class="rcard__desc">{{ truncate(room.description) }}</p>
              <div class="rcard__chips">
                <span v-for="a in (room.amenities||[]).slice(0,4)" :key="a" class="chip">
                  {{ amenityIcon(a) }} {{ amenityLabel(a) }}
                </span>
                <span v-if="(room.amenities||[]).length > 4" class="chip chip--more">
                  +{{ room.amenities.length - 4 }} more
                </span>
              </div>
              <div class="rcard__footer">
                <span>👥 Up to {{ room.capacity }} guests</span>
                <span v-if="room.average_rating">⭐ {{ room.average_rating }}</span>
                <span v-if="room.total_price" class="rcard__total">
                  Total: <strong>ETB {{ fmt(room.total_price) }}</strong>
                </span>
              </div>
              <button
                class="rcard__book-btn"
                :class="{ 'rcard__book-btn--disabled': room.status !== 'available' }"
                :disabled="room.status !== 'available'"
                @click.stop="goBook(room)"
              >
                {{ room.status === 'available' ? 'Book Now' : room.status }}
              </button>
            </div>
          </article>
        </div>

        <!-- Empty -->
        <div v-else class="empty">
          <div class="empty__icon">🏨</div>
          <h3>No rooms found</h3>
          <p>Try different dates, fewer guests, or reset your filters.</p>
          <button @click="resetFilters">Reset Filters</button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { roomService } from '../../services/rooms'

const router = useRouter()
const route  = useRoute()

const rooms      = ref([])
const loading    = ref(false)
const searchMode = ref(false)
const meta       = reactive({ nights: 0, rooms_found: 0 })

const today = new Date().toISOString().split('T')[0]

const filters = reactive({
  check_in:  route.query.check_in  || '',
  check_out: route.query.check_out || '',
  guests:    Number(route.query.guests) || 1,
  type:      '',
  max_price: 20000,
  sort_by:   'price_per_night',
  sort_dir:  'asc',
})

const minCheckOut = computed(() => {
  if (!filters.check_in) return today
  const d = new Date(filters.check_in); d.setDate(d.getDate() + 1)
  return d.toISOString().split('T')[0]
})

function onCheckInChange() {
  if (filters.check_out && filters.check_out <= filters.check_in) {
    filters.check_out = minCheckOut.value
  }
}

const roomTypes = [
  { value: 'single',    label: 'Single' },
  { value: 'double',    label: 'Double' },
  { value: 'deluxe',    label: 'Deluxe' },
  { value: 'suite',     label: 'Suite' },
  { value: 'penthouse', label: 'Penthouse' },
]

const fallbacks = [
  'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=80',
  'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=600&q=80',
  'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=600&q=80',
  'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80',
  'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&q=80',
]

function roomImg(room) {
  if (room.images?.length) return room.images[0]
  return fallbacks[room.id % fallbacks.length]
}

async function loadAll() {
  loading.value = true
  try {
    const params = {
      per_page: 50,
      type:     filters.type     || undefined,
      max_price:filters.max_price < 20000 ? filters.max_price : undefined,
      sort_by:  filters.sort_by,
      sort_dir: filters.sort_dir,
    }
    const { data } = await roomService.list(params)
    const raw = data.data?.rooms
    rooms.value  = raw?.data ?? raw ?? data.data?.data ?? data.data ?? []
    searchMode.value = false
  } catch { rooms.value = [] }
  finally { loading.value = false }
}

async function doSearch() {
  if (!filters.check_in || !filters.check_out) {
    return loadAll()
  }
  loading.value = true
  try {
    const { data } = await roomService.availability({
      check_in:  filters.check_in,
      check_out: filters.check_out,
      guests:    filters.guests || undefined,
      type:      filters.type   || undefined,
      max_price: filters.max_price < 20000 ? filters.max_price : undefined,
      sort_by:   filters.sort_by,
      sort_dir:  filters.sort_dir,
    })
    rooms.value        = data.data?.rooms?.data ?? data.data?.rooms ?? []
    meta.nights        = data.data?.nights ?? 0
    meta.rooms_found   = data.data?.rooms_found ?? rooms.value.length
    searchMode.value   = true
  } catch { rooms.value = [] }
  finally { loading.value = false }
}

function resetFilters() {
  filters.check_in  = ''
  filters.check_out = ''
  filters.guests    = 1
  filters.type      = ''
  filters.max_price = 20000
  filters.sort_by   = 'price_per_night'
  filters.sort_dir  = 'asc'
  loadAll()
}

function goBook(room) {
  if (room.status !== 'available') return
  router.push({
    name: 'room-detail',
    params: { id: room.id },
    query: { check_in: filters.check_in, check_out: filters.check_out, guests: filters.guests },
  })
}

function fmt(n) { return Number(n||0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function truncate(s) { return s && s.length > 80 ? s.slice(0,80).trimEnd()+'…' : (s||'') }
function fmtDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

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

onMounted(() => {
  if (filters.check_in && filters.check_out) doSearch()
  else loadAll()
})
</script>

<style scoped>
/* ── HERO ──────────────────────────────────────────────────────── */
.rooms-hero {
  position: relative; height: 340px;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; margin-top: 0;
}
.rooms-hero__img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.rooms-hero__overlay { position: absolute; inset: 0; background: rgba(10,10,20,0.6); }
.rooms-hero__content { position: relative; z-index: 2; text-align: center; padding: 0 24px; }
.rooms-hero__eyebrow { color: #c9a84c; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 10px; }
.rooms-hero__title   { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900; color: #fff; margin-bottom: 10px; }
.rooms-hero__sub     { color: rgba(255,255,255,0.75); font-size: 15px; }

/* ── SEARCH BAR ────────────────────────────────────────────────── */
.search-bar-wrap {
  background: #1a1a2e; padding: 20px 24px;
  position: sticky; top: 0; z-index: 50;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.search-bar {
  max-width: 900px; margin: 0 auto;
  display: flex; align-items: stretch;
  background: #fff; border-radius: 12px; overflow: hidden;
}
.search-bar__field {
  flex: 1; display: flex; flex-direction: column;
  padding: 10px 18px; gap: 3px;
}
.search-bar__field label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
.search-bar__field input,
.search-bar__field select {
  border: none; outline: none; font-size: 14px; font-weight: 600;
  color: #1a1a2e; background: transparent; font-family: inherit; cursor: pointer;
}
.search-bar__sep { width: 1px; background: #e5e7eb; margin: 10px 0; }
.search-bar__btn {
  padding: 0 28px; background: #c9a84c; color: #1a1a2e;
  border: none; font-size: 14px; font-weight: 800; cursor: pointer;
  transition: background 0.2s; display: flex; align-items: center; gap: 8px;
  white-space: nowrap;
}
.search-bar__btn:hover:not(:disabled) { background: #f0d080; }
.search-bar__btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* ── BODY ──────────────────────────────────────────────────────── */
.rooms-body {
  display: grid; grid-template-columns: 260px 1fr; gap: 32px;
  max-width: 1280px; margin: 0 auto; padding: 40px 24px;
  background: var(--bg);
}

/* ── FILTERS ───────────────────────────────────────────────────── */
.filters {
  background: var(--bg-card); border-radius: 16px; padding: 24px;
  box-shadow: var(--shadow-sm); height: fit-content;
  position: sticky; top: 90px;
}
.filters__title { font-size: 16px; font-weight: 800; color: var(--text); margin-bottom: 20px; }
.filters__group { margin-bottom: 24px; }
.filters__label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 10px; }
.filters__types { display: flex; flex-wrap: wrap; gap: 6px; }
.type-btn {
  padding: 6px 14px; border-radius: 99px; border: 1.5px solid #e5e7eb;
  font-size: 12.5px; font-weight: 600; cursor: pointer; background: #fff;
  color: #374151; transition: all 0.15s;
}
.type-btn:hover       { border-color: #c9a84c; color: #c9a84c; }
.type-btn--active     { background: #1a1a2e; color: #c9a84c; border-color: #1a1a2e; }
.price-range input    { width: 100%; accent-color: #c9a84c; cursor: pointer; }
.price-range__vals    { display: flex; justify-content: space-between; font-size: 12px; color: #6b7280; margin-top: 6px; }
.filters__select {
  width: 100%; padding: 9px 12px; border: 1.5px solid #e5e7eb;
  border-radius: 8px; font-size: 13px; color: #1a1a2e; background: #fafafa;
  outline: none; cursor: pointer; margin-bottom: 8px;
}
.sort-dir { display: flex; gap: 6px; }
.sort-dir button {
  flex: 1; padding: 7px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 12px; font-weight: 600; cursor: pointer; background: #fff; color: #6b7280;
  transition: all 0.15s;
}
.sort-dir button.active { background: #1a1a2e; color: #c9a84c; border-color: #1a1a2e; }
.filters__reset {
  width: 100%; padding: 10px; border: none; border-radius: 8px;
  background: #f3f4f6; color: #374151; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.15s;
}
.filters__reset:hover { background: #e5e7eb; }

/* ── RESULTS BAR ───────────────────────────────────────────────── */
.results-bar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  margin-bottom: 24px;
}
.results-bar__count { font-size: 15px; font-weight: 700; color: #1a1a2e; }
.results-bar__dates { font-size: 13px; color: #6b7280; }

/* ── ROOM GRID ─────────────────────────────────────────────────── */
.rooms-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;
}
.room-skel {
  height: 420px; border-radius: 16px;
}
.shimmer {
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.4s infinite;
}
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* ── ROOM CARD ─────────────────────────────────────────────────── */
.rcard {
  background: var(--bg-card); border-radius: 16px; overflow: hidden;
  box-shadow: var(--shadow-md); cursor: pointer;
  transition: transform 0.25s, box-shadow 0.25s;
  display: flex; flex-direction: column;
}
.rcard:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }

.rcard__img-wrap { position: relative; height: 220px; overflow: hidden; flex-shrink: 0; }
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

.rcard__unavail {
  position: absolute; top: 12px; right: 12px;
  padding: 4px 12px; border-radius: 99px;
  font-size: 10px; font-weight: 700; text-transform: capitalize;
  background: rgba(0,0,0,0.6); color: #fff;
}
.rcard__photo-count {
  position: absolute; bottom: 12px; left: 12px;
  padding: 3px 9px; border-radius: 8px;
  font-size: 11px; font-weight: 700;
  background: rgba(0,0,0,0.55); color: #fff;
}
.rcard__nights {
  position: absolute; bottom: 12px; right: 12px;
  padding: 4px 10px; border-radius: 8px;
  font-size: 11px; font-weight: 700;
  background: rgba(26,26,46,0.9); color: #c9a84c;
}
.rcard__hover-overlay {
  position: absolute; inset: 0; background: rgba(26,26,46,0.5);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.25s;
}
.rcard:hover .rcard__hover-overlay { opacity: 1; }
.rcard__hover-overlay button {
  padding: 10px 24px; background: #c9a84c; color: #1a1a2e;
  border: none; border-radius: 8px; font-size: 14px; font-weight: 800; cursor: pointer;
}

.rcard__body { padding: 18px 20px 20px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.rcard__top  { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.rcard__num  { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
.rcard__name { font-size: 16px; font-weight: 800; color: var(--text); }
.rcard__pricing { text-align: right; flex-shrink: 0; }
.rcard__price { display: block; font-size: 18px; font-weight: 900; color: #c9a84c; }
.rcard__per   { font-size: 11px; color: #9ca3af; }
.rcard__desc  { font-size: 12.5px; color: #6b7280; line-height: 1.6; }
.rcard__chips { display: flex; flex-wrap: wrap; gap: 5px; }
.chip { background: #f3f4f6; border-radius: 6px; padding: 3px 9px; font-size: 11.5px; color: #374151; }
.chip--more { background: #fef3c7; color: #92400e; font-weight: 700; }
.rcard__footer { display: flex; flex-wrap: wrap; gap: 10px; font-size: 12px; color: #9ca3af; margin-top: auto; }
.rcard__total strong { color: #c9a84c; }

.rcard__book-btn {
  width: 100%; padding: 12px; border: none; border-radius: 10px;
  background: #1a1a2e; color: #fff; font-size: 14px; font-weight: 800;
  cursor: pointer; transition: background 0.2s; margin-top: 4px;
}
.rcard__book-btn:hover:not(:disabled) { background: #c9a84c; color: #1a1a2e; }
.rcard__book-btn--disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }

/* ── EMPTY ─────────────────────────────────────────────────────── */
.empty {
  text-align: center; padding: 80px 20px;
  background: #f9fafb; border-radius: 16px; grid-column: 1/-1;
}
.empty__icon { font-size: 3rem; margin-bottom: 16px; }
.empty h3    { font-size: 1.2rem; font-weight: 800; color: #1a1a2e; margin-bottom: 8px; }
.empty p     { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
.empty button {
  padding: 10px 24px; background: #1a1a2e; color: #fff;
  border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;
}

/* ── SPINNER ───────────────────────────────────────────────────── */
.spin {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(26,26,46,0.3); border-top-color: #1a1a2e;
  animation: spin 0.7s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── RESPONSIVE ────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .rooms-body { grid-template-columns: 1fr; }
  .filters { position: static; }
}
@media (max-width: 640px) {
  .search-bar { flex-direction: column; border-radius: 12px; }
  .search-bar__sep { width: 100%; height: 1px; margin: 0; }
  .search-bar__btn { padding: 14px; }
  .rooms-grid { grid-template-columns: 1fr; }
}
</style>
