import api from '../plugins/axios'

export const roomService = {
  stats:        (config)         => api.get('/guest/stats', config),
  list:         (params, config) => api.get('/guest/rooms', { params, ...config }),
  show:         (id)             => api.get(`/guest/rooms/${id}`),
  availability: (params)         => api.get('/guest/rooms/availability', { params }),
  bookedDates:  (id, params)     => api.get(`/guest/rooms/${id}/booked-dates`, { params }),
}
