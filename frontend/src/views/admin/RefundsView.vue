<template>
  <div class="refunds-page">
    <div class="page-header">
      <h1 class="page-header__title">Refunds</h1>
      <p class="page-header__sub">{{ pagination.total }} refunded transactions</p>
    </div>

    <div class="summary-grid">
      <div class="scard scard--purple">
        <span class="scard__icon">↩️</span>
        <span class="scard__val">{{ pagination.total }}</span>
        <span class="scard__label">Total Refunds</span>
      </div>
      <div class="scard scard--red">
        <span class="scard__icon">💸</span>
        <span class="scard__val">ETB {{ fmt(totalRefunded) }}</span>
        <span class="scard__label">Total Refunded</span>
      </div>
    </div>

    <div class="table-wrap">
      <div v-if="loading" class="table-loading"><div class="spinner"></div> Loading refunds…</div>
      <table v-else class="rtable">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Guest</th>
            <th>Booking</th>
            <th>Amount</th>
            <th>Gateway</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!refunds.length">
            <td colspan="6" class="table-empty">No refunds found.</td>
          </tr>
          <tr v-for="r in refunds" :key="r.id">
            <td class="rtable__txid">{{ r.transaction_id || '—' }}</td>
            <td>
              <p class="rtable__name">{{ r.user?.name ?? '—' }}</p>
              <p class="rtable__email">{{ r.user?.email ?? '' }}</p>
            </td>
            <td>
              <p class="rtable__ref">{{ r.booking?.reference ?? '—' }}</p>
              <p class="rtable__room">{{ r.booking?.room ?? '' }}</p>
            </td>
            <td class="rtable__amount">ETB {{ fmt(r.amount) }}</td>
            <td><span class="gateway-badge">{{ r.gateway ?? '—' }}</span></td>
            <td>{{ fmtDate(r.paid_at || r.created_at) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="pagination.last_page > 1">
        <button :disabled="pagination.current_page === 1" @click="load(pagination.current_page - 1)">← Prev</button>
        <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <button :disabled="pagination.current_page === pagination.last_page" @click="load(pagination.current_page + 1)">Next →</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import api from '../../plugins/axios'

const refunds  = ref([])
const loading  = ref(false)
const pagination = reactive({ total: 0, current_page: 1, last_page: 1 })

const totalRefunded = computed(() => refunds.value.reduce((s, r) => s + (r.amount ?? 0), 0))

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/payments', { params: { status: 'refunded', page, per_page: 20 } })
    refunds.value = data.data?.payments ?? []
    Object.assign(pagination, data.data?.pagination ?? {})
  } catch { refunds.value = [] }
  finally { loading.value = false }
}

function fmt(n) { return Number(n || 0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-ET', { year: 'numeric', month: 'short', day: 'numeric' })
}

load()
</script>

<style scoped>
.refunds-page { padding: 28px 32px; max-width: 1100px; }
.page-header { margin-bottom: 24px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: var(--text); margin-bottom: 2px; }
.page-header__sub   { font-size: 13px; color: var(--text-muted); }

.summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; max-width: 480px; }
.scard { border-radius: 14px; padding: 20px 22px; display: flex; flex-direction: column; gap: 6px; }
.scard--purple { background: #ede9fe; }
.scard--red    { background: #fee2e2; }
.scard__icon  { font-size: 1.5rem; }
.scard__val   { font-size: 1.5rem; font-weight: 900; color: var(--text); }
.scard__label { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

.table-wrap { background: var(--bg-card); border-radius: 14px; box-shadow: var(--shadow-sm); overflow: hidden; }
.table-loading { display: flex; align-items: center; gap: 10px; padding: 40px; justify-content: center; color: var(--text-muted); }
.rtable { width: 100%; border-collapse: collapse; }
.rtable thead tr { background: var(--bg-soft); border-bottom: 1px solid var(--border); }
.rtable th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); text-align: left; }
.rtable td { padding: 14px 16px; font-size: 13.5px; color: var(--text-soft); border-bottom: 1px solid var(--border-soft); vertical-align: middle; }
.table-empty { text-align: center; color: var(--text-muted); padding: 48px !important; }
.rtable__txid  { font-family: monospace; font-size: 12px; color: var(--text-muted); }
.rtable__name  { font-weight: 700; color: var(--text); font-size: 13.5px; }
.rtable__email { font-size: 11.5px; color: var(--text-muted); }
.rtable__ref   { font-weight: 600; font-size: 13px; color: var(--text); }
.rtable__room  { font-size: 11.5px; color: var(--text-muted); }
.rtable__amount { font-weight: 800; color: #991b1b; }
.gateway-badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; background: #ede9fe; color: #5b21b6; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; border-top: 1px solid var(--border); }
.pagination button { padding: 7px 16px; border: 1.5px solid var(--border); border-radius: 8px; background: var(--bg-card); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; }
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination span { font-size: 13px; color: var(--text-muted); }

.spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--border); border-top-color: var(--indigo); animation: spin 0.7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
