<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 30;

    public function backoff(): array { return [60, 120, 240]; }

    public function __construct(private Booking $booking) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("How Was Your Stay? Share Your Experience | " . config('app.name'))
            ->view('emails.review-request', [
                'user'           => $notifiable,
                'booking'        => $this->booking->loadMissing('room'),
                'recipientEmail' => $notifiable->email,
            ]);
    }
}
