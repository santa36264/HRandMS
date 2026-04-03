<template>
  <div class="room-search">

    <!-- Search Form -->
    <div class="search-form">
      <h2 class="search-form__title">Find Your Perfect Room</h2>

      <div class="search-form__grid">

        <!-- Check-in -->
        <div class="form-field">
          <label class="form-field__label" for="check-in">Check-in</label>
          <div class="form-field__input-wrap">
            <span class="form-field__icon">📅</span>
            <input
              id="check-in"
              type="date"
              class="form-field__input"
              v-model="filters.check_in"
              :min="today"
              @change="onCheckInChange"
            />
          </div>
        </div>

        <!-- Check-out -->
        <div class="form-field">
          <label class="form-field__label" for="check-out">Check-out</label>
          <div class="form-field__input-wrap">
            <span class="form-field__icon">📅</span>
            <input
              id="check-out"
              type="date"
              class="form-field__input"
              v-model="filters.check_out"
              :min="minCheckOut"
            />
          </div>
        </div>

        <!-- Guests -->
        <div class="form-field">
          <label class="form-field__label" for="guests">Guests</label>
          <div class="form-field__input-wrap">
            <span class="form-field__icon">👥</span>
            <select id="guests" class="form-field__input" v-model="filters.guests">
              <option v-for="n in 8" :key="n" :value="n">{{ n }} {{ n === 1 ? 'Guest' : 'Guests' }}</option>
            </select>
          </div>
        </div>

        <!-- Room type -->
        <div class="form-field">
          <label class="form-field__label" for="type">Room Type</label>
          <div class="form-field__input-wrap">
            <span class="form-field__icon">🏨</span>
            <select id="type" class="form-field__input" v-model="filters.type">
              <option value="">All Types</option>
              <option value="single">Single</option>
              <option value="double">Double</option>
              <option value="deluxe">Deluxe</option>
              <option value="suite">Suite</option>
              <option value="penthouse">Penthouse</option>
            </select>
          </div>
        </div>

      </div>

      <!-- Price Range Slider -->
      <div class="search-form__price">
        <PriceRangeSlider
          v-model="priceRange"
          :min="0"
          :max="1000"
          :step="10"
        />
      </div>

      <!-- Sort + Actions -->
      <div class="search-form__actions">
        <div class="form-field form-field--inline">
          <label class="form-field__label" for="sort">Sort by</label>
          <select id="sort" class="form-field__input form-field__input--sm" v-model="filters.sort_by">
            <option value="price_per_night">Price</option>
            <option value="capacity">Capacity</option>
            <option value="floor">Floor</option>
          </select>
          <button
            class="sort-dir-btn"
            :title="filters.sort_dir === 'asc' ? 'Ascending' : 'Descending'"
            @click="filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'"
          >
            {{ filters.sort_dir === 'asc' ? '↑' : '↓' }}
          </button>
        </div>

        <div class="search-form__btns">
          <button class="btn btn--ghost" @click="reset">Reset</button>
          <button
            class="btn btn--primary"
            :disabled="loading || !filters.check_in || !filters.check_out"
            @click="search"
          >
            <span v-if="loading" class="spinner" aria-hidden="true" />
            {{ loading ? 'Searching...' : 'Search Rooms' }}
          </button>
        </div>
      </div>

      <!-- Error -->
      <p v-if="error" class="search-error" role="alert">⚠️ {{ error }}</p>
    </div>

    <!-- Results -->
    <div v-if="searched" class="search-results">

      <!-- Summary bar -->
      <div class="results-summary">
        <template v-if="meta.rooms_found > 0">
          <span class="results-summary__count">
            {{ meta.rooms_found }} room{{ meta.rooms_found !== 1 ? 's' : '' }} available
          </span>
          <span class="results-summary__dates">
            {{ formatDate(filters.check_in) }} → {{ formatDate(filters.check_out) }}
            · {{ meta.nights }} night{{ meta.nights !== 1 ? 's' : '' }}
          </span>
        </template>
        <span v-else class="results-summary__empty">No rooms found for your criteria.</span>
      </div>

      <!-- Room grid -->
      <div v-if="loading" class="rooms-grid">
        <RoomCardSkeleton v-for="i in 6" :key="i" />
      </div>
      <div v-else-if="rooms.length" class="rooms-grid">
        <RoomCard
          v-for="room in rooms"
          :key="room.id"
          :room="room"
          :check-in="filters.check_in"
          :check-out="filters.check_out"
          @book="onBook"
          @view="onView"
        />
      </div>

      <!-- Empty state -->
      <div v-else class="empty-state">
        <div class="empty-state__icon">🏨</div>
        <p class="empty-state__title">No rooms available</p>
        <p class="empty-state__sub">Try different dates, fewer guests, or a wider price range.</p>
        <button class="btn btn--ghost" @click="reset">Clear filters</button>
      </div>

    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import PriceRangeSlider from './PriceRangeSlider.vue'
