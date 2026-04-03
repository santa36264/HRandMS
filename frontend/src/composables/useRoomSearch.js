import { ref, reactive, computed } from 'vue'
import { roomService } from '../services/rooms'

export function useRoomSearch() {
  const rooms   = ref([])
  const loading = ref(false)
  const error   = ref('')
  const searched = ref(false)
  const meta    = reactive({ nights: 0, rooms_found: 0 })

  const filters = reactive({
    check_in:  '',
    check_out: '',
    guests:    1,
    type:      '',
    min_price: 0,
    max_price: 1000,
    sort_by:   'price_per_night',
    sort_dir:  'asc',
  })

  // Today and tomorrow as default min dates
  const today    = computed(() => new Date().toISOString().split('T')[0])
  const tomorrow = computed(() => {
    const d = new Date(); d.setDate(d.getDate() + 1)
    return d.toISOString().split('T')[0]
  })

  // Min check-out is always day after check-in
  const minCheckOut = computed(() => {
    if (!filters.check_in) return tomorrow.value
    const d = new Date(filters.check_in); d.setDate(d.getDate() + 1)
    return d.toISOString().split('T')[0]
  })

  function onCheckInChange() {
    // Reset check-out if it's now before check-in
    if (filters.check_out && filters.check_out <= filters.check_in) {
      filters.check_out = minCheckOut.value
    }
  }

  async function search() {
    if (!filters.check_in || !filters.check_out) {
      error.value = 'Please select check-in and check-out dates.'
      return
    }
    loading.value = true
    error.value   = ''
    try {
      const params = {
        check_in:  filters.check_in,
        check_out: filters.check_out,
        guests:    filters.guests || undefined,
        type:      filters.type   || undefined,
        min_price: filters.min_price > 0     ? filters.min_price  : undefined,
        max_price: filters.max_price < 1000  ? filters.max_price  : undefined,
        sort_by:   filters.sort_by,
        sort_dir:  filters.sort_dir,
      }
      const { data } = await roomService.availability(params)
      rooms.value        = data.data.rooms
      meta.nights        = data.data.nights
      meta.rooms_found   = data.data.rooms_found
      searched.value     = true
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Search failed. Please try again.'
    } finally {
      loading.value = false
    }
  }

  function reset() {
    filters.check_in  = ''
    filters.check_out = ''
    filters.guests    = 1
    filters.type      = ''
    filters.min_price = 0
    filters.max_price = 1000
    rooms.value       = []
    searched.value    = false
    error.value       = ''
  }

  return { filters, rooms, loading, error, searched, meta,
           today, tomorrow, minCheckOut, onCheckInChange, search, reset }
}
