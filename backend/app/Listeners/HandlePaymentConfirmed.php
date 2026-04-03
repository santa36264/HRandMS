<?php

namespace App\Listeners;

use App\Events\PaymentConfirmed;
use App\Jobs\SendBookingConfirmedJob;
use App\Jobs\SendPaymentReceiptJob;
use Illuminate\Support\Facades\Log;

class HandlePaymentConfirmed
{
    /**
     * Runs synchronously (QUEUE_CONNECTION=sync) so emails fire immediately.
     */
    public function handle(PaymentConfirmed $event): void
    {
        $booking = $event->booking->loadMissing(['user', 'room']);
        $payment = $event->payment;

        Log::info("Payment confirmed for booking #{$booking->booking_reference}", [
            'transaction_id' => $payment->transaction_id,
            'gateway'        => $payment->gateway,
            'amount'         => $payment->amount,
        ]);

        // Send both emails directly (sync queue fires them inline)
        SendBookingConfirmedJob::dispatch($booking, $payment);
        SendPaymentReceiptJob::dispatch($booking, $payment);
    }
}
