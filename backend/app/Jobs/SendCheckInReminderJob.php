<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\CheckInReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCheckInReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying (exponential: 60s, 120s, 240s).
     */
    public int $backoff = 60;

    /**
     * Seconds before the job times out.
     */
    public int $timeout = 30;

    /**
     * Delete the job if the booking no longer exists.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly Booking $booking) {}

    public function handle(): void
    {
        // Re-check status in case it changed since dispatch
        $booking = $this->booking->fresh(['user', 'room']);

        if (! $booking || $booking->status !== 'confirmed') {
            Log::info("CheckInReminderJob skipped — booking {$this->booking->booking_reference} no longer confirmed.");
            return;
        }

        if (! $booking->user) {
            Log::warning("CheckInReminderJob skipped — no user on booking {$booking->booking_reference}.");
            return;
        }

        $booking->user->notify(new CheckInReminderNotification($booking));

        Log::info("CheckInReminderJob sent to {$booking->user->email} for {$booking->booking_reference}.");
    }

    public function failed(Throwable $e): void
    {
        Log::error("CheckInReminderJob permanently failed for {$this->booking->booking_reference}: {$e->getMessage()}");
    }

    /**
     * Exponential backoff: 60s → 120s → 240s.
     */
    public function backoff(): array
    {
        return [60, 120, 240];
    }
}
