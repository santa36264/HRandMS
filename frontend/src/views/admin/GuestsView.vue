<template>
  <div class="guests-admin">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Guests</h1>
        <p class="page-header__sub">{{ pagination.total }} registered guests</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="filters-bar__search" placeholder="🔍 Search by name, email or phone…" @input="debouncedLoad" />
      <select v-model="sortBy" class="filters-bar__select" @change="load()">
        <option value="created_at">Joined Date</option>
        <option value="name">Name</option>
        <option value="email">Email</option>
      </select>
      <select v-model="sortDir" class="filters-bar__select" @change="load()">
        <option value="desc">Newest First</option>
        <option value="asc">Oldest First</option>
      </select>
      <button class="btn-ghost" @click="resetFilters">Reset</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <div v-if="loading" class="table-loading"><div class="spinner"></div> Loading guests…</div>
      <table v-else class="gtable">
        <thead>
          <tr>
            <th>Guest</th>
            <th>Phone</th>
            <th>Nationality</th>
            <th>Bookings</th>
            <th>Joined</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!guests.length">
            <td colspan="7" class="table-empty">No guests found.</td>
          </tr>
          <tr v-for="g in guests" :key="g.id" class="gtable__row">
            <td class="gtable__guest-cell">
              <div class="gtable__avatar">{{ g.name?.[0]?.toUpperCase() }}</div>
              <div>
                <p class="gtable__name">{{ g.name }}</p>
                <p class="gtable__email">{{ g.email }}</p>
              </div>
            </td>
            <td>{{ g.phone || '—' }}</td>
            <td>{{ g.nationality || '—' }}</td>
            <td>
              <span class="bookings-badge">{{ g.bookings_count ?? 0 }}</span>
            </td>
            <td>{{ fmtDate(g.created_at) }}</td>
            <td>
              <span class="status-badge" :class="g.email_verified_at ? 'status-badge--active' : 'status-badge--suspended'">
                {{ g.email_verified_at ? 'Active' : 'Suspended' }}
              </span>
            </td>
            <td class="gtable__actions">
              <button class="action-btn action-btn--view"   @click="openDetail(g)" title="View Details">👁️</button>
              <button class="action-btn action-btn--toggle" @click="toggleActive(g)" :title="g.email_verified_at ? 'Suspend' : 'Activate'">
                {{ g.email_verified_at ? '🔒' : '🔓' }}
              </button>
              <button class="action-btn action-btn--del" @click="confirmDelete(g)" title="Delete">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.last_page > 1">
        <button :disabled="pagination.current_page === 1" @click="load(pagination.current_page - 1)">← Prev</button>
        <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <button :disabled="pagination.current_page === pagination.last_page" @click="load(pagination.current_page + 1)">Next →</button>
      </div>
    </div>

    <!-- Detail Drawer -->
    <div v-if="detail" class="drawer-backdrop" @click.self="detail = null">
      <div class="drawer">
        <div class="drawer__header">
          <h2>Guest Profile</h2>
          <button class="drawer__close" @click="detail = null">✕</button>
        </div>
        <div class="drawer__body" v-if="detailLoading">
          <div class="table-loading"><div class="spinner"></div> Loading…</div>
        </div>
        <div class="drawer__body" v-else-if="detailData">
          <!-- Profile -->
          <div class="profile-card">
            <div class="profile-card__avatar">{{ detailData.guest?.name?.[0]?.toUpperCase() }}</div>
            <div>
              <h3 class="profile-card__name">{{ detailData.guest?.name }}</h3>
              <p class="profile-card__email">{{ detailData.guest?.email }}</p>
              <p class="profile-card__meta">{{ detailData.guest?.phone || 'No phone' }} · {{ detailData.guest?.nationality || 'Unknown nationality' }}</p>
            </div>
          </div>

          <!-- Stats -->
          <div class="detail-stats">
            <div class="detail-stat">
              <span class="detail-stat__val">{{ detailData.stats?.total_bookings }}</span>
              <span class="detail-stat__label">Bookings</span>
            </div>
            <div class="detail-stat">
              <span class="detail-stat__val">{{ detailData.stats?.total_reviews }}</span>
              <span class="detail-stat__label">Reviews</span>
            </div>
            <div class="detail-stat">
              <span class="detail-stat__val">ETB {{ fmt(detailData.stats?.total_spent) }}</span>
              <span class="detail-stat__label">Total Spent</span>
            </div>
          </div>

          <!-- Recent Bookings -->
          <h4 class="drawer__section-title">Recent Bookings</h4>
          <div v-if="!detailData.bookings?.length" class="drawer__empty">No bookings yet.</div>
          <div v-else class="booking-list">
            <div v-for="b in detailData.bookings" :key="b.id" class="booking-item">
              <div>
                <p class="booking-item__ref">{{ b.booking_reference }}</p>
                <p class="booking-item__room">{{ b.room?.name }} · {{ b.check_in_date }} → {{ b.check_out_date }}</p>
              </div>
              <div class="booking-item__right">
                <span class="bstatus" :class="`bstatus--${b.status}`">{{ b.status }}</span>
                <span class="booking-item__amount">ETB {{ fmt(b.final_amount) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
      <div class="modal">
        <div class="modal__header">
          <h2>Delete Guest</h2>
          <button class="modal__close" @click="deleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p>Delete <strong>{{ deleteTarget.name }}</strong> ({{ deleteTarget.email }})?</p>
          <p class="delete-warn">⚠️ Guests with active bookings cannot be deleted.</p>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="deleteTarget = null">Cancel</button>
          <button class="btn-danger" @click="deleteGuest" :disabled="saving">
            <span v-if="saving" class="spinner spinner--sm"></span>
            {{ saving ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../../plugins/axios'

const guests     = ref([])
const loading    = ref(false)
const saving     = ref(false)
const pagination = reactive({ total: 0, current_page: 1, last_page: 1, per_page: 15 })

const search  = ref('')
const sortBy  = ref('created_at')
const sortDir = ref('desc')

const detail        = ref(false)
const detailData    = ref(null)
const detailLoading = ref(false)
const deleteTarget  = ref(null)

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => load(), 400)
}

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/guests', {
      params: {
        page,
        per_page: pagination.per_page,
        search:   search.value   || undefined,
        sort_by:  sortBy.value,
        sort_dir: sortDir.value,
      }
    })
    guests.value = data.data?.guests ?? []
    Object.assign(pagination, data.data?.pagination ?? {})
  } catch { guests.value = [] }
  finally { loading.value = false }
}

