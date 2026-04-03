<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPaymentReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private readonly Booking $booking,
        private readonly Payment $payment,
    ) {}

    public function handle(): void
    {
        $booking = $this->booking->fresh(['user', 'room']);
        $payment = $this->payment->fresh();

        if (! $booking || ! $booking->user || ! $payment) {
            Log::warning("SendPaymentReceiptJob skipped — missing model for booking {$this->booking->booking_reference}.");
            return;
        }

        $booking->user->notify(new PaymentReceiptNotification($booking, $payment));

        Log::info("SendPaymentReceiptJob sent to {$booking->user->email} for {$booking->booking_reference}.");
    }

    public function failed(Throwable $e): void
    {
        Log::error("SendPaymentReceiptJob permanently failed for {$this->booking->booking_reference}: {$e->getMessage()}");
    }

    public function backoff(): array
    {
        return [60, 120, 240];
    }
}
