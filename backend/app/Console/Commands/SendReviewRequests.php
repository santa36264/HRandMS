<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\ReviewCollectionSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReviewRequests extends Command
{
    protected $signature = 'reviews:send-requests';
    protected $description = 'Send review requests to guests who checked out';

    public function handle()
    {
        try {
            $this->sendReviewRequests();
            $this->info('Review requests sent successfully.');
        } catch (\Exception $e) {
            Log::error('Error sending review requests', ['error' => $e->getMessage()]);
            $this->error('Error sending review requests: ' . $e->getMessage());
        }
    }

    /**
     * Send review requests to checked-out guests
     */
    private function sendReviewRequests(): void
    {
        // Get bookings checked out in the last 2 hours
        $checkoutTime = now()->subHours(2);

        $bookings = Booking::where('status', 'checked_out')
            ->where('check_out_date', '>=', $checkoutTime)
            ->where('check_out_date', '<=', now())
            ->whereHas('user', function ($query) {
                $query->whereNotNull('telegram_id');
            })
            ->get();

        foreach ($bookings as $booking) {
            // Check if review session already exists
            $existingSession = ReviewCollectionSession::where('booking_id', $booking->id)->first();

            if ($existingSession) {
                continue;
            }

            // Create review collection session
            $session = ReviewCollectionSession::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'status' => 'pending',
            ]);

            Log::info('Review Request Sent', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'session_id' => $session->id,
            ]);
        }
    }
}
