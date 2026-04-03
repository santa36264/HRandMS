<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\StaffNotification;
use Illuminate\Support\Facades\Log;

class StaffNotificationService
{
    private string $staffGroupId;

    public function __construct()
    {
        $this->staffGroupId = config('telegram.staff_group_id', env('TELEGRAM_STAFF_GROUP_ID'));
    }

    /**
     * Notify new booking
     */
    public function notifyNewBooking(Booking $booking): void
    {
        try {
            $message = $this->formatNewBookingMessage($booking);

            $notification = StaffNotification::create([
                'type' => 'booking',
                'title' => 'New Booking Confirmed',
                'message' => $message,
                'data' => [
                    'booking_id' => $booking->id,
                    'room_id' => $booking->room_id,
                    'guest_name' => $booking->user->name,
                    'check_in' => $booking->check_in_date,
                    'check_out' => $booking->check_out_date,
                ],
                'status' => 'pending',
                'booking_id' => $booking->id,
            ]);

            $this->sendToStaffGroup($message, $notification->id);
        } catch (\Exception $e) {
            Log::error('Error notifying new booking', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify service request
     */
    public function notifyServiceRequest(int $roomNumber, string $serviceType, string $time): void
    {
        try {
            $message = $this->formatServiceRequestMessage($roomNumber, $serviceType, $time);

            $notification = StaffNotification::create([
                'type' => 'service_request',
                'title' => "Room {$roomNumber} Service Request",
                'message' => $message,
                'data' => [
                    'room_number' => $roomNumber,
                    'service_type' => $serviceType,
                    'time' => $time,
                ],
                'status' => 'pending',
            ]);

            $this->sendToStaffGroup($message, $notification->id);
        } catch (\Exception $e) {
            Log::error('Error notifying service request', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send daily summary
     */
    public function sendDailySummary(): void
    {
        try {
            $summary = $this->generateDailySummary();
            $message = $summary['message'];

            $notification = StaffNotification::create([
                'type' => 'daily_summary',
                'title' => 'Daily Operations Summary',
                'message' => $message,
                'data' => $summary['data'],
                'status' => 'pending',
            ]);

            $this->sendToStaffGroup($message, $notification->id);
        } catch (\Exception $e) {
            Log::error('Error sending daily summary', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format new booking message
     */
    private function formatNewBookingMessage(Booking $booking): string
    {
        $message = "🆕 <b>New Booking Confirmed</b>\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "🏠 <b>Room:</b> {$booking->room->name} (#{$booking->room_id})\n";
        $message .= "👤 <b>Guest:</b> {$booking->user->name}\n";
        $message .= "📧 <b>Email:</b> {$booking->user->email}\n";
        $message .= "📱 <b>Phone:</b> {$booking->user->phone}\n";
        $message .= "📅 <b>Check-in:</b> {$booking->check_in_date->format('M d, Y H:i')}\n";
        $message .= "📅 <b>Check-out:</b> {$booking->check_out_date->format('M d, Y H:i')}\n";
        $message .= "👥 <b>Guests:</b> {$booking->guest_count}\n";
        $message .= "💰 <b>Total:</b> {$booking->total_price} ETB\n";

        if ($booking->special_requests) {
            $message .= "\n📝 <b>Special Requests:</b>\n{$booking->special_requests}\n";
        }

        $message .= "\n📌 <b>Booking ID:</b> #{$booking->id}\n";
        $message .= "🔗 <b>Reference:</b> {$booking->booking_reference}\n";

        return $message;
    }

    /**
     * Format service request message
     */
    private function formatServiceRequestMessage(int $roomNumber, string $serviceType, string $time): string
    {
        $serviceLabel = match ($serviceType) {
            'housekeeping' => '🧹 Housekeeping',
            'maintenance' => '🔧 Maintenance',
            'room_service' => '🍽️ Room Service',
            'concierge' => '🎩 Concierge',
            default => ucfirst($serviceType),
        };

        $message = "🔔 <b>Room Service Request</b>\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "🏠 <b>Room:</b> #{$roomNumber}\n";
        $message .= "🎯 <b>Service:</b> {$serviceLabel}\n";
        $message .= "⏰ <b>Time:</b> {$time}\n";
        $message .= "\n<b>Action Required:</b> Please assign staff to handle this request.\n";

        return $message;
    }

    /**
     * Generate daily summary
     */
    private function generateDailySummary(): array
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        // Today's check-ins
        $checkIns = Booking::whereBetween('check_in_date', [$today, $tomorrow])
            ->where('status', 'confirmed')
            ->count();

        // Today's check-outs
        $checkOuts = Booking::whereBetween('check_out_date', [$today, $tomorrow])
            ->where('status', 'checked_in')
            ->count();

        // Current occupancy
        $totalRooms = \App\Models\Room::count();
        $occupiedRooms = Booking::where('status', 'checked_in')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Pending requests
        $pendingNotifications = StaffNotification::where('status', 'pending')
            ->where('created_at', '>=', $today)
            ->count();

        $message = "📊 <b>Daily Operations Summary</b>\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "📅 <b>Date:</b> " . now()->format('M d, Y') . "\n\n";

        $message .= "✅ <b>Today's Check-ins:</b> {$checkIns}\n";
        $message .= "🚪 <b>Today's Check-outs:</b> {$checkOuts}\n";
        $message .= "🏨 <b>Current Occupancy:</b> {$occupiedRooms}/{$totalRooms} ({$occupancyRate}%)\n";
        $message .= "⏳ <b>Pending Requests:</b> {$pendingNotifications}\n";

        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Have a productive day! 💪\n";

        return [
            'message' => $message,
            'data' => [
                'check_ins' => $checkIns,
                'check_outs' => $checkOuts,
                'occupied_rooms' => $occupiedRooms,
                'total_rooms' => $totalRooms,
                'occupancy_rate' => $occupancyRate,
                'pending_requests' => $pendingNotifications,
            ],
        ];
    }

    /**
     * Send message to staff group
     */
    private function sendToStaffGroup(string $message, int $notificationId): void
    {
        try {
            Log::info('Staff Group Notification', [
                'group_id' => $this->staffGroupId,
                'notification_id' => $notificationId,
                'message' => $message,
            ]);

            // Update notification status
            StaffNotification::find($notificationId)?->update(['status' => 'sent']);
        } catch (\Exception $e) {
            Log::error('Error sending to staff group', ['error' => $e->getMessage()]);
        }
    }
}
