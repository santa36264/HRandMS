<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Booking model
     */
    private Booking $booking;

    /**
     * Cancellation policy
     */
    private array $policy;

    /**
     * Cancellation reference
     */
    private string $cancellationRef;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, array $policy, string $cancellationRef)
    {
        $this->booking = $booking;
        $this->policy = $policy;
        $this->cancellationRef = $cancellationRef;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $bookingRef = strtoupper(substr(md5($this->booking->id), 0, 8));
        $nights = $this->booking->check_out_date->diffInDays($this->booking->check_in_date);

        $mail = (new MailMessage)
            ->subject('Booking Cancellation Confirmation - ' . $bookingRef)
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your booking at SATAAB Hotel has been successfully cancelled.")
            ->line('')
            ->line('<strong>Cancellation Details:</strong>')
            ->line("Original Booking Reference: {$bookingRef}")
            ->line("Cancellation Reference: {$this->cancellationRef}")
            ->line("Cancellation Date: " . now()->format('M d, Y H:i'))
            ->line('')
            ->line('<strong>Booking Information:</strong>')
            ->line("Room Type: {$this->booking->room->type}")
            ->line("Check-in: {$this->booking->check_in_date->format('M d, Y')} (14:00)")
            ->line("Check-out: {$this->booking->check_out_date->format('M d, Y')} (11:00)")
            ->line("Duration: {$nights} night" . ($nights > 1 ? 's' : ''))
            ->line('')
            ->line('<strong>Payment Information:</strong>')
            ->line("Original Amount: {$this->booking->total_price} ETB");

        if ($this->policy['refund_eligible']) {
            $mail->line("Refund Amount: {$this->policy['refund_amount']} ETB (100%)")
                ->line("Refund Status: Processing")
                ->line("Expected Timeline: 3-5 business days")
                ->line("Refund Method: Original payment method");
        } else {
            $mail->line("Refund Status: Non-refundable")
                ->line("Reason: Cancellation deadline has passed");
        }

        $mail->line('')
            ->line('<strong>Important Information:</strong>')
            ->line('• Your cancellation has been confirmed')
            ->line('• The hotel has been notified')
            ->line('• You will receive a refund confirmation email once processed')
            ->line('• If you have any questions, please contact us')
            ->line('')
            ->line('<strong>Contact Information:</strong>')
            ->line('Phone: +251911234567')
            ->line('Email: info@sataabhotel.com')
            ->line('Website: www.sataabhotel.com')
            ->line('')
            ->line('We hope to welcome you back to SATAAB Hotel in the future!')
            ->salutation('Best regards, SATAAB Hotel Team');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => strtoupper(substr(md5($this->booking->id), 0, 8)),
            'cancellation_reference' => $this->cancellationRef,
            'refund_eligible' => $this->policy['refund_eligible'],
            'refund_amount' => $this->policy['refund_amount'],
        ];
    }
}
