<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Services\StaffNotificationService;
use Illuminate\Support\Facades\Log;

class NotifyStaffOnBookingConfirmed
{
    private StaffNotificationService $notificationService;

    public function __construct(StaffNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(BookingConfirmed $event): void
    {
        try {
            $this->notificationService->notifyNewBooking($event->booking);
        } catch (\Exception $e) {
            Log::error('Error notifying staff on booking confirmed', ['error' => $e->getMessage()]);
        }
    }
}
