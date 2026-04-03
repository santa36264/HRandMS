<template>
  <div class="reviews-admin">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Reviews</h1>
        <p class="page-header__sub">{{ pagination.total }} reviews total</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="scard scard--blue">
        <span class="scard__icon">📋</span>
        <span class="scard__val">{{ summary.total }}</span>
        <span class="scard__label">Total Reviews</span>
      </div>
      <div class="scard scard--green">
        <span class="scard__icon">✅</span>
        <span class="scard__val">{{ summary.approved }}</span>
        <span class="scard__label">Approved</span>
      </div>
      <div class="scard scard--yellow">
        <span class="scard__icon">⏳</span>
        <span class="scard__val">{{ summary.pending }}</span>
        <span class="scard__label">Pending</span>
      </div>
      <div class="scard scard--gold">
        <span class="scard__icon">⭐</span>
        <span class="scard__val">{{ summary.avg || '—' }}</span>
        <span class="scard__label">Avg Rating</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="filters-bar__search" placeholder="🔍 Search reviews, guests…" @input="debouncedLoad" />
      <select v-model="filterApproved" class="filters-bar__select" @change="load()">
        <option value="">All Reviews</option>
        <option value="1">Approved</option>
        <option value="0">Pending</option>
      </select>
      <select v-model="filterRating" class="filters-bar__select" @change="load()">
        <option value="">All Ratings</option>
        <option v-for="n in [5,4,3,2,1]" :key="n" :value="n">{{ '★'.repeat(n) }} ({{ n }})</option>
      </select>
      <select v-model="sortDir" class="filters-bar__select" @change="load()">
        <option value="desc">Newest First</option>
        <option value="asc">Oldest First</option>
      </select>
      <button class="btn-ghost" @click="resetFilters">Reset</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <div v-if="loading" class="table-loading"><div class="spinner"></div> Loading reviews…</div>
      <table v-else class="rtable">
        <thead>
          <tr>
            <th>Guest</th>
            <th>Room</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!reviews.length">
            <td colspan="7" class="table-empty">No reviews found.</td>
          </tr>
          <tr v-for="r in reviews" :key="r.id" class="rtable__row">
            <td class="rtable__guest-cell">
              <div class="rtable__avatar">{{ r.user?.name?.[0]?.toUpperCase() }}</div>
              <div>
                <p class="rtable__name">{{ r.user?.name }}</p>
                <p class="rtable__email">{{ r.user?.email }}</p>
              </div>
            </td>
            <td>
              <p class="rtable__room-name">{{ r.room?.name }}</p>
              <p class="rtable__room-num">#{{ r.room?.room_number }}</p>
            </td>
            <td>
              <div class="stars">
                <span v-for="n in 5" :key="n" :class="n <= r.rating ? 'star--on' : 'star--off'">★</span>
              </div>
              <p class="rtable__rating-num">{{ r.rating }}/5</p>
            </td>
            <td class="rtable__review-cell">
              <p class="rtable__title" v-if="r.title">{{ r.title }}</p>
              <p class="rtable__comment">{{ truncate(r.comment, 80) }}</p>
            </td>
            <td>{{ fmtDate(r.created_at) }}</td>
            <td>
              <span class="status-badge" :class="r.is_approved ? 'status-badge--approved' : 'status-badge--pending'">
                {{ r.is_approved ? 'Approved' : 'Pending' }}
              </span>
            </td>
            <td class="rtable__actions">
              <button v-if="!r.is_approved" class="action-btn action-btn--approve" @click="approve(r)" title="Approve">✅</button>
              <button v-if="r.is_approved"  class="action-btn action-btn--reject"  @click="reject(r)"  title="Reject">🚫</button>
              <button class="action-btn action-btn--view" @click="openDetail(r)" title="View">👁️</button>
              <button class="action-btn action-btn--del"  @click="confirmDelete(r)" title="Delete">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>

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
          <h2>Review Details</h2>
          <button class="drawer__close" @click="detail = null">✕</button>
        </div>
        <div class="drawer__body" v-if="detailData">
          <div class="profile-card">
            <div class="profile-card__avatar">{{ detailData.user?.name?.[0]?.toUpperCase() }}</div>
            <div>
              <h3 class="profile-card__name">{{ detailData.user?.name }}</h3>
              <p class="profile-card__email">{{ detailData.user?.email }}</p>
            </div>
          </div>

          <div class="detail-row"><span>Room</span><strong>{{ detailData.room?.name }} (#{{ detailData.room?.room_number }})</strong></div>
          <div class="detail-row">
            <span>Overall Rating</span>
            <div class="stars stars--lg">
              <span v-for="n in 5" :key="n" :class="n <= detailData.rating ? 'star--on' : 'star--off'">★</span>
            </div>
          </div>
          <div v-if="detailData.cleanliness_rating" class="detail-row"><span>Cleanliness</span><span>{{ detailData.cleanliness_rating }}/5</span></div>
          <div v-if="detailData.service_rating"     class="detail-row"><span>Service</span><span>{{ detailData.service_rating }}/5</span></div>
          <div v-if="detailData.location_rating"    class="detail-row"><span>Location</span><span>{{ detailData.location_rating }}/5</span></div>
          <div v-if="detailData.title" class="detail-row detail-row--block"><span>Title</span><p>{{ detailData.title }}</p></div>
          <div class="detail-row detail-row--block"><span>Comment</span><p>{{ detailData.comment }}</p></div>
          <div class="detail-row"><span>Status</span>
            <span class="status-badge" :class="detailData.is_approved ? 'status-badge--approved' : 'status-badge--pending'">
              {{ detailData.is_approved ? 'Approved' : 'Pending' }}
            </span>
          </div>
          <div class="detail-row"><span>Submitted</span><span>{{ fmtDate(detailData.created_at) }}</span></div>

          <div class="drawer__actions">
            <button v-if="!detailData.is_approved" class="btn-approve" @click="approve(detailData); detail = null">✅ Approve</button>
            <button v-if="detailData.is_approved"  class="btn-reject"  @click="reject(detailData);  detail = null">🚫 Reject</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
      <div class="modal">
        <div class="modal__header">
          <h2>Delete Review</h2>
          <button class="modal__close" @click="deleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p>Delete this review by <strong>{{ deleteTarget.user?.name }}</strong>?</p>
          <p class="delete-warn">⚠️ This cannot be undone.</p>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="deleteTarget = null">Cancel</button>
          <button class="btn-danger" @click="deleteReview" :disabled="saving">
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

const reviews    = ref([])
const loading    = ref(false)
const saving     = ref(false)
const pagination = reactive({ total: 0, current_page: 1, last_page: 1 })
const summary    = reactive({ total: 0, approved: 0, pending: 0, avg: 0 })

const search         = ref('')
const filterApproved = ref('')
const filterRating   = ref('')
const sortDir        = ref('desc')

const detail      = ref(false)
const detailData  = ref(null)
const deleteTarget = ref(null)

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => load(), 400)
}

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/reviews', {
      params: {
        page,
        search:   search.value         || undefined,
        approved: filterApproved.value !== '' ? filterApproved.value : undefined,
        rating:   filterRating.value   || undefined,
        sort_dir: sortDir.value,
      }
    })
    reviews.value = data.data?.reviews ?? []
    Object.assign(pagination, data.data?.pagination ?? {})
    Object.assign(summary,    data.data?.summary    ?? {})
  } catch { reviews.value = [] }
  finally { loading.value = false }
}

