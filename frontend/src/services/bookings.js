import api from '../plugins/axios'

export const bookingsService = {
  // Guest
  list:         (params) => api.get('/guest/bookings', { params }),
  show:         (id)     => api.get(`/guest/bookings/${id}`),
  byReference:  (ref)    => api.get(`/guest/bookings/by-reference/${ref}`),
  store:        (data)   => api.post('/guest/bookings', data),
  cancel:       (id, data) => api.delete(`/guest/bookings/${id}`, { data }),
  preview:      (params) => api.get('/guest/bookings/preview', { params }),
  checkinToken: (id)     => api.get(`/guest/bookings/${id}/checkin-token`),

  // Admin
  adminList:         (params) => api.get('/admin/bookings', { params }),
  adminShow:         (id)     => api.get(`/admin/bookings/${id}`),
  adminUpdateStatus: (id, data) => api.patch(`/admin/bookings/${id}/status`, data),
  adminDelete:       (id)     => api.delete(`/admin/bookings/${id}`),
}
