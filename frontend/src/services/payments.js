import api from '../plugins/axios'

export const paymentService = {
  gateways: ()                        => api.get('/guest/payments/gateways'),
  initiate: (bookingId, gateway)      => api.post('/guest/payments/initiate', { booking_id: bookingId, gateway }),
  verify:   (transactionId, gateway)  => api.get(`/guest/payments/verify/${transactionId}`, { params: { gateway } }),
}