function resetFilters() {
  search.value = ''; sortBy.value = 'created_at'; sortDir.value = 'desc'
  load()
}

async function openDetail(g) {
  detail.value = true
  detailData.value = null
  detailLoading.value = true
  try {
    const { data } = await api.get(`/admin/guests/${g.id}`)
    detailData.value = data.data
  } catch { detail.value = false }
  finally { detailLoading.value = false }
}

async function toggleActive(g) {
  try {
    const { data } = await api.patch(`/admin/guests/${g.id}/toggle-active`)
    // Reflect change locally
    if (data.data?.status === 'suspended') {
      g.email_verified_at = null
    } else {
      g.email_verified_at = new Date().toISOString()
    }
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not update guest status.')
  }
}

function confirmDelete(g) { deleteTarget.value = g }

async function deleteGuest() {
  saving.value = true
  try {
    await api.delete(`/admin/guests/${deleteTarget.value.id}`)
    deleteTarget.value = null
    load(pagination.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not delete guest.')
  } finally { saving.value = false }
}

function fmt(n) { return Number(n || 0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-ET', { year: 'numeric', month: 'short', day: 'numeric' })
}

load()
</script>

<style scoped>
.guests-admin { padding: 28px 32px; max-width: 1300px; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 2px; }
.page-header__sub   { font-size: 13px; color: #9ca3af; }

/* Filters */
.filters-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filters-bar__search {
  flex: 1; min-width: 220px; padding: 9px 14px;
  border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; outline: none;
}
.filters-bar__search:focus { border-color: #4f46e5; }
.filters-bar__select {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #374151; background: #fff; outline: none; cursor: pointer;
}

/* Table */
.table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); overflow: hidden; }
.table-loading { display: flex; align-items: center; gap: 10px; padding: 40px; justify-content: center; color: #6b7280; }
.gtable { width: 100%; border-collapse: collapse; }
.gtable thead tr { background: #f9fafb; border-bottom: 1px solid #f0f0f0; }
.gtable th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; text-align: left; white-space: nowrap; }
.gtable td { padding: 14px 16px; font-size: 13.5px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
.gtable__row:hover td { background: #fafafa; }
.table-empty { text-align: center; color: #9ca3af; padding: 48px !important; }

.gtable__guest-cell { display: flex; align-items: center; gap: 12px; }
.gtable__avatar {
  width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
  background: #1a1a2e; color: #c9a84c;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800;
}
.gtable__name  { font-weight: 700; color: #1a202c; font-size: 14px; }
.gtable__email { font-size: 12px; color: #9ca3af; }
.gtable__actions { display: flex; gap: 6px; }

/* Badges */
.bookings-badge {
  display: inline-block; min-width: 28px; text-align: center;
  padding: 3px 10px; border-radius: 99px;
  background: #ede9fe; color: #5b21b6; font-size: 12px; font-weight: 700;
}
.status-badge {
  padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700;
}
.status-badge--active    { background: #d1fae5; color: #065f46; }
.status-badge--suspended { background: #fee2e2; color: #991b1b; }

/* Action buttons */
.action-btn { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.15s; }
.action-btn--view   { background: #dbeafe; }
.action-btn--view:hover   { background: #bfdbfe; }
.action-btn--toggle { background: #fef3c7; }
.action-btn--toggle:hover { background: #fde68a; }
.action-btn--del    { background: #fee2e2; }
.action-btn--del:hover    { background: #fecaca; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; border-top: 1px solid #f0f0f0; }
.pagination button { padding: 7px 16px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination span { font-size: 13px; color: #6b7280; }

/* Buttons */
.btn-ghost  { padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-ghost:hover { background: #e5e7eb; }
.btn-danger { padding: 10px 20px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

/* Detail Drawer */
.drawer-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; display: flex; justify-content: flex-end; }
.drawer {
  width: 100%; max-width: 480px; height: 100%; background: #fff;
  display: flex; flex-direction: column; box-shadow: -8px 0 40px rgba(0,0,0,0.15);
  animation: slideIn 0.25s ease;
}
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
.drawer__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.drawer__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.drawer__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.drawer__close:hover { color: #374151; }
.drawer__body { padding: 24px; overflow-y: auto; flex: 1; }
.drawer__section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin: 24px 0 12px; }
.drawer__empty { color: #9ca3af; font-size: 13px; text-align: center; padding: 20px 0; }

/* Profile card */
.profile-card { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.profile-card__avatar {
  width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
  background: #1a1a2e; color: #c9a84c;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; font-weight: 800;
}
.profile-card__name  { font-size: 17px; font-weight: 800; color: #1a202c; }
.profile-card__email { font-size: 13px; color: #6b7280; margin: 2px 0; }
.profile-card__meta  { font-size: 12px; color: #9ca3af; }

/* Detail stats */
.detail-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 8px; }
.detail-stat {
  background: #f9fafb; border-radius: 10px; padding: 14px 12px; text-align: center;
}
.detail-stat__val   { display: block; font-size: 18px; font-weight: 900; color: #1a1a2e; }
.detail-stat__label { display: block; font-size: 11px; color: #9ca3af; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.4px; }

/* Booking list */
.booking-list { display: flex; flex-direction: column; gap: 10px; }
.booking-item {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 12px 14px; background: #f9fafb; border-radius: 10px; gap: 8px;
}
.booking-item__ref  { font-size: 13px; font-weight: 700; color: #1a202c; }
.booking-item__room { font-size: 12px; color: #6b7280; margin-top: 2px; }
.booking-item__right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.booking-item__amount { font-size: 13px; font-weight: 700; color: #4f46e5; }

/* Booking status */
.bstatus { padding: 2px 8px; border-radius: 99px; font-size: 10px; font-weight: 700; text-transform: capitalize; }
.bstatus--pending    { background: #fef3c7; color: #92400e; }
.bstatus--confirmed  { background: #d1fae5; color: #065f46; }
.bstatus--checked_in { background: #dbeafe; color: #1e40af; }
.bstatus--checked_out{ background: #f3f4f6; color: #374151; }
.bstatus--cancelled  { background: #fee2e2; color: #991b1b; }

/* Delete modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 300; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.modal__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.modal__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.modal__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.modal__body { padding: 24px; }
.modal__footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f0f0f0; }
.delete-warn { color: #ef4444; font-size: 13px; margin-top: 8px; }

/* Spinner */
.spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: spin 0.7s linear infinite; display: inline-block; }
.spinner--sm { width: 13px; height: 13px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .guests-admin { padding: 16px; }
  .gtable th:nth-child(3), .gtable td:nth-child(3),
  .gtable th:nth-child(5), .gtable td:nth-child(5) { display: none; }
  .drawer { max-width: 100%; }
  .detail-stats { grid-template-columns: repeat(3, 1fr); }
}
</style>
