<template>
  <div class="maint-admin">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Maintenance</h1>
        <p class="page-header__sub">{{ pagination.total }} tasks total</p>
      </div>
      <button class="btn-primary" @click="openCreate">+ New Task</button>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="scard" v-for="s in summaryCards" :key="s.label" :class="`scard--${s.color}`">
        <span class="scard__icon">{{ s.icon }}</span>
        <span class="scard__val">{{ s.value }}</span>
        <span class="scard__label">{{ s.label }}</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="filters-bar__search" placeholder="🔍 Search tasks or rooms…" @input="debouncedLoad" />
      <select v-model="filterStatus" class="filters-bar__select" @change="load()">
        <option value="">All Statuses</option>
        <option value="scheduled">Scheduled</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="filterPriority" class="filters-bar__select" @change="load()">
        <option value="">All Priorities</option>
        <option value="urgent">Urgent</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
      </select>
      <select v-model="filterType" class="filters-bar__select" @change="load()">
        <option value="">All Types</option>
        <option value="cleaning">Cleaning</option>
        <option value="repair">Repair</option>
        <option value="inspection">Inspection</option>
        <option value="renovation">Renovation</option>
        <option value="other">Other</option>
      </select>
      <button class="btn-ghost" @click="resetFilters">Reset</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <div v-if="loading" class="table-loading"><div class="spinner"></div> Loading tasks…</div>
      <table v-else class="mtable">
        <thead>
          <tr>
            <th>Task</th>
            <th>Room</th>
            <th>Type</th>
            <th>Priority</th>
            <th>Assigned To</th>
            <th>Scheduled</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!items.length">
            <td colspan="8" class="table-empty">No maintenance tasks found.</td>
          </tr>
          <tr v-for="item in items" :key="item.id" class="mtable__row">
            <td class="mtable__task-cell">
              <p class="mtable__title">{{ item.title }}</p>
              <p class="mtable__desc" v-if="item.description">{{ truncate(item.description, 50) }}</p>
            </td>
            <td>
              <p class="mtable__room-name">{{ item.room?.name }}</p>
              <p class="mtable__room-num">#{{ item.room?.room_number }}</p>
            </td>
            <td><span class="type-badge" :class="`type-badge--${item.type}`">{{ cap(item.type) }}</span></td>
            <td><span class="priority-badge" :class="`priority-badge--${item.priority}`">{{ cap(item.priority) }}</span></td>
            <td>{{ item.assigned_to?.name || '—' }}</td>
            <td>{{ fmtDate(item.scheduled_at) }}</td>
            <td>
              <select class="status-select" :class="`status-select--${item.status}`"
                :value="item.status" @change="quickStatus(item, $event.target.value)">
                <option value="scheduled">Scheduled</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </td>
            <td class="mtable__actions">
              <button class="action-btn action-btn--edit" @click="openEdit(item)" title="Edit">✏️</button>
              <button class="action-btn action-btn--del"  @click="confirmDelete(item)" title="Delete">🗑️</button>
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

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <div class="modal__header">
          <h2>{{ editing ? 'Edit Task' : 'New Maintenance Task' }}</h2>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>
        <div class="modal__body">
          <div class="form-grid">
            <div class="form-field form-field--full">
              <label>Title *</label>
              <input v-model="form.title" placeholder="e.g. Fix AC unit" />
              <span class="form-err" v-if="errors.title">{{ errors.title[0] }}</span>
            </div>
            <div class="form-field">
              <label>Room *</label>
              <select v-model="form.room_id">
                <option value="">Select room…</option>
                <option v-for="r in roomsList" :key="r.id" :value="r.id">{{ r.name }} (#{{ r.room_number }})</option>
              </select>
              <span class="form-err" v-if="errors.room_id">{{ errors.room_id[0] }}</span>
            </div>
            <div class="form-field">
              <label>Assign To</label>
              <select v-model="form.assigned_to">
                <option :value="null">Unassigned</option>
                <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="form-field">
              <label>Type *</label>
              <select v-model="form.type">
                <option value="cleaning">Cleaning</option>
                <option value="repair">Repair</option>
                <option value="inspection">Inspection</option>
                <option value="renovation">Renovation</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-field">
              <label>Priority *</label>
              <select v-model="form.priority">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="form-field">
              <label>Scheduled At *</label>
              <input type="datetime-local" v-model="form.scheduled_at" />
              <span class="form-err" v-if="errors.scheduled_at">{{ errors.scheduled_at[0] }}</span>
            </div>
            <div class="form-field">
              <label>Cost (ETB)</label>
              <input type="number" v-model.number="form.cost" min="0" placeholder="0.00" />
            </div>
            <div class="form-field form-field--full" v-if="editing">
              <label>Status</label>
              <select v-model="form.status">
                <option value="scheduled">Scheduled</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="form-field form-field--full">
              <label>Description</label>
              <textarea v-model="form.description" rows="3" placeholder="Describe the task…"></textarea>
            </div>
            <div class="form-field form-field--full">
              <label>Notes</label>
              <textarea v-model="form.notes" rows="2" placeholder="Internal notes…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="closeModal">Cancel</button>
          <button class="btn-primary" @click="save" :disabled="saving">
            <span v-if="saving" class="spinner spinner--sm"></span>
            {{ saving ? 'Saving…' : (editing ? 'Update Task' : 'Create Task') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget = null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>Delete Task</h2>
          <button class="modal__close" @click="deleteTarget = null">✕</button>
        </div>
        <div class="modal__body">
          <p>Delete <strong>{{ deleteTarget.title }}</strong>?</p>
          <p class="delete-warn">⚠️ This cannot be undone.</p>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="deleteTarget = null">Cancel</button>
          <button class="btn-danger" @click="deleteTask" :disabled="saving">
            <span v-if="saving" class="spinner spinner--sm"></span>
            {{ saving ? 'Deleting…' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import api from '../../plugins/axios'

const items      = ref([])
const loading    = ref(false)
const saving     = ref(false)
const pagination = reactive({ total: 0, current_page: 1, last_page: 1 })
const summary    = reactive({ total: 0, scheduled: 0, in_progress: 0, completed: 0, overdue: 0 })

const search         = ref('')
const filterStatus   = ref('')
const filterPriority = ref('')
const filterType     = ref('')

const showModal   = ref(false)
const editing     = ref(null)
const deleteTarget = ref(null)
const errors      = ref({})

const roomsList = ref([])
const staffList = ref([])

const summaryCards = computed(() => [
  { icon: '📋', label: 'Total',       value: summary.total,       color: 'blue'   },
  { icon: '🗓️', label: 'Scheduled',   value: summary.scheduled,   color: 'yellow' },
  { icon: '🔧', label: 'In Progress', value: summary.in_progress, color: 'purple' },
  { icon: '✅', label: 'Completed',   value: summary.completed,   color: 'green'  },
  { icon: '⚠️', label: 'Overdue',     value: summary.overdue,     color: 'red'    },
])

const defaultForm = () => ({
  title: '', room_id: '', assigned_to: null, type: 'cleaning',
  priority: 'medium', status: 'scheduled', scheduled_at: '',
  cost: null, description: '', notes: '',
})
const form = reactive(defaultForm())

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => load(), 400)
}

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/maintenance', {
      params: {
        page,
        search:   search.value         || undefined,
        status:   filterStatus.value   || undefined,
        priority: filterPriority.value || undefined,
        type:     filterType.value     || undefined,
      }
    })
    items.value = data.data?.items ?? []
    Object.assign(pagination, data.data?.pagination ?? {})
    Object.assign(summary,    data.data?.summary    ?? {})
  } catch { items.value = [] }
  finally { loading.value = false }
}

