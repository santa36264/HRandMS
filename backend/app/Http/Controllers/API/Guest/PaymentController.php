<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService,
        private PaymentManager $manager,
    ) {}

    /**
     * GET /api/guest/payments/gateways
     */
    public function gateways(): JsonResponse
    {
        return $this->success($this->manager->available());
    }

    /**
     * POST /api/guest/payments/initiate
     * Body: { booking_id, gateway }
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $booking = Booking::with('room', 'user')
            ->where('user_id', $request->user()->id)
            ->findOrFail($request->booking_id);

        $result = $this->paymentService->initiate($booking, $request->gateway);

        return $this->success([
            'transaction_id' => $result['transaction_id'],
            'payment_url'    => $result['payment_url'],
            'checkout_token' => $result['checkout_token'],
        ], 'Payment initiated. Redirect user to payment_url.');
    }

    /**
     * GET /api/guest/payments/verify/{transactionId}?gateway=chapa
     * Manual polling endpoint — called by frontend until status is terminal.
     */
    public function verify(Request $request, string $transactionId): JsonResponse
    {
        $request->validate([
            'gateway' => ['required', 'string', 'in:chapa'],
        ]);

        $payment = $this->paymentService->verify($transactionId, $request->gateway);

        return $this->success([
            'status'         => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at'        => $payment->paid_at?->toDateTimeString(),
            'booking'        => new BookingResource($payment->booking),
        ]);
    }

    /**
     * POST /api/guest/payments/verify-by-reference
     * Finds the pending payment for a booking reference and verifies it with Chapa.
     * Called by PaymentResultView after Chapa redirects back — no tx_ref needed.
     */
    public function verifyByReference(Request $request): JsonResponse
    {
        $request->validate([
            'booking_reference' => ['required', 'string'],
        ]);

        $payment = Payment::with('booking')
            ->whereHas('booking', fn($q) => $q
                ->where('user_id', $request->user()->id)
                ->where('booking_reference', $request->booking_reference)
            )
            ->whereIn('status', ['pending', 'completed'])
            ->orderByDesc('created_at')
            ->first();

        if (! $payment) {
            return $this->error('No payment found for this booking.', 404);
        }

        // Already completed — return immediately
        if ($payment->status === 'completed') {
            return $this->success([
                'status'         => 'completed',
                'transaction_id' => $payment->transaction_id,
                'paid_at'        => $payment->paid_at?->toDateTimeString(),
                'booking'        => new BookingResource($payment->booking),
            ]);
        }

        // Verify with Chapa
        if (! $payment->transaction_id) {
            return $this->success([
                'status'         => $payment->status,
                'transaction_id' => $payment->transaction_id,
                'paid_at'        => $payment->paid_at?->toDateTimeString(),
                'booking'        => new BookingResource($payment->booking),
            ], 'Payment is still pending. Please wait.');
        }

        $payment = $this->paymentService->verify((string) $payment->transaction_id, $payment->gateway);

        return $this->success([
            'status'         => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at'        => $payment->paid_at?->toDateTimeString(),
            'booking'        => new BookingResource($payment->booking),
        ]);
    }

    /**
     * GET /api/guest/payments/{id}/receipt
     * Returns a payment receipt for a completed payment.
     */
    public function receipt(Request $request, int $id): JsonResponse
    {
        $payment = Payment::with('booking.room')
            ->whereHas('booking', fn($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($id);

        if ($payment->status !== 'completed') {
            return $this->error('Receipt is only available for completed payments.', 422);
        }

        $booking = $payment->booking;

        return $this->success([
            'receipt' => [
                'receipt_number'   => 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                'transaction_id'   => $payment->transaction_id,
                'booking_reference'=> $booking->booking_reference,
                'guest_name'       => $request->user()->name,
                'room'             => $booking->room->name,
                'room_number'      => $booking->room->room_number,
                'check_in'         => $booking->check_in_date->toDateString(),
                'check_out'        => $booking->check_out_date->toDateString(),
                'nights'           => $booking->nights(),
                'amount'           => (float) $payment->amount,
                'currency'         => $payment->currency,
                'gateway'          => $payment->gateway,
                'gateway_label'    => $this->manager->available()[$payment->gateway]['label'] ?? $payment->gateway,
                'paid_at'          => $payment->paid_at?->toDateTimeString(),
                'issued_at'        => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * GET /api/guest/payments
     * List the authenticated user's payments.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with('booking.room')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return $this->success([
            'payments'   => $payments->map(fn($p) => $this->formatPayment($p)),
            'pagination' => [
                'total'        => $payments->total(),
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/payments/webhook/{gateway}  ← public, no auth
     */
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        $this->paymentService->handleWebhook(
            $gateway,
            $request->all(),
            $request->headers->all(),
        );

        return response()->json(['status' => 'ok']);
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function formatPayment(Payment $payment): array
    {
        return [
            'id'             => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount'         => (float) $payment->amount,
            'currency'       => $payment->currency,
            'status'         => $payment->status,
            'gateway'        => $payment->gateway,
            'method'         => $payment->method,
            'paid_at'        => $payment->paid_at?->toDateTimeString(),
            'booking'        => [
                'id'                => $payment->booking->id,
                'reference'         => $payment->booking->booking_reference,
                'room'              => $payment->booking->room->name ?? null,
                'check_in'          => $payment->booking->check_in_date?->toDateString(),
                'check_out'         => $payment->booking->check_out_date?->toDateString(),
            ],
        ];
    }
}
