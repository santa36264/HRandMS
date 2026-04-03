<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class RoomServiceCallbackHandler
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
     * Constructor
     */
    public function __construct(
        int $chatId,
        int $userId,
        string $callbackData,
        string $callbackId
    ) {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
    }

    /**
     * Handle room service callback
     */
    public function handle(): array
    {
        try {
            $parts = explode('_', $this->callbackData);

            if (count($parts) < 2) {
                return [
                    'success' => false,
                    'callback_id' => $this->callbackId,
                    'message' => '❌ Invalid callback data',
                ];
            }

            $action = $parts[1];

            $response = match ($action) {
                'category' => $this->handleCategory($parts),
                'add' => $this->handleAddToCart($parts),
                'remove' => $this->handleRemoveFromCart($parts),
                'confirm' => $this->handleConfirmOrder($parts),
                'cart' => $this->handleShowCart(),
                'categories' => $this->handleShowCategories(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling room service callback', [
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
     * Handle show categories
     */
    private function handleShowCategories(): array
    {
        $command = new RoomServiceCommand($this->chatId, $this->userId);

        return $command->showMenuCategories();
    }

    /**
     * Handle category selection
     */
    private function handleCategory(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid category'];
        }

        // Join remaining parts in case category name has underscores
        $category = implode('_', array_slice($parts, 2));
        $command = new RoomServiceCommand($this->chatId, $this->userId);

        return $command->showMenuByCategory($category);
    }

    /**
     * Handle add to cart
     */
    private function handleAddToCart(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid item ID'];
        }

        $itemId = (int)$parts[2];
        $command = new RoomServiceCommand($this->chatId, $this->userId);

        return $command->addToCart($itemId);
    }

    /**
     * Handle remove from cart
     */
    private function handleRemoveFromCart(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid item ID'];
        }

        $itemId = (int)$parts[2];
        $command = new RoomServiceCommand($this->chatId, $this->userId);

        return $command->removeFromCart($itemId);
    }

    /**
     * Handle confirm order
     */
    private function handleConfirmOrder(array $parts): array
    {
        $command = new RoomServiceCommand($this->chatId, $this->userId);

        return $command->confirmOrder();
    }

    /**
     * Handle show cart
     */
    private function handleShowCart(): array
    {
        $command = new RoomServiceCommand($this->chatId, $this->userId);

        return $command->showCart();
    }
}
