<?php

namespace App\Notifications;

use App\Models\ConciergeBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConciergeBookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private ConciergeBooking $booking;

    public function __construct(ConciergeBooking $booking)
    {
        $this->booking = $booking;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = $this->booking->service;

        return (new MailMessage)
            ->subject("Concierge Service Confirmed - {$this->booking->confirmation_code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your concierge service has been confirmed!")
            ->line("**Service:** {$service->name}")
            ->line("**Confirmation Code:** {$this->booking->confirmation_code}")
            ->line("**Scheduled Time:** {$this->booking->scheduled_time->format('M d, Y H:i')}")
            ->line("**Amount:** {$this->booking->total_amount} ETB")
            ->when($service->provider_name, function ($message) use ($service) {
                return $message->line("**Provider:** {$service->provider_name}");
            })
            ->when($service->provider_phone, function ($message) use ($service) {
                return $message->line("**Contact:** {$service->provider_phone}");
            })
            ->line("Thank you for using SATAAB Hotel Concierge Service!");
    }
}
