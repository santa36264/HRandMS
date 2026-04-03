import api from '../plugins/axios'

export const authService = {
  register:       (data)  => api.post('/register', data),
  login:          (data)  => api.post('/login', data),
  logout:         ()      => api.post('/logout'),
  logoutAll:      ()      => api.post('/logout-all'),
  me:             ()      => api.get('/me', { _silent401: true }),
  forgotPassword: (data)  => api.post('/forgot-password', data),
  resetPassword:  (data)  => api.post('/reset-password', data),
  updateProfile:  (data)  => api.put('/profile', data),
  changePassword: (data)  => api.put('/profile/password', data),

  // Email OTP
  sendOtp:   ()      => api.post('/email/send-otp'),
  verifyOtp: (otp)   => api.post('/email/verify', { otp }),
  otpStatus: ()      => api.get('/email/status'),
}
