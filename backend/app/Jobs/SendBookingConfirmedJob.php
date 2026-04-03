<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBookingConfirmedJob implements ShouldQueue
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
            Log::warning("SendBookingConfirmedJob skipped — missing model for booking {$this->booking->booking_reference}.");
            return;
        }

        $emailData = [
            'booking_reference' => $booking->booking_reference,
            'hotel_name' => config('app.name'),
            'check_in_date' => $booking->check_in_date->format('Y-m-d'),
            'check_out_date' => $booking->check_out_date->format('Y-m-d'),
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'nights' => $booking->nights(),
            'room_type' => $booking->room->type ?? 'Standard',
            'room_name' => $booking->room->name,
            'room_capacity' => $booking->room->capacity ?? 2,
            'guest_names' => [$booking->user->name],
            'special_requests' => $booking->special_requests ?? '',
            'total_price' => $booking->final_amount,
            'payment_method' => $payment->method ?? 'online',
            'transaction_id' => $payment->transaction_id ?? 'N/A',
            'hotel_phone' => '+251-XXX-XXX-XXXX',
            'hotel_address' => 'Addis Ababa, Ethiopia',
        ];

        $booking->user->notify(new BookingConfirmedNotification($booking, $emailData));

        Log::info("SendBookingConfirmedJob sent to {$booking->user->email} for {$booking->booking_reference}.");
    }

    public function failed(Throwable $e): void
    {
        Log::error("SendBookingConfirmedJob permanently failed for {$this->booking->booking_reference}: {$e->getMessage()}");
    }

    public function backoff(): array
    {
        return [60, 120, 240];
    }
}
