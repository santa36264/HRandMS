<template>
  <div class="rooms-admin">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Rooms Management</h1>
        <p class="page-header__sub">{{ pagination.total }} rooms total</p>
      </div>
      <button class="btn-primary" @click="openCreate">+ Add Room</button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="filters-bar__search" placeholder="🔍 Search rooms…" @input="debouncedLoad" />
      <select v-model="filterType" class="filters-bar__select" @change="loadRooms">
        <option value="">All Types</option>
        <option v-for="t in TYPES" :key="t" :value="t">{{ cap(t) }}</option>
      </select>
      <select v-model="filterStatus" class="filters-bar__select" @change="loadRooms">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="occupied">Occupied</option>
        <option value="maintenance">Maintenance</option>
      </select>
      <button class="btn-ghost" @click="resetFilters">Reset</button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <div v-if="loading" class="table-loading">
        <div class="spinner"></div> Loading rooms…
      </div>
      <table v-else class="rooms-table">
        <thead>
          <tr>
            <th>Room</th>
            <th>Type</th>
            <th>Floor</th>
            <th>Capacity</th>
            <th>Price/Night</th>
            <th>Status</th>
            <th>Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!rooms.length">
            <td colspan="8" class="table-empty">No rooms found.</td>
          </tr>
          <tr v-for="room in rooms" :key="room.id" class="rooms-table__row">
            <td class="rooms-table__room-cell">
              <img :src="room.images?.[0] || fallback" :alt="room.name" class="rooms-table__thumb" @error="e=>e.target.src=fallback" />
              <div>
                <p class="rooms-table__name">{{ room.name }}</p>
                <p class="rooms-table__num">#{{ room.room_number }}</p>
              </div>
            </td>
            <td><span class="badge" :class="`badge--${room.type}`">{{ cap(room.type) }}</span></td>
            <td>{{ room.floor }}</td>
            <td>👥 {{ room.capacity }}</td>
            <td class="rooms-table__price">ETB {{ fmt(room.price_per_night) }}</td>
            <td>
              <select class="status-select" :class="`status-select--${room.status}`" :value="room.status" @change="changeStatus(room, $event.target.value)">
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
              </select>
            </td>
            <td>
              <button class="toggle-btn" :class="{ 'toggle-btn--on': room.is_active }" @click="toggleActive(room)">
                {{ room.is_active ? 'Active' : 'Inactive' }}
              </button>
            </td>
            <td class="rooms-table__actions">
              <button class="action-btn action-btn--edit" @click="openEdit(room)" title="Edit">✏️</button>
              <button class="action-btn action-btn--del"  @click="confirmDelete(room)" title="Delete">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.last_page > 1">
        <button :disabled="pagination.current_page === 1" @click="goPage(pagination.current_page - 1)">← Prev</button>
        <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <button :disabled="pagination.current_page === pagination.last_page" @click="goPage(pagination.current_page + 1)">Next →</button>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <div class="modal__header">
          <h2>{{ editingRoom ? 'Edit Room' : 'Add New Room' }}</h2>
          <button class="modal__close" @click="closeModal">✕</button>
        </div>
        <div class="modal__body">
          <div class="form-grid">
            <div class="form-field">
              <label>Room Number *</label>
              <input v-model="form.room_number" placeholder="e.g. 101" :disabled="!!editingRoom" />
              <span class="form-err" v-if="errors.room_number">{{ errors.room_number[0] }}</span>
            </div>
            <div class="form-field">
              <label>Room Name *</label>
              <input v-model="form.name" placeholder="e.g. Deluxe King" />
              <span class="form-err" v-if="errors.name">{{ errors.name[0] }}</span>
            </div>
            <div class="form-field">
              <label>Type *</label>
              <select v-model="form.type">
                <option v-for="t in TYPES" :key="t" :value="t">{{ cap(t) }}</option>
              </select>
              <span class="form-err" v-if="errors.type">{{ errors.type[0] }}</span>
            </div>
            <div class="form-field" v-if="editingRoom">
              <label>Status</label>
              <select v-model="form.status">
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
              </select>
            </div>
            <div class="form-field">
              <label>Floor *</label>
              <input v-model.number="form.floor" type="number" min="1" />
              <span class="form-err" v-if="errors.floor">{{ errors.floor[0] }}</span>
            </div>
            <div class="form-field">
              <label>Capacity *</label>
              <input v-model.number="form.capacity" type="number" min="1" max="10" />
              <span class="form-err" v-if="errors.capacity">{{ errors.capacity[0] }}</span>
            </div>
            <div class="form-field">
              <label>Price per Night (ETB) *</label>
              <input v-model.number="form.price_per_night" type="number" min="1" />
              <span class="form-err" v-if="errors.price_per_night">{{ errors.price_per_night[0] }}</span>
            </div>
            <div class="form-field form-field--full">
              <label>Description</label>
              <textarea v-model="form.description" rows="3" placeholder="Room description…"></textarea>
            </div>
            <div class="form-field form-field--full">
              <label>Room Images</label>
              <div
                class="upload-zone"
                :class="{ 'upload-zone--drag': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave="isDragging = false"
                @drop.prevent="onDrop"
                @click="$refs.fileInput.click()"
              >
                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple hidden @change="onFileChange" />
                <div class="upload-zone__inner">
                  <span class="upload-zone__icon">📁</span>
                  <p class="upload-zone__text">Drag &amp; drop images here or <span>click to browse</span></p>
                  <p class="upload-zone__hint">JPG, PNG, WEBP · Max 5MB each</p>
                </div>
              </div>

              <!-- Uploading progress -->
              <div v-if="uploadingCount > 0" class="upload-progress">
                <div class="spinner spinner--sm"></div>
                Uploading {{ uploadingCount }} image{{ uploadingCount > 1 ? 's' : '' }}…
              </div>

              <!-- Preview grid -->
              <div v-if="form.images.length" class="img-preview-grid">
                <div v-for="(url, i) in form.images" :key="url" class="img-preview">
                  <img :src="url" :alt="`Image ${i+1}`" @error="e=>e.target.src=fallback" />
                  <button class="img-preview__remove" @click.stop="removeImage(i)" title="Remove">✕</button>
                  <span v-if="i === 0" class="img-preview__primary">Main</span>
                </div>
              </div>
              <span class="form-err" v-if="errors.images">{{ errors.images[0] }}</span>
            </div>
            <div class="form-field form-field--full">
              <label>Amenities</label>
              <div class="amenities-grid">
                <label v-for="a in ALL_AMENITIES" :key="a" class="amenity-check">
                  <input type="checkbox" :value="a" v-model="form.amenities" />
                  {{ amenityLabel(a) }}
                </label>
              </div>
            </div>
            <div class="form-field">
              <label class="toggle-label">
                <input type="checkbox" v-model="form.is_active" />
                Active (visible to guests)
              </label>
            </div>
          </div>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="closeModal">Cancel</button>
          <button class="btn-primary" @click="saveRoom" :disabled="saving">
            <span v-if="saving" class="spinner spinner--sm"></span>
            {{ saving ? 'Saving…' : (editingRoom ? 'Update Room' : 'Create Room') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="deleteTarget" class="modal-backdrop" @click.self="deleteTarget=null">
      <div class="modal modal--sm">
        <div class="modal__header">
          <h2>Delete Room</h2>
          <button class="modal__close" @click="deleteTarget=null">✕</button>
        </div>
        <div class="modal__body">
          <p>Are you sure you want to delete <strong>{{ deleteTarget.name }}</strong> (#{{ deleteTarget.room_number }})?</p>
          <p class="delete-warn">⚠️ This cannot be undone. Rooms with active bookings cannot be deleted.</p>
        </div>
        <div class="modal__footer">
          <button class="btn-ghost" @click="deleteTarget=null">Cancel</button>
          <button class="btn-danger" @click="deleteRoom" :disabled="saving">
            <span v-if="saving" class="spinner spinner--sm"></span>
            {{ saving ? 'Deleting…' : 'Delete Room' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import api from '../../plugins/axios'

const rooms      = ref([])
const loading    = ref(false)
const saving     = ref(false)
const showModal  = ref(false)
const editingRoom = ref(null)
const deleteTarget = ref(null)
const search     = ref('')
const filterType   = ref('')
const filterStatus = ref('')
const pagination = reactive({ total: 0, current_page: 1, last_page: 1, per_page: 15 })
const errors     = ref({})

const TYPES = ['single', 'double', 'deluxe', 'suite', 'penthouse']
const ALL_AMENITIES = ['wifi','tv','minibar','bathtub','balcony','safe','air_conditioning','jacuzzi','kitchen','nespresso','private_bathroom','garden_view','parking','gym','pool']
const AMENITY_LABELS = { wifi:'WiFi',tv:'TV',minibar:'Minibar',bathtub:'Bathtub',balcony:'Balcony',safe:'Safe',air_conditioning:'A/C',jacuzzi:'Jacuzzi',kitchen:'Kitchen',nespresso:'Coffee Machine',private_bathroom:'Private Bathroom',garden_view:'Garden View',parking:'Parking',gym:'Gym',pool:'Pool' }
const fallback = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=200&q=60'

const defaultForm = () => ({
  room_number: '', name: '', type: 'single', status: 'available',
  floor: 1, capacity: 1, price_per_night: '', description: '',
  amenities: [], images: [], is_active: true,
})

const form = reactive(defaultForm())


const isDragging    = ref(false)
const uploadingCount = ref(0)

async function uploadFiles(files) {
  const allowed = ['image/jpeg','image/png','image/webp']
  for (const file of files) {
    if (!allowed.includes(file.type)) { alert(`${file.name} is not a supported image type.`); continue }
    if (file.size > 5 * 1024 * 1024)  { alert(`${file.name} exceeds 5MB.`); continue }
    uploadingCount.value++
    try {
      const fd = new FormData()
      fd.append('image', file)
      const { data } = await api.post('/admin/upload/image', fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      form.images.push(data.data.url)
    } catch { alert(`Failed to upload ${file.name}.`) }
    finally { uploadingCount.value-- }
  }
}

function onFileChange(e) { uploadFiles([...e.target.files]); e.target.value = '' }
function onDrop(e)       { isDragging.value = false; uploadFiles([...e.dataTransfer.files]) }

async function removeImage(i) {
  const url = form.images[i]
  // Try to delete from server if it's a local storage URL
  if (url.includes('/storage/rooms/')) {
    const path = 'rooms/' + url.split('/rooms/').pop()
    try { await api.delete('/admin/upload/image', { data: { path } }) } catch {}
  }
  form.images.splice(i, 1)
}

let debounceTimer = null
function debouncedLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadRooms(), 400)
}

async function loadRooms(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/rooms', {
      params: {
        page,
        per_page: pagination.per_page,
        search:   search.value   || undefined,
        type:     filterType.value   || undefined,
        status:   filterStatus.value || undefined,
      }
    })
    rooms.value = data.data?.rooms ?? []
    Object.assign(pagination, data.data?.pagination ?? {})
  } catch { rooms.value = [] }
  finally { loading.value = false }
}

function goPage(p) { loadRooms(p) }

function resetFilters() {
  search.value = ''; filterType.value = ''; filterStatus.value = ''
  loadRooms()
}

function openCreate() {
  editingRoom.value = null
  Object.assign(form, defaultForm())
  errors.value = {}
  showModal.value = true
}

function openEdit(room) {
  editingRoom.value = room
  Object.assign(form, {
    room_number:     room.room_number,
    name:            room.name,
    type:            room.type,
    status:          room.status,
    floor:           room.floor,
    capacity:        room.capacity,
    price_per_night: room.price_per_night,
    description:     room.description ?? '',
    amenities:       [...(room.amenities ?? [])],
    images:          [...(room.images ?? [])],
    is_active:       room.is_active,
  })
  errors.value = {}
  showModal.value = true
}

function closeModal() { showModal.value = false; editingRoom.value = null }

async function saveRoom() {
  saving.value = true; errors.value = {}
  try {
    const payload = { ...form }
    if (editingRoom.value) {
      await api.put(`/admin/rooms/${editingRoom.value.id}`, payload)
    } else {
      await api.post('/admin/rooms', payload)
    }
    closeModal()
    loadRooms(pagination.current_page)
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
  } finally { saving.value = false }
}

async function changeStatus(room, status) {
  try {
    await api.put(`/admin/rooms/${room.id}`, { status })
    room.status = status
  } catch {}
}

async function toggleActive(room) {
  try {
    await api.put(`/admin/rooms/${room.id}`, { is_active: !room.is_active })
    room.is_active = !room.is_active
  } catch {}
}

function confirmDelete(room) { deleteTarget.value = room }

async function deleteRoom() {
  saving.value = true
  try {
    await api.delete(`/admin/rooms/${deleteTarget.value.id}`)
    deleteTarget.value = null
    loadRooms(pagination.current_page)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Could not delete room.')
  } finally { saving.value = false }
}

function fmt(n) { return Number(n||0).toLocaleString('en-ET', { minimumFractionDigits: 2 }) }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function amenityLabel(k) { return AMENITY_LABELS[k] ?? k.replace(/_/g,' ') }

loadRooms()
</script>

<style scoped>
.rooms-admin { padding: 28px 32px; max-width: 1300px; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.page-header__title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 2px; }
.page-header__sub   { font-size: 13px; color: #9ca3af; }

/* Filters */
.filters-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filters-bar__search {
  flex: 1; min-width: 200px; padding: 9px 14px;
  border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; outline: none;
}
.filters-bar__search:focus { border-color: #4f46e5; }
.filters-bar__select {
  padding: 9px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 14px; color: #374151; background: #fff; outline: none; cursor: pointer;
}

/* Table */
.table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); overflow: hidden; }
.table-loading { display: flex; align-items: center; gap: 10px; padding: 40px; justify-content: center; color: #6b7280; }
.rooms-table { width: 100%; border-collapse: collapse; }
.rooms-table thead tr { background: #f9fafb; border-bottom: 1px solid #f0f0f0; }
.rooms-table th { padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; text-align: left; white-space: nowrap; }
.rooms-table td { padding: 14px 16px; font-size: 13.5px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
.rooms-table__row:hover td { background: #fafafa; }
.table-empty { text-align: center; color: #9ca3af; padding: 48px !important; }

.rooms-table__room-cell { display: flex; align-items: center; gap: 12px; }
.rooms-table__thumb { width: 52px; height: 40px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.rooms-table__name { font-weight: 700; color: #1a202c; font-size: 14px; }
.rooms-table__num  { font-size: 11px; color: #9ca3af; }
.rooms-table__price { font-weight: 700; color: #4f46e5; }
.rooms-table__actions { display: flex; gap: 6px; }

/* Badges */
.badge { padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.badge--single    { background: #d1fae5; color: #065f46; }
.badge--double    { background: #dbeafe; color: #1e40af; }
.badge--deluxe    { background: #fef3c7; color: #92400e; }
.badge--suite     { background: #ede9fe; color: #5b21b6; }
.badge--penthouse { background: #fee2e2; color: #991b1b; }

/* Status select */
.status-select {
  padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
  border: 1.5px solid #e5e7eb; cursor: pointer; outline: none;
}
.status-select--available   { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
.status-select--occupied    { background: #dbeafe; color: #1e40af; border-color: #93c5fd; }
.status-select--maintenance { background: #fef3c7; color: #92400e; border-color: #fcd34d; }

/* Toggle active */
.toggle-btn {
  padding: 4px 12px; border-radius: 99px; font-size: 11.5px; font-weight: 700;
  border: none; cursor: pointer; transition: all 0.15s;
  background: #f3f4f6; color: #9ca3af;
}
.toggle-btn--on { background: #d1fae5; color: #065f46; }

/* Action buttons */
.action-btn { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.15s; }
.action-btn--edit { background: #ede9fe; }
.action-btn--edit:hover { background: #ddd6fe; }
.action-btn--del  { background: #fee2e2; }
.action-btn--del:hover  { background: #fecaca; }

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
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 680px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.modal--sm { max-width: 440px; }
.modal__header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f0f0; }
.modal__header h2 { font-size: 1.1rem; font-weight: 800; color: #1a202c; }
.modal__close { background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; padding: 4px; }
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
  font-size: 14px; color: #1a202c; outline: none; font-family: inherit;
  transition: border-color 0.15s;
}
.form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color: #4f46e5; }
.form-field input:disabled { background: #f9fafb; color: #9ca3af; }
.form-err { font-size: 12px; color: #ef4444; }
.toggle-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; text-transform: none; letter-spacing: 0; }
.toggle-label input { width: auto; }

/* Amenities */
.amenities-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.amenity-check { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #374151; cursor: pointer; font-weight: 400; text-transform: none; letter-spacing: 0; }
.amenity-check input { width: auto; }

/* Upload zone */
.upload-zone {
  border: 2px dashed #d1d5db; border-radius: 10px; padding: 28px 20px;
  text-align: center; cursor: pointer; transition: border-color 0.2s, background 0.2s;
  background: #fafafa;
}
.upload-zone:hover, .upload-zone--drag {
  border-color: #4f46e5; background: #f0f0ff;
}
.upload-zone__icon { font-size: 2rem; display: block; margin-bottom: 8px; }
.upload-zone__text { font-size: 14px; color: #374151; margin-bottom: 4px; }
.upload-zone__text span { color: #4f46e5; font-weight: 600; text-decoration: underline; }
.upload-zone__hint { font-size: 12px; color: #9ca3af; }

.upload-progress {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px; color: #6b7280; margin-top: 10px;
}

.img-preview-grid {
  display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px;
}
.img-preview {
  position: relative; width: 90px; height: 70px; border-radius: 8px; overflow: hidden;
  border: 2px solid #e5e7eb;
}
.img-preview img { width: 100%; height: 100%; object-fit: cover; }
.img-preview__remove {
  position: absolute; top: 3px; right: 3px;
  width: 20px; height: 20px; border-radius: 50%;
  background: rgba(0,0,0,0.6); color: #fff;
  border: none; font-size: 10px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  line-height: 1;
}
.img-preview__primary {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: rgba(79,70,229,0.85); color: #fff;
  font-size: 9px; font-weight: 700; text-align: center; padding: 2px;
  text-transform: uppercase; letter-spacing: 0.5px;
}

/* Delete warning */
.delete-warn { color: #ef4444; font-size: 13px; margin-top: 8px; }

/* Spinner */
.spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: spin 0.7s linear infinite; display: inline-block; }
.spinner--sm { width: 13px; height: 13px; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .rooms-admin { padding: 16px; }
  .form-grid { grid-template-columns: 1fr; }
  .amenities-grid { grid-template-columns: repeat(2, 1fr); }
  .rooms-table th:nth-child(3), .rooms-table td:nth-child(3),
  .rooms-table th:nth-child(4), .rooms-table td:nth-child(4) { display: none; }
}
</style>
