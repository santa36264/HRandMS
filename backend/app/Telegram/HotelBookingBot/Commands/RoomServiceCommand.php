<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\MenuItem;
use App\Models\RoomServiceOrder;
use App\Models\RoomServiceOrderItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RoomServiceCommand
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
     * Hotel name
     */
    private string $hotelName = 'SATAAB Hotel';

    /**
     * Average preparation time
     */
    private const PREP_TIME_MINUTES = 30;

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Show menu categories
     */
    public function showMenuCategories(): array
    {
        try {
            $message = "🍽️ <b>Room Service Menu</b>\n\n";
            $message .= "Welcome to {$this->hotelName} Room Service!\n\n";
            $message .= "Select a category to browse our menu:\n";

            $buttons = [
                [
                    ['text' => '🍳 Breakfast', 'callback_data' => 'service_category_breakfast'],
                ],
                [
                    ['text' => '🍝 Main Course', 'callback_data' => 'service_category_main_course'],
                ],
                [
                    ['text' => '🥤 Drinks', 'callback_data' => 'service_category_drinks'],
                ],
                [
                    ['text' => '🍰 Dessert', 'callback_data' => 'service_category_dessert'],
                ],
                [
                    ['text' => '🛒 View Cart', 'callback_data' => 'service_cart'],
                    ['text' => '🔙 Back', 'callback_data' => 'service_main'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing menu categories', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading menu.',
            ];
        }
    }

    /**
     * Show menu items by category
     */
    public function showMenuByCategory(string $category): array
    {
        try {
            $items = MenuItem::where('category', $category)
                ->where('is_available', true)
                ->get();

            if ($items->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "❌ No items available in this category.",
                    'buttons' => [[
                        ['text' => '🔙 Back to Menu', 'callback_data' => 'service_categories'],
                    ]],
                ];
            }

            $categoryLabel = match ($category) {
                'breakfast' => '🍳 Breakfast',
                'main_course' => '🍝 Main Course',
                'drinks' => '🥤 Drinks',
                'dessert' => '🍰 Dessert',
                default => 'Menu',
            };

            $message = "<b>{$categoryLabel}</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $buttons = [];

            foreach ($items as $item) {
                $message .= "{$item->emoji} <b>{$item->name}</b>\n";
                $message .= "   {$item->description}\n";
                $message .= "   💰 {$item->price} ETB | ⏱️ {$item->preparation_time} min\n\n";

                $buttons[] = [
                    ['text' => "➕ Add {$item->name}", 'callback_data' => "service_add_{$item->id}"],
                ];
            }

            $buttons[] = [
                ['text' => '🛒 View Cart', 'callback_data' => 'service_cart'],
                ['text' => '🔙 Back', 'callback_data' => 'service_categories'],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing menu by category', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading menu items.',
            ];
        }
    }

    /**
     * Add item to cart
     */
    public function addToCart(int $itemId): array
    {
        try {
            $item = MenuItem::findOrFail($itemId);

            $cart = $this->getCart();
            $cart[$itemId] = ($cart[$itemId] ?? 0) + 1;
            $this->saveCart($cart);

            return [
                'success' => true,
                'message' => "✅ <b>{$item->name}</b> added to cart!\n\n"
                    . "💰 Price: {$item->price} ETB\n"
                    . "Quantity: 1",
                'buttons' => [[
                    ['text' => '➕ Add More', 'callback_data' => "service_add_{$itemId}"],
                    ['text' => '🛒 View Cart', 'callback_data' => 'service_cart'],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error adding to cart', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error adding item to cart.',
            ];
        }
    }

    /**
     * Show cart
     */
    public function showCart(): array
    {
        try {
            $cart = $this->getCart();

            if (empty($cart)) {
                return [
                    'success' => true,
                    'message' => "🛒 <b>Your Cart</b>\n\n"
                        . "Your cart is empty.\n"
                        . "Browse our menu to add items.",
                    'buttons' => [[
                        ['text' => '🍽️ Browse Menu', 'callback_data' => 'service_categories'],
                    ]],
                ];
            }

            $message = "🛒 <b>Your Cart</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $totalAmount = 0;
            $buttons = [];

            foreach ($cart as $itemId => $quantity) {
                $item = MenuItem::find($itemId);
                if (!$item) continue;

                $itemTotal = $item->price * $quantity;
                $totalAmount += $itemTotal;

                $message .= "{$item->emoji} <b>{$item->name}</b>\n";
                $message .= "   {$quantity}x {$item->price} ETB = {$itemTotal} ETB\n\n";

                $buttons[] = [
                    ['text' => "➖ Remove", 'callback_data' => "service_remove_{$itemId}"],
                    ['text' => "➕ Add", 'callback_data' => "service_add_{$itemId}"],
                ];
            }

            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💰 <b>Total: {$totalAmount} ETB</b>\n\n";

            $buttons[] = [
                ['text' => '✅ Confirm Order', 'callback_data' => 'service_confirm_order'],
                ['text' => '🔙 Continue Shopping', 'callback_data' => 'service_categories'],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
                'total' => $totalAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing cart', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading cart.',
            ];
        }
    }

    /**
     * Remove from cart
     */
    public function removeFromCart(int $itemId): array
    {
        try {
            $cart = $this->getCart();
            unset($cart[$itemId]);
            $this->saveCart($cart);

            return $this->showCart();
        } catch (\Exception $e) {
            Log::error('Error removing from cart', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error removing item from cart.',
            ];
        }
    }

    /**
     * Confirm order
     */
    public function confirmOrder(string $specialRequests = ''): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)
                ->where('status', 'checked_in')
                ->latest()
                ->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'message' => '❌ You are not currently checked in.',
                ];
            }

            $cart = $this->getCart();

            if (empty($cart)) {
                return [
                    'success' => false,
                    'message' => '❌ Your cart is empty.',
                ];
            }

            // Create order
            $totalAmount = 0;
            $maxPrepTime = 0;

            $order = RoomServiceOrder::create([
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
                'room_id' => $booking->room_id,
                'total_amount' => 0, // Will update
                'status' => 'pending',
                'special_requests' => $specialRequests,
                'ordered_at' => now(),
            ]);

            // Add items to order
            foreach ($cart as $itemId => $quantity) {
                $item = MenuItem::find($itemId);
                if (!$item) continue;

                $itemTotal = $item->price * $quantity;
                $totalAmount += $itemTotal;
                $maxPrepTime = max($maxPrepTime, $item->preparation_time);

                RoomServiceOrderItem::create([
                    'room_service_order_id' => $order->id,
                    'menu_item_id' => $itemId,
                    'quantity' => $quantity,
                    'unit_price' => $item->price,
                    'total_price' => $itemTotal,
                ]);
            }

            // Update order total and estimated delivery
            $estimatedDelivery = now()->addMinutes($maxPrepTime + 10);
            $order->update([
                'total_amount' => $totalAmount,
                'estimated_delivery_time' => $estimatedDelivery,
            ]);

            // Clear cart
            $this->clearCart();

            // Notify hotel staff
            $this->notifyHotelStaff($order, $booking);

            // Add to room bill
            $this->addToBill($booking, $totalAmount, $order->id);

            return [
                'success' => true,
                'message' => $this->formatOrderConfirmation($order, $estimatedDelivery),
                'buttons' => [[
                    ['text' => '🍽️ Order More', 'callback_data' => 'service_categories'],
                    ['text' => '🔙 Back', 'callback_data' => 'service_main'],
                ]],
                'order_id' => $order->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error confirming order', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error confirming order.',
            ];
        }
    }

    /**
     * Format order confirmation
     */
    private function formatOrderConfirmation(RoomServiceOrder $order, $estimatedDelivery): string
    {
        $message = "✅ <b>Order Confirmed!</b>\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "📌 <b>Order ID:</b> #{$order->id}\n";
        $message .= "🛏️ <b>Room:</b> {$order->room->name}\n";
        $message .= "💰 <b>Total:</b> {$order->total_amount} ETB\n\n";

        $message .= "📋 <b>Items:</b>\n";
        foreach ($order->items as $item) {
            $message .= "   • {$item->menuItem->name} x{$item->quantity}\n";
        }

        $message .= "\n⏱️ <b>Estimated Delivery:</b>\n";
        $message .= "   {$estimatedDelivery->format('H:i')} (in ~" . $estimatedDelivery->diffInMinutes(now()) . " minutes)\n\n";

        if ($order->special_requests) {
            $message .= "📝 <b>Special Requests:</b>\n";
            $message .= "   {$order->special_requests}\n\n";
        }

        $message .= "✨ Your order will be delivered to your room.\n";
        $message .= "Thank you for ordering from {$this->hotelName}!";

        return $message;
    }

    /**
     * Notify hotel staff
     */
    private function notifyHotelStaff(RoomServiceOrder $order, Booking $booking): void
    {
        try {
            Log::info('Room Service Order - Hotel Staff Notification', [
                'order_id' => $order->id,
                'room_id' => $order->room_id,
                'room_name' => $order->room->name,
                'guest_name' => $booking->user->name,
                'total_amount' => $order->total_amount,
                'items' => $order->items->map(fn($item) => [
                    'name' => $item->menuItem->name,
                    'quantity' => $item->quantity,
                ])->toArray(),
                'special_requests' => $order->special_requests,
                'ordered_at' => $order->ordered_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying hotel staff', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add to room bill
     */
    private function addToBill(Booking $booking, float $amount, int $orderId): void
    {
        try {
            // This would integrate with your billing system
            // For now, we log it for the hotel staff to process
            Log::info('Room Service Charge - Add to Bill', [
                'booking_id' => $booking->id,
                'room_id' => $booking->room_id,
                'order_id' => $orderId,
                'amount' => $amount,
                'description' => "Room Service Order #{$orderId}",
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding to bill', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cart from cache
     */
    private function getCart(): array
    {
        return Cache::get("room_service_cart_{$this->userId}", []);
    }

    /**
     * Save cart to cache
     */
    private function saveCart(array $cart): void
    {
        Cache::put("room_service_cart_{$this->userId}", $cart, now()->addHours(24));
    }

    /**
     * Clear cart
     */
    private function clearCart(): void
    {
        Cache::forget("room_service_cart_{$this->userId}");
    }
}