function resetFilters() {
  search.value = ''; filterStatus.value = ''; filterPriority.value = ''; filterType.value = ''
  load()
}

async function loadDropdowns() {
  const [rooms, staff] = await Promise.allSettled([
    api.get('/admin/maintenance/rooms-list'),
    api.get('/admin/maintenance/staff-list'),
  ])
  if (rooms.status  === 'fulfilled') roomsList.value = rooms.value.data.data ?? []
  if (staff.status  === 'fulfilled') staffList.value = staff.value.data.data ?? []
}

function openCreate() {
  editing.value = null
  Object.assign(form, defaultForm())
  errors.value = {}
  showModal.value = true
  loadDropdowns()
}

function openEdit(item) {
  editing.value = item
  Object.assign(form, {
    title:        item.title,
    room_id:      item.room?.id ?? '',
    assigned_to:  item.assigned_to?.id ?? null,
    type:         item.type,
    priority:     item.priority,
    status:       item.status,
    scheduled_at: item.scheduled_at ? item.scheduled_at.replace(' ', 'T').slice(0, 16) : '',
    cost:         item.cost ?? null,
    description:  item.description ?? '',
    notes:        item.notes ?? '',
  })
  errors.value = {}
  showModal.value = true
  loadDropdowns()
}

function closeModal() { showModal.value = false; editing.value = null }

async function save() {
  saving.value = true; errors.value = {}
  try {
    const payload = { ...form }
    if (editing.value) {
      await api.put(`/admin/maintenance/${editing.value.id}`, payload)
    } else {
      await api.post('/admin/maintenance', payload)
    }
    closeModal()
    load(pagination.current_page)
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
  } finally { saving.value = false }
}

async function quickStatus(item, status) {
  try {
    await api.put(`/admin/maintenance/${item.id}`, { status })
    item.status = status
  } catch {}
}

function confirmDelete(item) { deleteTarget.value = item }

