import { ref, computed } from 'vue'
import { paymentService } from '../services/payments'

export function usePayment(bookingId) {
  const step            = ref('select')   // select | processing | polling | success | failed
  const selectedGateway = ref(null)
  const gateways        = ref([])
  const transactionId   = ref('')
  const paymentUrl      = ref('')
  const error           = ref('')
  const pollCount       = ref(0)
  const paymentResult   = ref(null)

  const MAX_POLLS    = 24   // 2 min at 5s intervals
  const POLL_INTERVAL = 5000

  let pollTimer = null

  const isLoading = computed(() => ['processing', 'polling'].includes(step.value))

  async function loadGateways() {
    try {
      const { data } = await paymentService.gateways()
      gateways.value = data.data
    } catch {
      gateways.value = [
        { name: 'chapa', label: 'Chapa', currencies: ['ETB'] },
      ]
    }
  }

  function selectGateway(gateway) {
    selectedGateway.value = gateway
    error.value = ''
  }

  async function initiatePayment() {
    if (!selectedGateway.value) {
      error.value = 'Please select a payment method.'
      return
    }
    step.value  = 'processing'
    error.value = ''

    try {
      const { data } = await paymentService.initiate(bookingId, selectedGateway.value.name)
      transactionId.value = data.data.transaction_id
      paymentUrl.value    = data.data.payment_url

      // Redirect current tab to Chapa checkout.
      // Chapa will redirect back to return_url (/booking/payment-result?ref=...)
      // which handles verification with retry logic.
      if (paymentUrl.value) {
        window.location.href = paymentUrl.value
        return
      }

      step.value = 'polling'
      startPolling()
    } catch (e) {
      step.value  = 'failed'
      error.value = e.response?.data?.message ?? 'Payment initiation failed.'
    }
  }

  function startPolling() {
    pollCount.value = 0
    pollTimer = setInterval(async () => {
      pollCount.value++

      try {
        const { data } = await paymentService.verify(transactionId.value, selectedGateway.value.name)
        const status   = data.data.status

        if (status === 'completed') {
          clearInterval(pollTimer)
          paymentResult.value = data.data
          step.value = 'success'
        } else if (status === 'failed') {
          clearInterval(pollTimer)
          error.value = 'Payment was declined or failed.'
          step.value  = 'failed'
        } else if (pollCount.value >= MAX_POLLS) {
          clearInterval(pollTimer)
          error.value = 'Payment verification timed out. If you completed payment, contact support.'
          step.value  = 'failed'
        }
      } catch {
        // Network hiccup — keep polling
      }
    }, POLL_INTERVAL)
  }

  function stopPolling() {
    clearInterval(pollTimer)
  }

  function reset() {
    stopPolling()
    step.value            = 'select'
    selectedGateway.value = null
    transactionId.value   = ''
    paymentUrl.value      = ''
    error.value           = ''
    pollCount.value       = 0
    paymentResult.value   = null
  }

  return {
    step, selectedGateway, gateways, transactionId,
    paymentUrl, error, pollCount, paymentResult,
    isLoading, MAX_POLLS,
    loadGateways, selectGateway, initiatePayment, stopPolling, reset,
  }
}