import RoomCard         from './RoomCard.vue'
import RoomCardSkeleton from './RoomCardSkeleton.vue'
import { useRoomSearch } from '../../composables/useRoomSearch'

defineEmits(['room-selected'])

const router = useRouter()

const {
  filters, rooms, loading, error, searched, meta,
  today, minCheckOut, onCheckInChange, search, reset,
} = useRoomSearch()

// Two-way bind price range object ↔ filters.min_price / filters.max_price
const priceRange = computed({
  get: () => ({ min: filters.min_price, max: filters.max_price }),
  set: (v) => { filters.min_price = v.min; filters.max_price = v.max },
})

function onBook({ room, checkIn, checkOut }) {
  router.push({
    name: 'book',
    params: { id: room.id },
    query: { check_in: checkIn, check_out: checkOut },
  })
}

function onView(room) {
  router.push({ name: 'book', params: { id: room.id } })
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.room-search { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }

/* Form card */
.search-form {
  background: #fff;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.07);
  margin-bottom: 32px;
}
.search-form__title {
  font-size: 1.4rem; font-weight: 800;
  color: #1a202c; margin-bottom: 20px;
}
.search-form__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}
.search-form__price { margin-bottom: 20px; }
.search-form__actions {
  display: flex; justify-content: space-between;
  align-items: flex-end; flex-wrap: wrap; gap: 12px;
}
.search-form__btns { display: flex; gap: 10px; }

/* Form fields */
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field--inline { flex-direction: row; align-items: center; gap: 8px; }
.form-field__label { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.form-field__input-wrap { position: relative; display: flex; align-items: center; }
.form-field__icon { position: absolute; left: 10px; font-size: 14px; pointer-events: none; }
.form-field__input {
  width: 100%; padding: 10px 12px 10px 32px;
  border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #1a202c; background: #fafafa;
  outline: none; transition: border-color 0.2s;
  appearance: none;
}
.form-field__input:focus { border-color: #4f46e5; background: #fff; }
.form-field__input--sm { padding: 8px 10px; width: auto; }

/* Sort dir button */
.sort-dir-btn {
  padding: 8px 12px; border: 1.5px solid #e5e7eb;
  border-radius: 8px; background: #fafafa;
  font-size: 16px; cursor: pointer; transition: background 0.2s;
}
.sort-dir-btn:hover { background: #e0e7ff; }

/* Buttons */
.btn {
  padding: 10px 22px; border-radius: 8px;
  font-size: 14px; font-weight: 600; cursor: pointer;
  border: none; transition: background 0.2s, opacity 0.2s;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn--primary { background: #4f46e5; color: #fff; }
.btn--primary:hover:not(:disabled) { background: #4338ca; }
.btn--primary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn--ghost { background: #f3f4f6; color: #374151; }
.btn--ghost:hover { background: #e5e7eb; }

/* Error */
.search-error { color: #ef4444; font-size: 13px; margin-top: 10px; }

/* Results */
.results-summary {
  display: flex; align-items: center; gap: 12px;
  flex-wrap: wrap; margin-bottom: 20px;
}
.results-summary__count { font-size: 15px; font-weight: 700; color: #1a202c; }
.results-summary__dates { font-size: 13px; color: #6b7280; }
.results-summary__empty { font-size: 15px; color: #6b7280; }

.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

/* Empty state */
.empty-state {
  text-align: center; padding: 60px 20px;
  background: #fafafa; border-radius: 12px;
}
.empty-state__icon  { font-size: 3rem; margin-bottom: 12px; }
.empty-state__title { font-size: 1.1rem; font-weight: 700; color: #1a202c; margin-bottom: 6px; }
.empty-state__sub   { font-size: 14px; color: #6b7280; margin-bottom: 20px; }

/* Spinner */
.spinner {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 600px) {
  .search-form { padding: 20px 16px; }
  .search-form__actions { flex-direction: column; align-items: stretch; }
  .search-form__btns { flex-direction: column; }
  .btn { justify-content: center; }
}
</style>
