import api from '../plugins/axios'

export const analyticsService = {
  dashboard: ()       => api.get('/admin/analytics/dashboard'),
  revenue:   (year)   => api.get('/admin/analytics/revenue',   { params: { year } }),
  occupancy: (year)   => api.get('/admin/analytics/occupancy', { params: { year } }),
  payments:  (year)   => api.get('/admin/analytics/payments',  { params: { year } }),
  rooms:     (year)   => api.get('/admin/analytics/rooms',     { params: { year } }),
}
