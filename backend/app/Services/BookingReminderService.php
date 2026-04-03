<?php

namespace App\Services;

use App\Jobs\SendCheckInReminderJob;
use App\Jobs\SendReviewRequestJob;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class BookingReminderService
{
    /**
     * Dispatch check-in reminder jobs for all confirmed bookings
     * whose check-in date matches $date (default: tomorrow).
     *
     * Returns the number of jobs dispatched.
     */
    public function dispatchCheckInReminders(
        string $date = null,
        bool   $dryRun = false,
        string $queue = 'reminders',
    ): int {
        $date ??= now()->addDay()->toDateString();

        $count = 0;

        Booking::with(['user', 'room'])
            ->whereDate('check_in_date', $date)
            ->where('status', 'confirmed')
            ->whereHas('user')
            ->chunkById(100, function ($bookings) use ($dryRun, $queue, &$count) {
                foreach ($bookings as $booking) {
                    if ($dryRun) {
                        Log::info("[DRY-RUN] Would dispatch CheckInReminderJob for {$booking->booking_reference}");
                    } else {
                        SendCheckInReminderJob::dispatch($booking)
                            ->onQueue($queue)
                            ->delay(now()->addSeconds($count * 2)); // stagger by 2s each
                    }
                    $count++;
                }
            });

        Log::info("BookingReminderService: dispatched {$count} check-in reminder(s) for {$date}" . ($dryRun ? ' [DRY-RUN]' : '') . '.');

        return $count;
    }

    /**
     * Dispatch review request jobs for all checked-out bookings
     * whose check-out date matches $date (default: yesterday) and have no review yet.
     *
     * Returns the number of jobs dispatched.
     */
    public function dispatchReviewRequests(
        string $date = null,
        bool   $dryRun = false,
        string $queue = 'reminders',
    ): int {
        $date ??= now()->subDay()->toDateString();

        $count = 0;

        Booking::with(['user', 'room'])
            ->whereDate('check_out_date', $date)
            ->where('status', 'checked_out')
            ->whereDoesntHave('review')
            ->whereHas('user')
            ->chunkById(100, function ($bookings) use ($dryRun, $queue, &$count) {
                foreach ($bookings as $booking) {
                    if ($dryRun) {
                        Log::info("[DRY-RUN] Would dispatch ReviewRequestJob for {$booking->booking_reference}");
                    } else {
                        SendReviewRequestJob::dispatch($booking)
                            ->onQueue($queue)
                            ->delay(now()->addSeconds($count * 2));
                    }
                    $count++;
                }
            });

        Log::info("BookingReminderService: dispatched {$count} review request(s) for {$date}" . ($dryRun ? ' [DRY-RUN]' : '') . '.');

        return $count;
    }
}
