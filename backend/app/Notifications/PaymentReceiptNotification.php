<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 30;

    public function backoff(): array { return [60, 120, 240]; }

    public function __construct(
        private Booking $booking,
        private Payment $payment,
    ) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Receipt – ETB " . number_format($this->payment->amount, 2) . " | " . config('app.name'))
            ->view('emails.payment-receipt', [
                'user'           => $notifiable,
                'booking'        => $this->booking->loadMissing('room'),
                'payment'        => $this->payment,
                'recipientEmail' => $notifiable->email,
            ]);
    }
}
