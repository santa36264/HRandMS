<template>
  <div class="bookings-table">

    <!-- Filters bar -->
    <div class="bt__filters">
      <div class="bt__search-wrap">
        <span class="bt__search-icon">🔍</span>
        <input
          v-model="filters.search"
          class="bt__search"
          placeholder="Search reference, guest, room…"
          @input="debouncedFetch"
        />
      </div>

      <select v-model="filters.status" class="bt__select" @change="fetchBookings">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="checked_in">Checked In</option>
        <option value="checked_out">Checked Out</option>
        <option value="cancelled">Cancelled</option>
      </select>

      <select v-model="filters.payment_status" class="bt__select" @change="fetchBookings">
        <option value="">All Payments</option>
        <option value="unpaid">Unpaid</option>
        <option value="paid">Paid</option>
        <option value="refunded">Refunded</option>
      </select>

      <input v-model="filters.date_from" type="date" class="bt__select" @change="fetchBookings" title="Check-in from" />
      <input v-model="filters.date_to"   type="date" class="bt__select" @change="fetchBookings" title="Check-in to" />

      <button v-if="hasActiveFilters" class="bt__clear" @click="clearFilters">✕ Clear</button>
    </div>

    <!-- Table -->
    <div class="bt__wrap">
      <table class="bt__table">
        <thead>
          <tr>
            <th @click="setSort('booking_reference')" class="bt__th bt__th--sortable">
              Reference <SortIcon field="booking_reference" :current="sort" :dir="sortDir" />
            </th>
            <th class="bt__th">Guest</th>
            <th class="bt__th">Room</th>
            <th @click="setSort('check_in_date')" class="bt__th bt__th--sortable">
              Check-in <SortIcon field="check_in_date" :current="sort" :dir="sortDir" />
            </th>
            <th @click="setSort('check_out_date')" class="bt__th bt__th--sortable">
              Check-out <SortIcon field="check_out_date" :current="sort" :dir="sortDir" />
            </th>
            <th class="bt__th">Nights</th>
            <th @click="setSort('final_amount')" class="bt__th bt__th--sortable bt__th--right">
              Amount <SortIcon field="final_amount" :current="sort" :dir="sortDir" />
            </th>
            <th @click="setSort('status')" class="bt__th bt__th--sortable">
              Status <SortIcon field="status" :current="sort" :dir="sortDir" />
            </th>
            <th class="bt__th">Payment</th>
            <th class="bt__th bt__th--right">Actions</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading skeleton -->
          <template v-if="loading">
            <tr v-for="i in 8" :key="i" class="bt__row bt__row--skeleton">
              <td v-for="j in 10" :key="j"><div class="skeleton-line" /></td>
            </tr>
          </template>

          <!-- Empty state -->
          <tr v-else-if="!bookings.length">
            <td colspan="10" class="bt__empty">
              <div class="bt__empty-inner">
                <span class="bt__empty-icon">📋</span>
                <p>No bookings found</p>
                <button v-if="hasActiveFilters" class="bt__clear-link" @click="clearFilters">Clear filters</button>
              </div>
            </td>
          </tr>

          <!-- Rows -->
          <tr v-else v-for="b in bookings" :key="b.id" class="bt__row">
            <td class="bt__td bt__td--ref">{{ b.booking_reference }}</td>
            <td class="bt__td">
              <div class="bt__guest">
                <span class="bt__guest-name">{{ b.user?.name ?? '—' }}</span>
                <span class="bt__guest-email">{{ b.user?.email ?? '' }}</span>
              </div>
            </td>
            <td class="bt__td">
              <div class="bt__room">
                <span class="bt__room-name">{{ b.room?.name ?? '—' }}</span>
                <span class="bt__room-type">{{ b.room?.type ?? '' }}</span>
              </div>
            </td>
            <td class="bt__td">{{ formatDate(b.check_in_date) }}</td>
            <td class="bt__td">{{ formatDate(b.check_out_date) }}</td>
            <td class="bt__td bt__td--center">{{ b.nights }}</td>
            <td class="bt__td bt__td--right bt__td--amount">ETB {{ formatAmount(b.final_amount) }}</td>
            <td class="bt__td">
              <BookingStatusBadge :status="b.status" />
            </td>
            <td class="bt__td">
              <span class="bt__payment" :class="`bt__payment--${b.payment_status}`">
                {{ b.payment_status }}
              </span>
            </td>
            <td class="bt__td bt__td--right">
              <div class="bt__actions">
                <button class="bt__action-btn bt__action-btn--edit" title="Update status" @click="openStatusModal(b)">✏️</button>
                <button class="bt__action-btn bt__action-btn--view" title="View details" @click="$emit('view', b)">👁</button>
                <button class="bt__action-btn bt__action-btn--del"  title="Delete" @click="confirmDelete(b)">🗑</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="bt__pagination">
      <span class="bt__pagination-info">
        Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }}–{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of {{ pagination.total }}
      </span>
      <div class="bt__pagination-btns">
        <button :disabled="pagination.current_page === 1" @click="goToPage(pagination.current_page - 1)" class="bt__page-btn">‹</button>
        <button
          v-for="p in pageRange"
          :key="p"
          :class="['bt__page-btn', { 'bt__page-btn--active': p === pagination.current_page }]"
          @click="goToPage(p)"
        >{{ p }}</button>
        <button :disabled="pagination.current_page === pagination.last_page" @click="goToPage(pagination.current_page + 1)" class="bt__page-btn">›</button>
      </div>
    </div>

    <!-- Status modal -->
    <BookingStatusModal
      v-if="statusModal.open"
      :booking="statusModal.booking"
      :loading="statusModal.loading"
      @close="statusModal.open = false"
      @confirm="submitStatusUpdate"
    />

    <!-- Delete confirm -->
    <teleport to="body">
      <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
        <div class="confirm-modal">
          <p class="confirm-modal__text">Delete booking <strong>{{ deleteTarget.booking_reference }}</strong>? This cannot be undone.</p>
          <div class="confirm-modal__btns">
            <button class="modal__btn modal__btn--cancel" @click="deleteTarget = null">Cancel</button>
            <button class="modal__btn modal__btn--danger" :disabled="deleteLoading" @click="submitDelete">
              {{ deleteLoading ? 'Deleting…' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import BookingStatusBadge  from './BookingStatusBadge.vue'
import BookingStatusModal  from './BookingStatusModal.vue'
import { bookingsService } from '../../services/bookings'

// ── Inline SortIcon sub-component ─────────────────────────────────
const SortIcon = {
  props: ['field', 'current', 'dir'],
  template: `<span class="sort-icon">
    <span :class="['sort-icon__up',   { active: current === field && dir === 'asc'  }]">▲</span>
    <span :class="['sort-icon__down', { active: current === field && dir === 'desc' }]">▼</span>
  </span>`,
}

const emit = defineEmits(['view'])

// ── State ──────────────────────────────────────────────────────────
const bookings   = ref([])
const loading    = ref(false)
const sort       = ref('created_at')
const sortDir    = ref('desc')
const pagination = reactive({ total: 0, per_page: 15, current_page: 1, last_page: 1 })

const filters = reactive({
  search: '', status: '', payment_status: '', date_from: '', date_to: '',
})

const statusModal = reactive({ open: false, booking: null, loading: false })
const deleteTarget  = ref(null)
const deleteLoading = ref(false)

// ── Computed ───────────────────────────────────────────────────────
const hasActiveFilters = computed(() =>
  filters.search || filters.status || filters.payment_status || filters.date_from || filters.date_to
)

const pageRange = computed(() => {
  const { current_page: cur, last_page: last } = pagination
  const delta = 2
  const range = []
  for (let i = Math.max(1, cur - delta); i <= Math.min(last, cur + delta); i++) range.push(i)
  return range
})

// ── Fetch ──────────────────────────────────────────────────────────
async function fetchBookings() {
  loading.value = true
  try {
    const { data } = await bookingsService.adminList({
      ...filters,
      sort_by:  sort.value,
      sort_dir: sortDir.value,
      page:     pagination.current_page,
      per_page: pagination.per_page,
    })
    bookings.value = data.data.bookings.data ?? data.data.bookings
    Object.assign(pagination, data.data.pagination)
  } finally {
    loading.value = false
  }
}

// Debounce for search input
let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { pagination.current_page = 1; fetchBookings() }, 350)
}

function setSort(field) {
  if (sort.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value    = field
    sortDir.value = 'desc'
  }
  pagination.current_page = 1
  fetchBookings()
}

function goToPage(page) {
  pagination.current_page = page
  fetchBookings()
}

function clearFilters() {
  Object.assign(filters, { search: '', status: '', payment_status: '', date_from: '', date_to: '' })
  pagination.current_page = 1
  fetchBookings()
}

// ── Status update ──────────────────────────────────────────────────
function openStatusModal(booking) {
  statusModal.booking = booking
  statusModal.open    = true
}

async function submitStatusUpdate(payload) {
  statusModal.loading = true
  try {
    const { data } = await bookingsService.adminUpdateStatus(statusModal.booking.id, payload)
    const idx = bookings.value.findIndex(b => b.id === statusModal.booking.id)
    if (idx !== -1) bookings.value[idx] = data.data
    statusModal.open = false
  } finally {
    statusModal.loading = false
  }
}

// ── Delete ─────────────────────────────────────────────────────────
function confirmDelete(booking) { deleteTarget.value = booking }

async function submitDelete() {
  deleteLoading.value = true
  try {
    await bookingsService.adminDelete(deleteTarget.value.id)
    bookings.value = bookings.value.filter(b => b.id !== deleteTarget.value.id)
    pagination.total--
    deleteTarget.value = null
  } finally {
    deleteLoading.value = false
  }
}

// ── Helpers ────────────────────────────────────────────────────────
function formatDate(d)   { return d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }
function formatAmount(n) { return Number(n ?? 0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }

onMounted(fetchBookings)
</script>

<style scoped>
.bookings-table { display: flex; flex-direction: column; gap: 0; }

/* Filters */
.bt__filters {
  display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
  padding: 16px 20px; background: #fff;
  border: 1px solid #f0f0f0; border-radius: 12px 12px 0 0;
  border-bottom: none;
}
.bt__search-wrap { position: relative; flex: 1; min-width: 220px; }
.bt__search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 14px; }
.bt__search {
  width: 100%; padding: 8px 12px 8px 32px;
  border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 13.5px; outline: none; transition: border-color 0.15s;
}
.bt__search:focus { border-color: #4f46e5; }
.bt__select {
  padding: 8px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 13px; color: #374151; background: #fff; outline: none;
  cursor: pointer; transition: border-color 0.15s;
}
.bt__select:focus { border-color: #4f46e5; }
.bt__clear {
  padding: 8px 14px; background: #fee2e2; color: #991b1b;
  border: none; border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.15s;
}
.bt__clear:hover { background: #fecaca; }

/* Table wrap */
.bt__wrap { overflow-x: auto; border: 1px solid #f0f0f0; border-radius: 0; }
.bt__table { width: 100%; border-collapse: collapse; font-size: 13.5px; background: #fff; }

.bt__th {
  padding: 11px 14px; text-align: left; font-size: 11.5px; font-weight: 700;
  color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px;
  background: #f9fafb; border-bottom: 1px solid #f0f0f0; white-space: nowrap;
}
.bt__th--sortable { cursor: pointer; user-select: none; }
.bt__th--sortable:hover { color: #4f46e5; }
.bt__th--right { text-align: right; }

.bt__row { border-bottom: 1px solid #f9fafb; transition: background 0.1s; }
.bt__row:hover { background: #fafbff; }
.bt__row:last-child { border-bottom: none; }

.bt__td { padding: 12px 14px; color: #374151; vertical-align: middle; white-space: nowrap; }
.bt__td--ref    { font-family: monospace; font-size: 12.5px; color: #4f46e5; font-weight: 700; }
.bt__td--center { text-align: center; }
.bt__td--right  { text-align: right; }
.bt__td--amount { font-weight: 700; color: #1a202c; }

.bt__guest, .bt__room { display: flex; flex-direction: column; gap: 1px; }
.bt__guest-name, .bt__room-name { font-weight: 600; color: #1a202c; }
.bt__guest-email, .bt__room-type { font-size: 11.5px; color: #9ca3af; }

.bt__payment {
  display: inline-flex; padding: 2px 8px; border-radius: 99px;
  font-size: 11px; font-weight: 700; text-transform: capitalize;
}
.bt__payment--unpaid   { background: #fef3c7; color: #92400e; }
.bt__payment--paid     { background: #d1fae5; color: #065f46; }
.bt__payment--refunded { background: #e0e7ff; color: #3730a3; }

/* Actions */
.bt__actions { display: flex; gap: 4px; justify-content: flex-end; }
.bt__action-btn {
  width: 30px; height: 30px; border: none; border-radius: 6px;
  cursor: pointer; font-size: 14px; display: flex; align-items: center;
  justify-content: center; transition: background 0.15s;
}
.bt__action-btn--edit { background: #eef2ff; }
.bt__action-btn--edit:hover { background: #e0e7ff; }
.bt__action-btn--view { background: #f0fdf4; }
.bt__action-btn--view:hover { background: #dcfce7; }
.bt__action-btn--del  { background: #fff1f2; }
.bt__action-btn--del:hover  { background: #fee2e2; }

/* Skeleton */
.bt__row--skeleton td { padding: 14px; }
.skeleton-line {
  height: 14px; border-radius: 6px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%; animation: shimmer 1.2s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* Empty */
.bt__empty { padding: 60px 20px; }
.bt__empty-inner { display: flex; flex-direction: column; align-items: center; gap: 8px; color: #9ca3af; }
.bt__empty-icon  { font-size: 2.5rem; }
.bt__empty-inner p { font-size: 14px; font-weight: 600; }
.bt__clear-link { background: none; border: none; color: #4f46e5; font-size: 13px; font-weight: 600; cursor: pointer; }

/* Pagination */
.bt__pagination {
  display: flex; justify-content: space-between; align-items: center;
  padding: 14px 20px; background: #fff;
  border: 1px solid #f0f0f0; border-top: none; border-radius: 0 0 12px 12px;
  flex-wrap: wrap; gap: 10px;
}
.bt__pagination-info { font-size: 13px; color: #6b7280; }
.bt__pagination-btns { display: flex; gap: 4px; }
.bt__page-btn {
  min-width: 32px; height: 32px; padding: 0 8px;
  border: 1.5px solid #e5e7eb; border-radius: 7px;
  background: #fff; font-size: 13px; color: #374151;
  cursor: pointer; transition: all 0.15s;
}
.bt__page-btn:hover:not(:disabled) { border-color: #4f46e5; color: #4f46e5; }
.bt__page-btn--active { background: #4f46e5; border-color: #4f46e5; color: #fff; font-weight: 700; }
.bt__page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Sort icon */
:deep(.sort-icon) { display: inline-flex; flex-direction: column; margin-left: 4px; line-height: 1; font-size: 8px; vertical-align: middle; }
:deep(.sort-icon__up), :deep(.sort-icon__down) { color: #d1d5db; }
:deep(.sort-icon__up.active), :deep(.sort-icon__down.active) { color: #4f46e5; }

/* Delete confirm modal */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.confirm-modal {
  background: #fff; border-radius: 14px; padding: 28px;
  max-width: 400px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.confirm-modal__text { font-size: 14px; color: #374151; margin-bottom: 20px; line-height: 1.6; }
.confirm-modal__btns { display: flex; justify-content: flex-end; gap: 10px; }
.modal__btn { padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: background 0.15s; }
.modal__btn--cancel { background: #f3f4f6; color: #374151; }
.modal__btn--cancel:hover { background: #e5e7eb; }
.modal__btn--danger { background: #ef4444; color: #fff; }
.modal__btn--danger:hover:not(:disabled) { background: #dc2626; }
.modal__btn--danger:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
