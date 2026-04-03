<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Room;
use App\Services\RoomService;
use App\Telegram\HotelBookingBot\Formatters\RoomDetailsFormatter;
use Illuminate\Support\Facades\Log;

class RoomDetailsCommand
{
    /**
     * Room service
     */
    private RoomService $roomService;

    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * User ID
     */
    private int $userId;

    /**
     * Constructor
     */
    public function __construct(RoomService $roomService, int $chatId, int $userId)
    {
        $this->roomService = $roomService;
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Get room details
     */
    public function getRoomDetails(int $roomId): array
    {
        try {
            $room = Room::findOrFail($roomId);

            if (!$room->is_active) {
                return [
                    'success' => false,
                    'message' => '❌ This room is no longer available.',
                ];
            }

            $roomData = [
                'id' => $room->id,
                'name' => $room->name,
                'type' => $room->type,
                'capacity' => $room->capacity,
                'floor' => $room->floor,
                'size' => $room->size,
                'description' => $room->description,
                'price' => $room->price_per_night,
                'discount_percentage' => $room->discount_percentage,
                'taxes_percentage' => $room->taxes_percentage,
                'amenities' => $room->amenities ?? [],
                'photos' => $this->getPhotoUrls($room),
                'rating' => $room->rating ?? 0,
                'review_count' => $room->reviews()->count(),
                'cancellation_policy' => $room->cancellation_policy ?? 'Free cancellation up to 24 hours before check-in',
                'deposit_policy' => $room->deposit_policy ?? 'No deposit required',
                'check_in_time' => $room->check_in_time ?? '14:00',
                'check_out_time' => $room->check_out_time ?? '11:00',
                'pet_policy' => $room->pet_policy,
                'smoking_policy' => $room->smoking_policy,
            ];

            $formatter = new RoomDetailsFormatter($roomData, $room);

            return [
                'success' => true,
                'message' => $formatter->getCompleteMessage(),
                'buttons' => $formatter->getActionButtons(),
                'photos' => $formatter->getMediaGroup(),
                'has_photos' => $formatter->hasPhotos(),
                'photo_count' => $formatter->getPhotoCount(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching room details', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '❌ Error loading room details. Please try again.',
            ];
        }
    }

    /**
     * Get photo URLs from room
     */
    private function getPhotoUrls(Room $room): array
    {
        $photos = [];

        // Get photos from room_photos table if available
        if (method_exists($room, 'photos') && $room->photos()->exists()) {
            $photos = $room->photos()
                ->orderBy('order')
                ->limit(4)
                ->pluck('photo_url')
                ->toArray();
        }

        // Fallback to photos JSON field if available
        if (empty($photos) && !empty($room->photos)) {
            $photos = is_array($room->photos) ? array_slice($room->photos, 0, 4) : [];
        }

        return $photos;
    }

    /**
     * Get similar rooms
     */
    public function getSimilarRooms(int $roomId, int $limit = 3): array
    {
        try {
            $room = Room::findOrFail($roomId);

            $similarRooms = Room::where('id', '!=', $roomId)
                ->where('type', $room->type)
                ->where('is_active', true)
                ->limit($limit)
                ->get()
                ->map(function (Room $r) {
                    return [
                        'id' => $r->id,
                        'name' => $r->name,
                        'type' => $r->type,
                        'price' => $r->price_per_night,
                        'capacity' => $r->capacity,
                        'rating' => $r->rating ?? 0,
                    ];
                })
                ->toArray();

            return $similarRooms;
        } catch (\Exception $e) {
            Log::error('Error fetching similar rooms', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Format similar rooms for display
     */
    public function formatSimilarRooms(array $similarRooms): string
    {
        if (empty($similarRooms)) {
            return '';
        }

        $message = "\n━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 <b>Similar Rooms</b>\n\n";

        foreach ($similarRooms as $room) {
            $message .= "🏠 <b>{$room['name']}</b>\n";
            $message .= "   💰 {$room['price']} ETB/night\n";
            $message .= "   🪑 {$room['capacity']} guests | ⭐ {$room['rating']}/5\n\n";
        }

        return $message;
    }

    /**
     * Get similar rooms keyboard
     */
    public function getSimilarRoomsKeyboard(array $similarRooms): array
    {
        $buttons = [];

        foreach ($similarRooms as $room) {
            $buttons[] = [
                ['text' => "📋 {$room['name']}", 'callback_data' => "room_details_{$room['id']}"],
            ];
        }

        return $buttons;
    }
}
