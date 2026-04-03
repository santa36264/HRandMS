import api from '../plugins/axios'

export const reviewsService = {
  list:    ()         => api.get('/guest/reviews'),
  store:   (data)     => api.post('/guest/reviews', data),
  update:  (id, data) => api.put(`/guest/reviews/${id}`, data),
  destroy: (id)       => api.delete(`/guest/reviews/${id}`),

  // Public room reviews
  forRoom: (roomId, params) => api.get(`/guest/rooms/${roomId}/reviews`, { params }),
}
