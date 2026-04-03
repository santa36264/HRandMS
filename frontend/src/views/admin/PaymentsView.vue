<template>
  <div class="payments-admin">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Payments</h1>
        <p class="page-header__sub">{{ pagination.total }} transactions total</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="scard scard--green">
        <span class="scard__icon">💰</span>
        <span class="scard__val">ETB {{ fmt(summary.total_revenue) }}</span>
        <span class="scard__label">Total Revenue</span>
      </div>
      <div class="scard scard--blue">
        <span class="scard__icon">📋</span>
        <span class="scard__val">{{ summary.total_count }}</span>
        <span class="scard__label">Total Transactions</span>
      </div>
      <div class="scard scard--yellow">
        <span class="scard__icon">⏳</span>
        <span class="scard__val">{{ summary.pending_count }}</span>
        <span class="scard__label">Pending</span>
      </div>
      <div class="scard scard--red">
        <span class="scard__icon">❌</span>
        <span class="scard__val">{{ summary.failed_count }}</span>
        <span class="scard__label">Failed</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <select v-model="filterStatus" class="filters-bar__select" @change="load()">
        <option value="">All Statuses</option>
        <option value="completed">Completed</option>
        <option value="pending">Pending</option>
        <option value="failed">Failed</option>
        <option value="refunded">Refunded</option>
      </select>
      <select v-model="filterGateway" class="filters-bar__select" @change="load()">
        <option value="">All Gateways</option>
        <option value="chapa">Chapa</option>
      </select>
      <input type="date" v-model="filterFrom"  class="filters-bar__date" @change="load()" placeholder="From" />
      <input type="date" v-model="filterUntil" class="filters-bar__date" @change="load()" placeholder="Until" />
      <button class="btn-ghost" @click="resetFilters">Reset</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <div v-if="loading" class="table-loading"><div class="spinner"></div> Loading payments…</div>
      <table v-else class="ptable">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Guest</th>
            <th>Booking</th>
            <th>Gateway</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!payments.length">
            <td colspan="8" class="table-empty">No payments found.</td>
          </tr>
          <tr v-for="p in payments" :key="p.id" class="ptable__row">
            <td class="ptable__txid">{{ p.transaction_id || '—' }}</td>
            <td>
              <p class="ptable__name">{{ p.user?.name }}</p>
              <p class="ptable__email">{{ p.user?.email }}</p>
            </td>
            <td>
              <p class="ptable__ref">{{ p.booking?.reference }}</p>
              <p class="ptable__room">{{ p.booking?.room }}</p>
            </td>
            <td><span class="gateway-badge" :class="`gateway-badge--${p.gateway}`">{{ gatewayLabel(p.gateway) }}</span></td>
            <td class="ptable__amount">ETB {{ fmt(p.amount) }}</td>
            <td><span class="status-badge" :class="`status-badge--${p.status}`">{{ cap(p.status) }}</span></td>
            <td>{{ fmtDate(p.paid_at || p.created_at) }}</td>
            <td class="ptable__actions">
              <button class="action-btn action-btn--view" @click="openDetail(p)" title="View">👁️</button>
              <button v-if="p.status === 'pending'" class="action-btn action-btn--verify" @click="verifyPayment(p)" title="Verify">✅</button>
              <button v-if="p.status === 'completed'" class="action-btn action-btn--refund" @click="openRefund(p)" title="Refund">↩️</button>
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
          <h2>Payment Details</h2>
          <button class="drawer__close" @click="detail = null">✕</button>
        </div>
        <div class="drawer__body" v-if="detailData">
          <div class="detail-row"><span>Transaction ID</span><strong>{{ detailData.transaction_id || '—' }}</strong></div>
          <div class="detail-row"><span>Amount</span><strong class="detail-amount">ETB {{ fmt(detailData.amount) }}</strong></div>
          <div class="detail-row"><span>Status</span><span class="status-badge" :class="`status-badge--${detailData.status}`">{{ cap(detailData.status) }}</span></div>
          <div class="detail-row"><span>Gateway</span><span>{{ gatewayLabel(detailData.gateway) }}</span></div>
          <div class="detail-row"><span>Method</span><span>{{ detailData.method || '—' }}</span></div>
          <div class="detail-row"><span>Guest</span><strong>{{ detailData.user?.name }}</strong></div>
          <div class="detail-row"><span>Email</span><span>{{ detailData.user?.email }}</span></div>
          <div class="detail-row"><span>Booking Ref</span><span>{{ detailData.booking?.reference }}</span></div>
          <div class="detail-row"><span>Room</span><span>{{ detailData.booking?.room }}</span></div>
          <div class="detail-row"><span>Paid At</span><span>{{ fmtDate(detailData.paid_at) }}</span></div>
          <div class="detail-row"><span>Created</span><span>{{ fmtDate(detailData.created_at) }}</span></div>
          <div v-if="detailData.notes" class="detail-row detail-row--block"><span>Notes</span><p>{{ detailData.notes }}</p></div>
        </div>
      </div>
    </div>

    <!-- Refund Modal -->
    <div v-if="refundTarget" class="modal-backdrop" @click.self="refundTarget = null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>Process Refund</h2>
          <button class="modal__close" @click="refundTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p class="refund-info">Refunding payment for <strong>{{ refundTarget.user?.name }}</strong></p>
          <p class="refund-max">Max refundable: <strong>ETB {{ fmt(refundTarget.amount) }}</strong></p>
          <div class="form-field" style="margin-top:16px">
            <label>Refund Amount (ETB) *</label>
            <input type="number" v-model.number="refundForm.amount" :max="refundTarget.amount" min="1" />
          </div>
          <div class="form-field" style="margin-top:12px">
            <label>Reason</label>
            <textarea v-model="refundForm.reason" rows="2" placeholder="Reason for refund…"></textarea>
          </div>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="refundTarget = null">Cancel</button>
          <button class="btn-danger" @click="submitRefund" :disabled="saving">
            <span v-if="saving" class="spinner spinner--sm"></span>
            {{ saving ? 'Processing…' : 'Refund' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../../plugins/axios'

const payments     = ref([])
const loading      = ref(false)
const saving       = ref(false)
const pagination   = reactive({ total: 0, current_page: 1, last_page: 1 })
const summary      = reactive({ total_revenue: 0, total_count: 0, pending_count: 0, failed_count: 0 })

const filterStatus  = ref('')
const filterGateway = ref('')
const filterFrom    = ref('')
const filterUntil   = ref('')

const detail     = ref(false)
const detailData = ref(null)
const refundTarget = ref(null)
const refundForm   = reactive({ amount: 0, reason: '' })

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/payments', {
      params: {
        page,
        status:  filterStatus.value  || undefined,
        gateway: filterGateway.value || undefined,
        from:    filterFrom.value    || undefined,
        until:   filterUntil.value   || undefined,
      }
    })
    payments.value = data.data?.payments ?? []
    Object.assign(pagination, data.data?.pagination ?? {})
    Object.assign(summary,    data.data?.summary    ?? {})
  } catch { payments.value = [] }
  finally { loading.value = false }
}