function resetFilters() {
  search.value = ''; filterApproved.value = ''; filterRating.value = ''; sortDir.value = 'desc'
  load()
}

function openDetail(r) {
  detailData.value = r
  detail.value = true
}

async function approve(r) {
  try {
    await api.patch(`/admin/reviews/${r.id}/approve`)
    r.is_approved = true
    Object.assign(summary, { approved: summary.approved + 1, pending: Math.max(0, summary.pending - 1) })
  } catch (e) { alert(e.response?.data?.message ?? 'Failed.') }
}

async function reject(r) {
  try {
    await api.patch(`/admin/reviews/${r.id}/reject`)
    r.is_approved = false
    Object.assign(summary, { approved: Math.max(0, summary.approved - 1), pending: summary.pending + 1 })
  } catch (e) { alert(e.response?.data?.message ?? 'Failed.') }
}

function confirmDelete(r) { deleteTarget.value = r }

async function deleteReview() {
  saving.value = true
  try {
    await api.delete(`/admin/reviews/${deleteTarget.value.id}`)
    deleteTarget.value = null
    load(pagination.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not delete review.')
  } finally { saving.value = false }
}

function truncate(s, n) { return s && s.length > n ? s.slice(0, n).trimEnd() + '…' : (s || '') }
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-ET', { year: 'numeric', month: 'short', day: 'numeric' })
}

load()
</script>

<style scoped>
.reviews-admin { padding: 28px 32px; max-width: 1300px; }

