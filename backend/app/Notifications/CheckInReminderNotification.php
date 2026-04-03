<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CheckInReminderNotification extends Notification implements ShouldQueue
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
            ->subject("Check-in Tomorrow – {$this->booking->room->name} | " . config('app.name'))
            ->view('emails.checkin-reminder', [
                'user'           => $notifiable,
                'booking'        => $this->booking->loadMissing('room'),
                'recipientEmail' => $notifiable->email,
            ]);
    }
}
