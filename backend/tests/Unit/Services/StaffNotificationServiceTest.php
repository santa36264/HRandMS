<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\StaffNotification;
use App\Services\StaffNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test notify booking confirmed
     */
    public function test_notify_booking_confirmed(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        StaffNotificationService::notifyBookingConfirmed($booking);

        $this->assertDatabaseHas('staff_notifications', [
            'booking_id' => $booking->id,
            'type' => 'booking_confirmed',
        ]);
    }

    /**
     * Test notify room service request
     */
    public function test_notify_room_service_request(): void
    {
        $user = User::factory()->create();
        $order = \App\Models\RoomServiceOrder::factory()->create([
            'user_id' => $user->id,
        ]);

        StaffNotificationService::notifyRoomServiceRequest($order);

        $this->assertDatabaseHas('staff_notifications', [
            'room_service_order_id' => $order->id,
            'type' => 'room_service_request',
        ]);
    }

    /**
     * Test notify concierge booking
     */
    public function test_notify_concierge_booking(): void
    {
        $user = User::factory()->create();
        $service = \App\Models\ConciergeService::factory()->create();
        $booking = \App\Models\ConciergeBooking::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
        ]);

        StaffNotificationService::notifyConciergeBooking($booking);

        $this->assertDatabaseHas('staff_notifications', [
            'concierge_booking_id' => $booking->id,
            'type' => 'concierge_booking',
        ]);
    }

    /**
     * Test get pending notifications
     */
    public function test_get_pending_notifications(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        StaffNotificationService::notifyBookingConfirmed($booking);

        $notifications = StaffNotificationService::getPendingNotifications();

        $this->assertGreaterThan(0, count($notifications));
    }

    /**
     * Test mark notification as sent
     */
    public function test_mark_notification_as_sent(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        StaffNotificationService::notifyBookingConfirmed($booking);

        $notification = StaffNotification::first();
        StaffNotificationService::markAsSent($notification->id);

        $this->assertDatabaseHas('staff_notifications', [
            'id' => $notification->id,
            'sent_at' => now(),
        ]);
    }

    /**
     * Test get daily summary
     */
    public function test_get_daily_summary(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'check_in_date' => now(),
        ]);

        $summary = StaffNotificationService::getDailySummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('check_ins', $summary);
        $this->assertArrayHasKey('check_outs', $summary);
    }
}