function resetFilters() {
  filterStatus.value = ''; filterGateway.value = ''
  filterFrom.value = ''; filterUntil.value = ''
  load()
}

async function openDetail(p) {
  detail.value = true
  detailData.value = null
  try {
    const { data } = await api.get(`/admin/payments/${p.id}`)
    detailData.value = data.data
  } catch { detail.value = false }
}

async function verifyPayment(p) {
  try {
    await api.post(`/admin/payments/${p.id}/verify`)
    load(pagination.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Verification failed.')
  }
}

function openRefund(p) {
  refundTarget.value = p
  refundForm.amount = p.amount
  refundForm.reason = ''
}

async function submitRefund() {
  saving.value = true
  try {
    await api.post(`/admin/payments/${refundTarget.value.id}/refund`, {
      amount: refundForm.amount,
      reason: refundForm.reason,
    })
    refundTarget.value = null
    load(pagination.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Refund failed.')
  } finally { saving.value = false }
}

function fmt(n) { return Number(n || 0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-ET', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function gatewayLabel(g) {
  return g === 'chapa' ? 'Chapa' : (g || '—')
}

load()
</script>

<style scoped>
.payments-admin { padding: 28px 32px; max-width: 1300px; }

.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 2px; }
.page-header__sub   { font-size: 13px; color: #9ca3af; }

.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.scard { border-radius: 14px; padding: 20px 22px; display: flex; flex-direction: column; gap: 6px; }
.scard--green  { background: #d1fae5; }
.scard--blue   { background: #dbeafe; }
.scard--yellow { background: #fef3c7; }
.scard--red    { background: #fee2e2; }
.scard__icon  { font-size: 1.5rem; }
.scard__val   { font-size: 1.5rem; font-weight: 900; color: #1a202c; }
.scard__label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

.filters-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filters-bar__select, .filters-bar__date {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #374151; background: #fff; outline: none; cursor: pointer;
}
.filters-bar__date { cursor: text; }

.table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); overflow: hidden; }
.table-loading { display: flex; align-items: center; gap: 10px; padding: 40px; justify-content: center; color: #6b7280; }
.ptable { width: 100%; border-collapse: collapse; }
.ptable thead tr { background: #f9fafb; border-bottom: 1px solid #f0f0f0; }
.ptable th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; text-align: left; white-space: nowrap; }
.ptable td { padding: 14px 16px; font-size: 13.5px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
.ptable__row:hover td { background: #fafafa; }
.table-empty { text-align: center; color: #9ca3af; padding: 48px !important; }
.ptable__txid  { font-family: monospace; font-size: 12px; color: #6b7280; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ptable__name  { font-weight: 700; color: #1a202c; font-size: 13.5px; }
.ptable__email { font-size: 11.5px; color: #9ca3af; }
.ptable__ref   { font-weight: 600; font-size: 13px; color: #1a202c; }
.ptable__room  { font-size: 11.5px; color: #9ca3af; }
.ptable__amount { font-weight: 800; color: #065f46; }
.ptable__actions { display: flex; gap: 6px; }

.gateway-badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.gateway-badge--telebirr { background: #dbeafe; color: #1e40af; }
.gateway-badge--cbe_birr { background: #d1fae5; color: #065f46; }

.status-badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.status-badge--completed { background: #d1fae5; color: #065f46; }
.status-badge--pending   { background: #fef3c7; color: #92400e; }
.status-badge--failed    { background: #fee2e2; color: #991b1b; }
.status-badge--refunded  { background: #ede9fe; color: #5b21b6; }

.action-btn { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.15s; }
.action-btn--view   { background: #dbeafe; } .action-btn--view:hover   { background: #bfdbfe; }
.action-btn--verify { background: #d1fae5; } .action-btn--verify:hover { background: #a7f3d0; }
.action-btn--refund { background: #ede9fe; } .action-btn--refund:hover { background: #ddd6fe; }

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
.drawer { width: 100%; max-width: 420px; height: 100%; background: #fff; display: flex; flex-direction: column; box-shadow: -8px 0 40px rgba(0,0,0,0.15); animation: slideIn 0.25s ease; }
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
.drawer__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.drawer__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.drawer__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.drawer__body { padding: 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 12px; }
.detail-row { display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; padding: 8px 0; border-bottom: 1px solid #f9fafb; }
.detail-row span:first-child { color: #6b7280; font-weight: 600; }
.detail-row strong { color: #1a202c; }
.detail-amount { color: #065f46; font-size: 16px; }
.detail-row--block { flex-direction: column; align-items: flex-start; gap: 4px; }
.detail-row--block p { font-size: 13px; color: #374151; margin: 0; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 300; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 680px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.modal--sm { max-width: 440px; }
.modal__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.modal__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.modal__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.modal__body { padding: 24px; overflow-y: auto; flex: 1; }
.modal__footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f0f0f0; }
.refund-info { font-size: 14px; color: #374151; margin-bottom: 4px; }
.refund-max  { font-size: 13px; color: #6b7280; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field label { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.4px; }
.form-field input, .form-field textarea { padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; font-size: 14px; color: #1a202c; outline: none; font-family: inherit; }
.form-field input:focus, .form-field textarea:focus { border-color: #4f46e5; }

.spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: spin 0.7s linear infinite; display: inline-block; }
.spinner--sm { width: 13px; height: 13px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .payments-admin { padding: 16px; }
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
  .ptable th:nth-child(3), .ptable td:nth-child(3),
  .ptable th:nth-child(7), .ptable td:nth-child(7) { display: none; }
}
</style>
