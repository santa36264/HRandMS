<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\ReviewRequestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendReviewRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;
    public bool $deleteWhenMissingModels = true;

    public function __construct(private readonly Booking $booking) {}

    public function handle(): void
    {
        $booking = $this->booking->fresh(['user', 'room']);

        if (! $booking || $booking->status !== 'checked_out') {
            Log::info("ReviewRequestJob skipped — booking {$this->booking->booking_reference} not checked_out.");
            return;
        }

        // Skip if guest already submitted a review since dispatch
        if ($booking->review()->exists()) {
            Log::info("ReviewRequestJob skipped — review already exists for {$booking->booking_reference}.");
            return;
        }

        if (! $booking->user) {
            Log::warning("ReviewRequestJob skipped — no user on booking {$booking->booking_reference}.");
            return;
        }

        $booking->user->notify(new ReviewRequestNotification($booking));

        Log::info("ReviewRequestJob sent to {$booking->user->email} for {$booking->booking_reference}.");
    }

    public function failed(Throwable $e): void
    {
        Log::error("ReviewRequestJob permanently failed for {$this->booking->booking_reference}: {$e->getMessage()}");
    }

    public function backoff(): array
    {
        return [60, 120, 240];
    }
}
