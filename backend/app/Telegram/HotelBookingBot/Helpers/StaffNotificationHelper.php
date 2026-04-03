<?php

namespace App\Telegram\HotelBookingBot\Helpers;

use App\Services\StaffNotificationService;
use Illuminate\Support\Facades\Log;

class StaffNotificationHelper
{
    private StaffNotificationService $service;

    public function __construct()
    {
        $this->service = new StaffNotificationService();
    }

    /**
     * Notify staff of housekeeping request
     */
    public function notifyHousekeepingRequest(int $roomNumber, string $time): void
    {
        try {
            $this->service->notifyServiceRequest($roomNumber, 'housekeeping', $time);
        } catch (\Exception $e) {
            Log::error('Error notifying housekeeping request', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify staff of maintenance request
     */
    public function notifyMaintenanceRequest(int $roomNumber, string $issue, string $time): void
    {
        try {
            $this->service->notifyServiceRequest($roomNumber, 'maintenance', $time);
        } catch (\Exception $e) {
            Log::error('Error notifying maintenance request', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify staff of room service order
     */
    public function notifyRoomServiceOrder(int $roomNumber, string $items, float $amount): void
    {
        try {
            $message = "🍽️ <b>Room Service Order</b>\n\n";
            $message .= "🏠 <b>Room:</b> #{$roomNumber}\n";
            $message .= "📋 <b>Items:</b> {$items}\n";
            $message .= "💰 <b>Amount:</b> {$amount} ETB\n";
            $message .= "\n<b>Action Required:</b> Prepare and deliver order.\n";

            Log::info('Staff Notification - Room Service Order', [
                'room_number' => $roomNumber,
                'items' => $items,
                'amount' => $amount,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying room service order', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify staff of concierge booking
     */
    public function notifyConciergeBooking(string $serviceName, int $roomNumber, string $time): void
    {
        try {
            $message = "🎩 <b>Concierge Service Booking</b>\n\n";
            $message .= "🏠 <b>Room:</b> #{$roomNumber}\n";
            $message .= "🎯 <b>Service:</b> {$serviceName}\n";
            $message .= "⏰ <b>Time:</b> {$time}\n";
            $message .= "\n<b>Action Required:</b> Coordinate with service provider.\n";

            Log::info('Staff Notification - Concierge Booking', [
                'service_name' => $serviceName,
                'room_number' => $roomNumber,
                'time' => $time,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying concierge booking', ['error' => $e->getMessage()]);
        }
    }
}