.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 2px; }
.page-header__sub   { font-size: 13px; color: #9ca3af; }

.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
.scard { border-radius: 14px; padding: 18px 16px; display: flex; flex-direction: column; gap: 6px; }
.scard--blue   { background: #dbeafe; }
.scard--green  { background: #d1fae5; }
.scard--yellow { background: #fef3c7; }
.scard--gold   { background: #fef9c3; }
.scard__icon  { font-size: 1.4rem; }
.scard__val   { font-size: 1.6rem; font-weight: 900; color: #1a202c; }
.scard__label { font-size: 11.5px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

.filters-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filters-bar__search {
  flex: 1; min-width: 200px; padding: 9px 14px;
  border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; outline: none;
}
.filters-bar__search:focus { border-color: #4f46e5; }
.filters-bar__select {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #374151; background: #fff; outline: none; cursor: pointer;
}

.table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); overflow: hidden; }
.table-loading { display: flex; align-items: center; gap: 10px; padding: 40px; justify-content: center; color: #6b7280; }
.rtable { width: 100%; border-collapse: collapse; }
.rtable thead tr { background: #f9fafb; border-bottom: 1px solid #f0f0f0; }
.rtable th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; text-align: left; white-space: nowrap; }
.rtable td { padding: 14px 16px; font-size: 13.5px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
.rtable__row:hover td { background: #fafafa; }
.table-empty { text-align: center; color: #9ca3af; padding: 48px !important; }

.rtable__guest-cell { display: flex; align-items: center; gap: 10px; }
.rtable__avatar {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  background: #1a1a2e; color: #c9a84c;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800;
}
.rtable__name  { font-weight: 700; color: #1a202c; font-size: 13.5px; }
.rtable__email { font-size: 11.5px; color: #9ca3af; }
.rtable__room-name { font-weight: 600; color: #1a202c; font-size: 13px; }
.rtable__room-num  { font-size: 11px; color: #9ca3af; }
.rtable__review-cell { max-width: 260px; }
.rtable__title   { font-weight: 700; color: #1a202c; font-size: 13px; margin-bottom: 2px; }
.rtable__comment { font-size: 12.5px; color: #6b7280; line-height: 1.5; }
.rtable__rating-num { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.rtable__actions { display: flex; gap: 6px; }

.stars { display: flex; gap: 1px; }
.stars--lg { font-size: 18px; }
.star--on  { color: #c9a84c; }
.star--off { color: #d1d5db; }

.status-badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.status-badge--approved { background: #d1fae5; color: #065f46; }
.status-badge--pending  { background: #fef3c7; color: #92400e; }

.action-btn { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.15s; }
.action-btn--approve { background: #d1fae5; } .action-btn--approve:hover { background: #a7f3d0; }
.action-btn--reject  { background: #fee2e2; } .action-btn--reject:hover  { background: #fecaca; }
.action-btn--view    { background: #dbeafe; } .action-btn--view:hover    { background: #bfdbfe; }
.action-btn--del     { background: #fee2e2; } .action-btn--del:hover     { background: #fecaca; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; border-top: 1px solid #f0f0f0; }
.pagination button { padding: 7px 16px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination span { font-size: 13px; color: #6b7280; }

.btn-ghost  { padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-ghost:hover { background: #e5e7eb; }
.btn-danger { padding: 10px 20px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

.drawer-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 200; display: flex; justify-content: flex-end; }
.drawer { width: 100%; max-width: 460px; height: 100%; background: #fff; display: flex; flex-direction: column; box-shadow: -8px 0 40px rgba(0,0,0,0.15); animation: slideIn 0.25s ease; }
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
.drawer__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.drawer__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.drawer__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.drawer__body { padding: 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 10px; }
.drawer__actions { display: flex; gap: 10px; margin-top: 16px; }

.profile-card { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
.profile-card__avatar {
  width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0;
  background: #1a1a2e; color: #c9a84c;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; font-weight: 800;
}
.profile-card__name  { font-size: 16px; font-weight: 800; color: #1a202c; }
.profile-card__email { font-size: 12.5px; color: #6b7280; }

.detail-row { display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; padding: 8px 0; border-bottom: 1px solid #f9fafb; }
.detail-row span:first-child { color: #6b7280; font-weight: 600; }
.detail-row strong { color: #1a202c; }
.detail-row--block { flex-direction: column; align-items: flex-start; gap: 4px; }
.detail-row--block p { font-size: 13px; color: #374151; margin: 0; line-height: 1.6; }

.btn-approve { flex: 1; padding: 10px; background: #d1fae5; color: #065f46; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn-approve:hover { background: #a7f3d0; }
.btn-reject  { flex: 1; padding: 10px; background: #fee2e2; color: #991b1b; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn-reject:hover  { background: #fecaca; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 300; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.modal__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.modal__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.modal__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.modal__body { padding: 24px; }
.modal__footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f0f0f0; }
.delete-warn { color: #ef4444; font-size: 13px; margin-top: 8px; }

.spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: spin 0.7s linear infinite; display: inline-block; }
.spinner--sm { width: 13px; height: 13px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .reviews-admin { padding: 16px; }
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
  .rtable th:nth-child(2), .rtable td:nth-child(2),
  .rtable th:nth-child(5), .rtable td:nth-child(5) { display: none; }
}
</style>
