<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentService $paymentService) {}

    /**
     * GET /api/admin/payments
     * All payments with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['booking.room', 'user' => fn($q) => $q->withTrashed()])
            ->orderByDesc('created_at');

        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('gateway')) $query->where('gateway', $request->gateway);
        if ($request->filled('from'))    $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('until'))   $query->whereDate('created_at', '<=', $request->until);

        $payments = $query->paginate($request->integer('per_page', 20));

        $summary = Payment::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'failed'    THEN 1 ELSE 0 END) as failed_count
        ")->first();

        return $this->success([
            'payments'   => $payments->map(fn($p) => $this->formatPayment($p)),
            'summary'    => [
                'total_revenue' => (float) $summary->total_revenue,
                'total_count'   => (int)   $summary->total,
                'pending_count' => (int)   $summary->pending_count,
                'failed_count'  => (int)   $summary->failed_count,
            ],
            'pagination' => [
                'total'        => $payments->total(),
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/payments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with(['booking.room', 'user' => fn($q) => $q->withTrashed()])->findOrFail($id);

        return $this->success([
            ...$this->formatPayment($payment),
            'gateway_response' => $payment->gateway_response,
            'booking'          => new BookingResource($payment->booking),
        ]);
    }

    /**
     * POST /api/admin/payments/{id}/refund
     * Body: { amount, reason }
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        $payment = Payment::findOrFail($id);
        $payment = $this->paymentService->refund(
            $payment,
            (float) $request->amount,
            $request->input('reason', ''),
        );

        return $this->success([
            'status'         => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'booking'        => new BookingResource($payment->booking),
        ], 'Refund processed successfully.');
    }

    /**
     * POST /api/admin/payments/{id}/verify
     * Manually re-verify a pending payment.
     */
    public function verify(int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return $this->error("Payment is already {$payment->status}.", 422);
        }

        $payment = $this->paymentService->verify($payment->transaction_id, $payment->gateway);

        return $this->success([
            'status'         => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'booking'        => new BookingResource($payment->booking),
        ], 'Payment verification complete.');
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
            'notes'          => $payment->notes,
            'paid_at'        => $payment->paid_at?->toDateTimeString(),
            'created_at'     => $payment->created_at?->toDateTimeString(),
            'user'           => $payment->user ? [
                'id'    => $payment->user->id,
                'name'  => $payment->user->name,
                'email' => $payment->user->email,
            ] : null,
            'booking' => $payment->booking ? [
                'id'        => $payment->booking->id,
                'reference' => $payment->booking->booking_reference,
                'status'    => $payment->booking->status,
                'room'      => $payment->booking->room?->name ?? null,
            ] : null,
        ];
    }
}
