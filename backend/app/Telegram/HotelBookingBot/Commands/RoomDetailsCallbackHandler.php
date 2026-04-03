<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Services\RoomService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RoomDetailsCallbackHandler
{
    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * User ID
     */
    private int $userId;

    /**
     * Callback data
     */
    private string $callbackData;

    /**
     * Callback ID
     */
    private string $callbackId;

    /**
     * Room service
     */
    private RoomService $roomService;

    /**
     * Constructor
     */
    public function __construct(
        int $chatId,
        int $userId,
        string $callbackData,
        string $callbackId,
        RoomService $roomService
    ) {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
        $this->roomService = $roomService;
    }

    /**
     * Handle room details callback
     */
    public function handle(): array
    {
        try {
            $parts = explode('_', $this->callbackData);

            if (count($parts) < 3) {
                return [
                    'success' => false,
                    'callback_id' => $this->callbackId,
                    'message' => '❌ Invalid callback data',
                ];
            }

            $action = $parts[1];
            $roomId = (int)$parts[2];

            $response = match ($action) {
                'details' => $this->handleViewDetails($roomId),
                'favorite' => $this->handleAddToFavorites($roomId),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling room details callback', [
                'error' => $e->getMessage(),
                'callback_data' => $this->callbackData,
            ]);

            return [
                'success' => false,
                'callback_id' => $this->callbackId,
                'message' => '❌ Error processing request',
            ];
        }
    }

    /**
     * Handle view details
     */
    private function handleViewDetails(int $roomId): array
    {
        $roomDetailsCommand = new RoomDetailsCommand(
            $this->roomService,
            $this->chatId,
            $this->userId
        );

        $result = $roomDetailsCommand->getRoomDetails($roomId);

        if (!$result['success']) {
            return ['message' => $result['message']];
        }

        return [
            'message' => $result['message'],
            'buttons' => $result['buttons'],
            'photos' => $result['photos'],
            'has_photos' => $result['has_photos'],
            'photo_count' => $result['photo_count'],
        ];
    }

    /**
     * Handle add to favorites
     */
    private function handleAddToFavorites(int $roomId): array
    {
        try {
            $user = User::where('telegram_user_id', $this->userId)->first();

            if (!$user) {
                return [
                    'message' => "❌ Please register first to add favorites.\n\n"
                        . "Use the Register button to get started.",
                ];
            }

            // Check if already favorited
            $isFavorited = $user->favorites()
                ->where('room_id', $roomId)
                ->exists();

            if ($isFavorited) {
                // Remove from favorites
                $user->favorites()
                    ->where('room_id', $roomId)
                    ->delete();

                return [
                    'message' => "❌ Removed from favorites",
                    'action' => 'removed',
                ];
            } else {
                // Add to favorites
                $user->favorites()->attach($roomId);

                return [
                    'message' => "⭐ Added to favorites!",
                    'action' => 'added',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error adding to favorites', [
                'room_id' => $roomId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'message' => '❌ Error updating favorites. Please try again.',
            ];
        }
    }
}
