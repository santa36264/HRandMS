<?php

namespace Tests\Feature\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\StaffNotification;
use App\Services\StaffNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class NotificationSendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * Test booking confirmation notification
     */
    public function test_booking_confirmation_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);

        // Trigger booking confirmation event
        event(new \App\Events\BookingConfirmed($booking));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\BookingConfirmedNotification::class);
    }

    /**
     * Test check-in reminder notification
     */
    public function test_checkin_reminder_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'check_in_date' => now()->addHours(2),
            'status' => 'confirmed',
        ]);

        // Trigger check-in reminder
        event(new \App\Events\CheckInReminder($booking));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\CheckInReminderNotification::class);
    }

    /**
     * Test payment receipt notification
     */
    public function test_payment_receipt_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);
        $payment = \App\Models\Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'completed',
        ]);

        // Trigger payment confirmation event
        event(new \App\Events\PaymentConfirmed($payment));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\PaymentReceiptNotification::class);
    }

    /**
     * Test review request notification
     */
    public function test_review_request_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'check_out_date' => now()->subDay(),
            'status' => 'completed',
        ]);

        // Trigger review request
        event(new \App\Events\ReviewRequested($booking));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\ReviewRequestNotification::class);
    }

    /**
     * Test staff notification on booking confirmed
     */
    public function test_staff_notification_on_booking_confirmed(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);

        // Trigger booking confirmation
        event(new \App\Events\BookingConfirmed($booking));

        // Verify staff notification was created
        $this->assertDatabaseHas('staff_notifications', [
            'booking_id' => $booking->id,
            'type' => 'booking_confirmed',
        ]);
    }

    /**
     * Test concierge booking confirmation notification
     */
    public function test_concierge_booking_confirmation_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $service = \App\Models\ConciergeService::factory()->create();
        $booking = \App\Models\ConciergeBooking::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'confirmed',
        ]);

        // Trigger concierge booking confirmation
        event(new \App\Events\ConciergeBookingConfirmed($booking));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\ConciergeBookingConfirmedNotification::class);
    }

    /**
     * Test email verification OTP notification
     */
    public function test_email_verification_otp_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $otp = \App\Models\EmailVerificationOtp::factory()->create([
            'user_id' => $user->id,
        ]);

        // Trigger OTP notification
        event(new \App\Events\EmailVerificationOtpSent($otp));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\EmailVerificationOtpNotification::class);
    }

    /**
     * Test booking cancelled notification
     */
    public function test_booking_cancelled_notification(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'cancelled',
        ]);

        // Trigger booking cancellation
        event(new \App\Events\BookingCancelled($booking));

        // Verify notification was sent
        Notification::assertSentTo($user, \App\Notifications\BookingCancelledNotification::class);
    }

    /**
     * Test multiple notifications sent in sequence
     */
    public function test_multiple_notifications_sent_in_sequence(): void
    {
        $user = User::factory()->create(['telegram_id' => 987654321]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);

        // Send multiple notifications
        event(new \App\Events\BookingConfirmed($booking));
        event(new \App\Events\CheckInReminder($booking));

        // Verify both notifications were sent
        Notification::assertSentTo($user, \App\Notifications\BookingConfirmedNotification::class);
        Notification::assertSentTo($user, \App\Notifications\CheckInReminderNotification::class);
    }
}
