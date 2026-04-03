<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Telegram\HotelBookingBot\Middleware\AdminAuthMiddleware;
use Illuminate\Support\Facades\Log;

class AdminCommandHandler
{
    private int $chatId;
    private int $userId;
    private string $command;
    private array $params;

    public function __construct(int $chatId, int $userId, string $command, array $params = [])
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->command = $command;
        $this->params = $params;
    }

    /**
     * Handle admin command
     */
    public function handle(): array
    {
        // Check admin authorization
        if (!AdminAuthMiddleware::isAdmin($this->userId)) {
            AdminAuthMiddleware::logUnauthorizedAccess($this->userId, $this->command);
            return [
                'success' => false,
                'message' => AdminAuthMiddleware::getUnauthorizedMessage(),
            ];
        }

        return match ($this->command) {
            'broadcast' => $this->handleBroadcast(),
            'stats' => $this->handleStats(),
            'checkin' => $this->handleCheckIn(),
            'checkout' => $this->handleCheckOut(),
            'assign' => $this->handleAssign(),
            'maintenance' => $this->handleMaintenance(),
            default => [
                'success' => false,
                'message' => '❌ Unknown admin command.',
            ],
        };
    }

    /**
     * Handle broadcast command
     */
    private function handleBroadcast(): array
    {
        try {
            if (empty($this->params)) {
                return [
                    'success' => false,
                    'message' => '❌ Usage: /broadcast [message]\n\nExample: /broadcast Welcome to SATAAB Hotel!',
                ];
            }

            $message = implode(' ', $this->params);
            $users = User::where('telegram_id', '!=', null)->get();

            if ($users->isEmpty()) {
                return [
                    'success' => false,
                    'message' => '❌ No users to broadcast to.',
                ];
            }

            $broadcastMessage = "📢 <b>Announcement from Management</b>\n\n";
            $broadcastMessage .= "{$message}\n\n";
            $broadcastMessage .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $broadcastMessage .= "Thank you for staying with us!";

            Log::info('Admin Broadcast', [
                'admin_id' => $this->userId,
                'user_count' => $users->count(),
                'message' => $message,
            ]);

            return [
                'success' => true,
                'message' => "✅ <b>Broadcast Scheduled</b>\n\n"
                    . "Message will be sent to {$users->count()} users.\n\n"
                    . "<b>Message Preview:</b>\n{$broadcastMessage}",
                'broadcast_data' => [
                    'message' => $broadcastMessage,
                    'user_ids' => $users->pluck('telegram_id')->toArray(),
                    'count' => $users->count(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error handling broadcast', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing broadcast.',
            ];
        }
    }

    /**
     * Handle stats command
     */
    private function handleStats(): array
    {
        try {
            $today = now()->startOfDay();
            $tomorrow = now()->addDay()->startOfDay();

            // Today's bookings
            $todayBookings = Booking::whereBetween('created_at', [$today, $tomorrow])->count();

            // Occupancy rate
            $totalRooms = Room::count();
            $occupiedRooms = Booking::where('status', 'checked_in')->count();
            $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

            // Revenue today
            $revenueToday = Booking::whereBetween('created_at', [$today, $tomorrow])
                ->where('status', '!=', 'cancelled')
                ->sum('total_price');

            // Pending payments
            $pendingPayments = Booking::where('payment_status', 'pending')
                ->where('status', 'confirmed')
                ->count();

            $message = "📊 <b>Quick Metrics</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📅 <b>Today's Bookings:</b> {$todayBookings}\n";
            $message .= "🏨 <b>Occupancy Rate:</b> {$occupancyRate}% ({$occupiedRooms}/{$totalRooms})\n";
            $message .= "💰 <b>Revenue Today:</b> {$revenueToday} ETB\n";
            $message .= "⏳ <b>Pending Payments:</b> {$pendingPayments}\n";
            $message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "Generated: " . now()->format('M d, Y H:i') . "\n";

            Log::info('Admin Stats Requested', [
                'admin_id' => $this->userId,
                'today_bookings' => $todayBookings,
                'occupancy_rate' => $occupancyRate,
                'revenue_today' => $revenueToday,
                'pending_payments' => $pendingPayments,
            ]);

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling stats', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error retrieving statistics.',
            ];
        }
    }

    /**
     * Handle check-in command
     */
    private function handleCheckIn(): array
    {
        try {
            if (empty($this->params)) {
                return [
                    'success' => false,
                    'message' => '❌ Usage: /checkin [booking_reference]\n\nExample: /checkin BK20260325001',
                ];
            }

            $bookingRef = $this->params[0];
            $booking = Booking::where('booking_reference', $bookingRef)->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'message' => "❌ Booking not found: {$bookingRef}",
                ];
            }

            if ($booking->status === 'checked_in') {
                return [
                    'success' => false,
                    'message' => "⚠️ Guest already checked in.\n\nBooking: {$booking->booking_reference}",
                ];
            }

            $booking->update([
                'status' => 'checked_in',
                'check_in_date' => now(),
            ]);

            Log::info('Admin Manual Check-in', [
                'admin_id' => $this->userId,
                'booking_id' => $booking->id,
                'booking_reference' => $bookingRef,
            ]);

            $message = "✅ <b>Check-in Successful</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📌 <b>Booking:</b> {$booking->booking_reference}\n";
            $message .= "👤 <b>Guest:</b> {$booking->user->name}\n";
            $message .= "🏠 <b>Room:</b> {$booking->room->name}\n";
            $message .= "⏰ <b>Check-in Time:</b> " . now()->format('M d, Y H:i') . "\n";

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling check-in', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing check-in.',
            ];
        }
    }

    /**
     * Handle check-out command
     */
    private function handleCheckOut(): array
    {
        try {
            if (empty($this->params)) {
                return [
                    'success' => false,
                    'message' => '❌ Usage: /checkout [room_number]\n\nExample: /checkout 205',
                ];
            }

            $roomNumber = $this->params[0];
            $booking = Booking::whereHas('room', function ($query) use ($roomNumber) {
                $query->where('room_number', $roomNumber);
            })
            ->where('status', 'checked_in')
            ->latest()
            ->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'message' => "❌ No active booking found for room: {$roomNumber}",
                ];
            }

            $booking->update([
                'status' => 'checked_out',
                'check_out_date' => now(),
            ]);

            Log::info('Admin Manual Check-out', [
                'admin_id' => $this->userId,
                'booking_id' => $booking->id,
                'room_number' => $roomNumber,
            ]);

            $message = "✅ <b>Check-out Successful</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📌 <b>Booking:</b> {$booking->booking_reference}\n";
            $message .= "👤 <b>Guest:</b> {$booking->user->name}\n";
            $message .= "🏠 <b>Room:</b> {$booking->room->name}\n";
            $message .= "⏰ <b>Check-out Time:</b> " . now()->format('M d, Y H:i') . "\n";
            $message .= "💰 <b>Total Charged:</b> {$booking->total_price} ETB\n";

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling check-out', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing check-out.',
            ];
        }
    }

    /**
     * Handle assign command
     */
    private function handleAssign(): array
    {
        try {
            if (count($this->params) < 2) {
                return [
                    'success' => false,
                    'message' => '❌ Usage: /assign [room_number] [booking_reference]\n\nExample: /assign 205 BK20260325001',
                ];
            }

            $roomNumber = $this->params[0];
            $bookingRef = $this->params[1];

            $room = Room::where('room_number', $roomNumber)->first();
            if (!$room) {
                return [
                    'success' => false,
                    'message' => "❌ Room not found: {$roomNumber}",
                ];
            }

            $booking = Booking::where('booking_reference', $bookingRef)->first();
            if (!$booking) {
                return [
                    'success' => false,
                    'message' => "❌ Booking not found: {$bookingRef}",
                ];
            }

            $booking->update(['room_id' => $room->id]);

            Log::info('Admin Room Assignment', [
                'admin_id' => $this->userId,
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'room_number' => $roomNumber,
            ]);

            $message = "✅ <b>Room Assigned</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📌 <b>Booking:</b> {$booking->booking_reference}\n";
            $message .= "👤 <b>Guest:</b> {$booking->user->name}\n";
            $message .= "🏠 <b>Room:</b> {$room->name} (#{$room->room_number})\n";
            $message .= "⏰ <b>Assigned At:</b> " . now()->format('M d, Y H:i') . "\n";

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling assign', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error assigning room.',
            ];
        }
    }

    /**
     * Handle maintenance command
     */
    private function handleMaintenance(): array
    {
        try {
            if (empty($this->params)) {
                return [
                    'success' => false,
                    'message' => '❌ Usage: /maintenance [room_number]\n\nExample: /maintenance 205',
                ];
            }

            $roomNumber = $this->params[0];
            $room = Room::where('room_number', $roomNumber)->first();

            if (!$room) {
                return [
                    'success' => false,
                    'message' => "❌ Room not found: {$roomNumber}",
                ];
            }

            $currentStatus = $room->status;
            $newStatus = $currentStatus === 'maintenance' ? 'available' : 'maintenance';

            $room->update(['status' => $newStatus]);

            Log::info('Admin Room Maintenance Status', [
                'admin_id' => $this->userId,
                'room_id' => $room->id,
                'room_number' => $roomNumber,
                'previous_status' => $currentStatus,
                'new_status' => $newStatus,
            ]);

            $statusEmoji = $newStatus === 'maintenance' ? '🔧' : '✅';
            $statusText = $newStatus === 'maintenance' ? 'Under Maintenance' : 'Available';

            $message = "{$statusEmoji} <b>Room Status Updated</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "🏠 <b>Room:</b> {$room->name} (#{$room->room_number})\n";
            $message .= "📊 <b>Status:</b> {$statusText}\n";
            $message .= "⏰ <b>Updated At:</b> " . now()->format('M d, Y H:i') . "\n";

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling maintenance', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error updating room status.',
            ];
        }
    }
}
