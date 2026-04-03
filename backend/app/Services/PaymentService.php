<?php

namespace App\Services;

use App\Events\PaymentConfirmed;
use App\Events\PaymentFailed;
use App\Models\Booking;
use App\Models\Payment;
use App\Payments\DTOs\PaymentRequestDTO;
use App\Payments\DTOs\WebhookDTO;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private PaymentManager $manager) {}

    // =========================================================================
    // INITIATE
    // =========================================================================

    public function initiate(Booking $booking, string $gateway): array
    {
        if ($booking->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'booking' => ['This booking has already been paid.'],
            ]);
        }

        $user = $booking->user;

        $dto = PaymentRequestDTO::fromArray([
            'booking_id'     => $booking->id,
            'user_id'        => $user->id,
            'amount'         => $booking->final_amount,
            'currency'       => 'ETB',
            'customer_phone' => $user->phone ?? '',
            'customer_name'  => $user->name,
            'customer_email' => $user->email,
            'description'    => "Booking #{$booking->booking_reference} – {$booking->room->name}",
            'callback_url'   => route('payments.webhook', $gateway),
            'return_url'     => config('payments.return_url') . "?ref={$booking->booking_reference}",
            'metadata'       => ['booking_reference' => $booking->booking_reference],
        ]);

        $response = $this->manager->gateway($gateway)->initiate($dto);

        if (! $response->success) {
            throw ValidationException::withMessages([
                'payment' => [$response->errorMessage ?? 'Payment initiation failed.'],
            ]);
        }

        $payment = Payment::create([
            'transaction_id'   => $response->transactionId,
            'booking_id'       => $booking->id,
            'user_id'          => $user->id,
            'amount'           => $booking->final_amount,
            'currency'         => 'ETB',
            'method'           => 'online',
            'status'           => 'pending',
            'gateway'          => $gateway,
            'gateway_response' => $response->rawResponse,
        ]);

        return [
            'payment'        => $payment,
            'payment_url'    => $response->paymentUrl,
            'checkout_token' => $response->checkoutToken,
            'transaction_id' => $response->transactionId,
        ];
    }

    // =========================================================================
    // VERIFY (manual polling)
    // =========================================================================

    public function verify(string $transactionId, string $gateway): Payment
    {
        if (! $transactionId) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Transaction ID is required.'],
            ]);
        }

        $payment  = Payment::where('transaction_id', $transactionId)->firstOrFail();
        $response = $this->manager->gateway($gateway)->verify($transactionId);

        $this->applyPaymentStatus($payment, $response->status, $response->rawResponse);

        return $payment->fresh(['booking.room']);
    }

    // =========================================================================
    // WEBHOOK
    // =========================================================================

    public function handleWebhook(string $gateway, array $payload, array $headers): void
    {
        $dto     = $this->manager->gateway($gateway)->handleWebhook($payload, $headers);
        $payment = Payment::where('transaction_id', $dto->transactionId)->first();

        if (! $payment) {
            Log::warning("[Payment] Webhook for unknown transaction: {$dto->transactionId}");
            return;
        }

        // Idempotency — skip if already in terminal state
        if (in_array($payment->status, ['completed', 'refunded'])) {
            Log::info("[Payment] Skipping webhook — already {$payment->status}: {$dto->transactionId}");
            return;
        }

        $this->applyPaymentStatus($payment, $dto->status, $dto->rawPayload);
    }

    // =========================================================================
    // REFUND
    // =========================================================================

    public function refund(Payment $payment, float $amount, string $reason = ''): Payment
    {
        if ($payment->status !== 'completed') {
            throw ValidationException::withMessages([
                'payment' => ['Only completed payments can be refunded.'],
            ]);
        }

        if ($amount > $payment->amount) {
            throw ValidationException::withMessages([
                'amount' => ['Refund amount cannot exceed the original payment amount.'],
            ]);
        }

        // Chapa does not have a programmatic refund API — mark as refunded manually
        $gateway = $payment->gateway;
        if ($gateway !== 'chapa') {
            throw ValidationException::withMessages(['gateway' => ["Refund not supported for {$gateway}."]]);
        }
        // For Chapa, refunds are processed via the Chapa dashboard.
        // We record the refund locally and notify the admin.

        DB::transaction(function () use ($payment, $amount, $reason) {
            $payment->update([
                'status' => 'refunded',
                'notes'  => $reason,
            ]);

            $payment->booking->update(['payment_status' => 'refunded']);
        });

        Log::info("[Payment] Refund processed", [
            'transaction_id' => $payment->transaction_id,
            'amount'         => $amount,
            'gateway'        => $gateway,
        ]);

        return $payment->fresh();
    }

    // =========================================================================
    // CORE: apply status + update booking + fire events
    // =========================================================================

    private function applyPaymentStatus(Payment $payment, string $status, array $raw): void
    {
        DB::transaction(function () use ($payment, $status, $raw) {

            $payment->update([
                'status'           => $status,
                'gateway_response' => $raw,
                'paid_at'          => $status === 'completed' ? now() : $payment->paid_at,
            ]);

            $booking = $payment->booking;

            // ── Update booking payment_status ──────────────────────────────
            $bookingPaymentStatus = match ($status) {
                'completed' => 'paid',
                'refunded'  => 'refunded',
                default     => $booking->payment_status,
            };

            // ── Update booking status on confirmation ──────────────────────
            $bookingStatus = $booking->status;
            if ($status === 'completed' && in_array($booking->status, ['pending'])) {
                $bookingStatus = 'confirmed';
            }

            $booking->update([
                'payment_status' => $bookingPaymentStatus,
                'status'         => $bookingStatus,
            ]);

            $booking->refresh();

            Log::info("[Payment] Status applied: {$status} → booking #{$booking->booking_reference}", [
                'transaction_id'  => $payment->transaction_id,
                'booking_status'  => $bookingStatus,
                'payment_status'  => $bookingPaymentStatus,
            ]);
        });

        // Fire events outside transaction so listeners don't block it
        $payment->refresh();
        $booking = $payment->booking->load('room', 'user');

        match ($status) {
            'completed' => event(new PaymentConfirmed($payment, $booking)),
            'failed'    => event(new PaymentFailed($payment, $booking)),
            default     => null,
        };
    }
}
