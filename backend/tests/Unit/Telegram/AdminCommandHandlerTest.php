<?php

namespace Tests\Unit\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Telegram\HotelBookingBot\Commands\AdminCommandHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected int $adminTelegramId = 123456789;
    protected int $chatId = 123456789;

    /**
     * Test broadcast command
     */
    public function test_broadcast_command(): void
    {
        $handler = new AdminCommandHandler($this->chatId, $this->adminTelegramId);
        $result = $handler->handleBroadcast('Test message');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test stats command
     */
    public function test_stats_command(): void
    {
        User::factory()->count(5)->create();
        $room = Room::factory()->create();
        Booking::factory()->count(3)->create(['room_id' => $room->id]);

        $handler = new AdminCommandHandler($this->chatId, $this->adminTelegramId);
        $result = $handler->handleStats();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test check-in command
     */
    public function test_checkin_command(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);

        $handler = new AdminCommandHandler($this->chatId, $this->adminTelegramId);
        $result = $handler->handleCheckIn($booking->reference_number);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test check-out command
     */
    public function test_checkout_command(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'checked_in',
        ]);

        $handler = new AdminCommandHandler($this->chatId, $this->adminTelegramId);
        $result = $handler->handleCheckOut($room->room_number);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test assign room command
     */
    public function test_assign_room_command(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => null,
            'status' => 'confirmed',
        ]);

        $handler = new AdminCommandHandler($this->chatId, $this->adminTelegramId);
        $result = $handler->handleAssignRoom($room->room_number, $booking->reference_number);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test maintenance command
     */
    public function test_maintenance_command(): void
    {
        $room = Room::factory()->create();

        $handler = new AdminCommandHandler($this->chatId, $this->adminTelegramId);
        $result = $handler->handleMaintenance($room->room_number);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
