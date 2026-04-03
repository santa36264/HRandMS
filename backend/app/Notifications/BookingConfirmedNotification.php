<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Booking model
     */
    private Booking $booking;

    /**
     * Email data
     */
    private array $emailData;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, array $emailData)
    {
        $this->booking = $booking;
        $this->emailData = $emailData;
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
        $guestNames = implode(', ', $this->emailData['guest_names']);

        return (new MailMessage)
            ->subject('Booking Confirmation - ' . $this->emailData['booking_reference'])
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your booking at {$this->emailData['hotel_name']} has been confirmed.")
            ->line('')
            ->line('<strong>Booking Details:</strong>')
            ->line("Booking Reference: {$this->emailData['booking_reference']}")
            ->line("Check-in: {$this->emailData['check_in_date']} at {$this->emailData['check_in_time']}")
            ->line("Check-out: {$this->emailData['check_out_date']} at {$this->emailData['check_out_time']}")
            ->line("Duration: {$this->emailData['nights']} night" . ($this->emailData['nights'] > 1 ? 's' : ''))
            ->line('')
            ->line('<strong>Room Information:</strong>')
            ->line("Room Type: {$this->emailData['room_type']}")
            ->line("Room Name: {$this->emailData['room_name']}")
            ->line("Capacity: {$this->emailData['room_capacity']} guests")
            ->line('')
            ->line('<strong>Guest Information:</strong>')
            ->line("Guests: {$guestNames}")
            ->when(!empty($this->emailData['special_requests']), function ($message) {
                return $message->line("Special Requests: {$this->emailData['special_requests']}");
            })
            ->line('')
            ->line('<strong>Payment Details:</strong>')
            ->line("Total Amount: {$this->emailData['total_price']} ETB")
            ->line("Payment Method: {$this->emailData['payment_method']}")
            ->line("Transaction ID: {$this->emailData['transaction_id']}")
            ->line('')
            ->line('<strong>Hotel Contact:</strong>')
            ->line("Phone: {$this->emailData['hotel_phone']}")
            ->line("Address: {$this->emailData['hotel_address']}")
            ->line('')
            ->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Please arrive 15 minutes before check-in.')
            ->line('For any changes or questions, please contact the hotel.')
            ->line('')
            ->line('Thank you for choosing ' . $this->emailData['hotel_name'] . '!')
            ->salutation('Best regards, ' . $this->emailData['hotel_name'] . ' Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->emailData['booking_reference'],
            'hotel_name' => $this->emailData['hotel_name'],
            'check_in_date' => $this->emailData['check_in_date'],
            'check_out_date' => $this->emailData['check_out_date'],
        ];
    }
}