async function deleteTask() {
  saving.value = true
  try {
    await api.delete(`/admin/maintenance/${deleteTarget.value.id}`)
    deleteTarget.value = null
    load(pagination.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not delete task.')
  } finally { saving.value = false }
}

function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, ' ') : '' }
function truncate(s, n) { return s && s.length > n ? s.slice(0, n).trimEnd() + '…' : (s || '') }
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-ET', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

load()
</script>

<style scoped>
.maint-admin { padding: 28px 32px; max-width: 1300px; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 2px; }
.page-header__sub   { font-size: 13px; color: #9ca3af; }

/* Summary cards */
.summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 24px; }
.scard { border-radius: 14px; padding: 18px 16px; display: flex; flex-direction: column; gap: 6px; }
.scard--blue   { background: #dbeafe; }
.scard--yellow { background: #fef3c7; }
.scard--purple { background: #ede9fe; }
.scard--green  { background: #d1fae5; }
.scard--red    { background: #fee2e2; }
.scard__icon  { font-size: 1.4rem; }
.scard__val   { font-size: 1.6rem; font-weight: 900; color: #1a202c; }
.scard__label { font-size: 11.5px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

/* Filters */
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

/* Table */
.table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); overflow: hidden; }
.table-loading { display: flex; align-items: center; gap: 10px; padding: 40px; justify-content: center; color: #6b7280; }
.mtable { width: 100%; border-collapse: collapse; }
.mtable thead tr { background: #f9fafb; border-bottom: 1px solid #f0f0f0; }
.mtable th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; text-align: left; white-space: nowrap; }
.mtable td { padding: 14px 16px; font-size: 13.5px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
.mtable__row:hover td { background: #fafafa; }
.table-empty { text-align: center; color: #9ca3af; padding: 48px !important; }
.mtable__task-cell { max-width: 220px; }
.mtable__title { font-weight: 700; color: #1a202c; font-size: 14px; }
.mtable__desc  { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.mtable__room-name { font-weight: 600; color: #1a202c; font-size: 13px; }
.mtable__room-num  { font-size: 11px; color: #9ca3af; }
.mtable__actions { display: flex; gap: 6px; }

/* Type badges */
.type-badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.type-badge--cleaning    { background: #dbeafe; color: #1e40af; }
.type-badge--repair      { background: #fee2e2; color: #991b1b; }
.type-badge--inspection  { background: #fef3c7; color: #92400e; }
.type-badge--renovation  { background: #ede9fe; color: #5b21b6; }
.type-badge--other       { background: #f3f4f6; color: #374151; }

/* Priority badges */
.priority-badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.priority-badge--low    { background: #f3f4f6; color: #6b7280; }
.priority-badge--medium { background: #fef3c7; color: #92400e; }
.priority-badge--high   { background: #fed7aa; color: #9a3412; }
.priority-badge--urgent { background: #fee2e2; color: #991b1b; }

/* Status select */
.status-select {
  padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
  border: 1.5px solid #e5e7eb; cursor: pointer; outline: none;
}
.status-select--scheduled   { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
.status-select--in_progress { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
.status-select--completed   { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
.status-select--cancelled   { background: #f3f4f6; color: #6b7280; border-color: #d1d5db; }

/* Action buttons */
.action-btn { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.15s; }
.action-btn--edit { background: #ede9fe; } .action-btn--edit:hover { background: #ddd6fe; }
.action-btn--del  { background: #fee2e2; } .action-btn--del:hover  { background: #fecaca; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 16px; border-top: 1px solid #f0f0f0; }
.pagination button { padding: 7px 16px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination span { font-size: 13px; color: #6b7280; }

/* Buttons */
.btn-primary { padding: 10px 20px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s; }
.btn-primary:hover:not(:disabled) { background: #4338ca; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-ghost  { padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-ghost:hover { background: #e5e7eb; }
.btn-danger { padding: 10px 20px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

/* Modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 200; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 640px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.modal--sm { max-width: 440px; }
.modal__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.modal__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.modal__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; }
.modal__close:hover { color: #374151; }
.modal__body { padding: 24px; overflow-y: auto; flex: 1; }
.modal__footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f0f0f0; }

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-field { display: flex; flex-direction: column; gap: 6px; }
.form-field--full { grid-column: 1 / -1; }
.form-field label { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.4px; }
.form-field input, .form-field select, .form-field textarea {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #1a202c; outline: none; font-family: inherit; transition: border-color 0.15s;
}
.form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color: #4f46e5; }
.form-err { font-size: 12px; color: #ef4444; }
.delete-warn { color: #ef4444; font-size: 13px; margin-top: 8px; }

/* Spinner */
.spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: spin 0.7s linear infinite; display: inline-block; }
.spinner--sm { width: 13px; height: 13px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 1024px) {
  .summary-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .maint-admin { padding: 16px; }
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
  .form-grid { grid-template-columns: 1fr; }
  .mtable th:nth-child(5), .mtable td:nth-child(5),
  .mtable th:nth-child(6), .mtable td:nth-child(6) { display: none; }
}
</style>