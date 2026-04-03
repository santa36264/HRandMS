<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Promotion;
use App\Models\PromotionBroadcast;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PromotionCommand
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
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Show promotion subscription menu
     */
    public function showPromotionMenu(): array
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '❌ User not found.',
                ];
            }

            $message = "📢 <b>Promotion Preferences</b>\n\n";
            $message .= "Manage your promotion subscriptions:\n\n";

            $message .= "📌 <b>Available Promotions:</b>\n";
            $message .= "• 🔥 Last-Minute Deals (24-48 hours)\n";
            $message .= "• 🎄 Seasonal Discounts\n";
            $message .= "• 💎 Loyalty Program Offers\n\n";

            $subscribed = $user->promotionSubscriptions()->where('opted_in', true)->count();
            $message .= "Currently subscribed to: <b>{$subscribed}</b> promotion types\n\n";

            $message .= "Toggle your preferences below:";

            $buttons = [
                [
                    ['text' => '🔥 Last-Minute Deals', 'callback_data' => 'promo_toggle_last_minute'],
                    ['text' => '🎄 Seasonal Discounts', 'callback_data' => 'promo_toggle_seasonal'],
                ],
                [
                    ['text' => '💎 Loyalty Offers', 'callback_data' => 'promo_toggle_loyalty'],
                ],
                [
                    ['text' => '🔙 Back to Menu', 'callback_data' => 'menu_main'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing promotion menu', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading promotion preferences.',
            ];
        }
    }

    /**
     * Toggle promotion subscription
     */
    public function togglePromotion(string $type): array
    {
        try {
            $user = User::find($this->userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '❌ User not found.',
                ];
            }

            // Map type to promotion type
            $promotionType = match ($type) {
                'last_minute' => 'last_minute_deal',
                'seasonal' => 'seasonal_discount',
                'loyalty' => 'loyalty_offer',
                default => null,
            };

            if (!$promotionType) {
                return [
                    'success' => false,
                    'message' => '❌ Invalid promotion type.',
                ];
            }

            // Get or create subscription
            $subscription = $user->promotionSubscriptions()
                ->whereHas('promotion', function ($q) use ($promotionType) {
                    $q->where('type', $promotionType);
                })
                ->first();

            if ($subscription) {
                // Toggle subscription
                $subscription->pivot->update(['opted_in' => !$subscription->pivot->opted_in]);
                $status = $subscription->pivot->opted_in ? 'subscribed' : 'unsubscribed';
            } else {
                // Create new subscription
                $promotion = Promotion::where('type', $promotionType)->first();
                if ($promotion) {
                    $user->promotionSubscriptions()->attach($promotion, ['opted_in' => true]);
                    $status = 'subscribed';
                } else {
                    return [
                        'success' => false,
                        'message' => '❌ Promotion type not available.',
                    ];
                }
            }

            $typeLabel = match ($type) {
                'last_minute' => '🔥 Last-Minute Deals',
                'seasonal' => '🎄 Seasonal Discounts',
                'loyalty' => '💎 Loyalty Offers',
            };

            $message = "✅ <b>Preference Updated</b>\n\n";
            $message .= "You have been <b>{$status}</b> from {$typeLabel}.\n\n";
            $message .= "You'll receive notifications about these offers via Telegram.";

            return [
                'success' => true,
                'message' => $message,
                'buttons' => [[
                    ['text' => '📢 Back to Preferences', 'callback_data' => 'promo_menu'],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error toggling promotion', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error updating preferences.',
            ];
        }
    }

    /**
     * Send promotion to user
     */
    public function sendPromotion(Promotion $promotion, string $variant = null): array
    {
        try {
            $user = User::find($this->userId);

            if (!$user || !$user->telegram_user_id) {
                return [
                    'success' => false,
                    'message' => 'User not found or no Telegram linked.',
                ];
            }

            $message = $this->formatPromotionMessage($promotion, $variant);
            $buttons = $this->getPromotionButtons($promotion);

            // Send message
            $bot = app('telegram.bot');
            $bot->sendMessage([
                'chat_id' => $user->telegram_user_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $buttons,
                ]),
            ]);

            // Record broadcast
            $broadcast = PromotionBroadcast::create([
                'promotion_id' => $promotion->id,
                'user_id' => $user->id,
                'variant' => $variant,
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            Log::info('Promotion sent', [
                'promotion_id' => $promotion->id,
                'user_id' => $user->id,
                'variant' => $variant,
            ]);

            return [
                'success' => true,
                'message' => $message,
                'broadcast_id' => $broadcast->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending promotion', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error sending promotion.',
            ];
        }
    }

    /**
     * Format promotion message
     */
    private function formatPromotionMessage(Promotion $promotion, ?string $variant = null): string
    {
        $message = "🎉 <b>Special Offer for You!</b>\n\n";

        $message .= "<b>{$promotion->title}</b>\n";
        $message .= "{$promotion->description}\n\n";

        // Discount info
        if ($promotion->discount_percentage) {
            $message .= "💰 <b>Discount:</b> {$promotion->discount_percentage}% OFF\n";
        } elseif ($promotion->discount_amount) {
            $message .= "💰 <b>Discount:</b> {$promotion->discount_amount} ETB OFF\n";
        }

        // Promo code
        if ($promotion->code) {
            $message .= "🎟️ <b>Code:</b> <code>{$promotion->code}</code>\n";
        }

        // Validity
        $message .= "⏰ <b>Valid Until:</b> {$promotion->valid_until->format('M d, Y H:i')}\n\n";

        // Type-specific info
        $message .= match ($promotion->type) {
            'last_minute_deal' => "🔥 <b>Last-Minute Deal!</b> Book within 24-48 hours.\n",
            'seasonal_discount' => "🎄 <b>Seasonal Special!</b> Limited time offer.\n",
            'loyalty_offer' => "💎 <b>Loyalty Reward!</b> Exclusive for our members.\n",
            default => "",
        };

        $message .= "\n<b>Don't miss out!</b> Book your stay now.";

        return $message;
    }

    /**
     * Get promotion buttons
     */
    private function getPromotionButtons(Promotion $promotion): array
    {
        return [
            [
                ['text' => '🔍 View Details', 'callback_data' => "promo_details_{$promotion->id}"],
                ['text' => '📅 Book Now', 'callback_data' => 'menu_search'],
            ],
            [
                ['text' => '❌ Unsubscribe', 'callback_data' => 'promo_unsubscribe'],
            ],
        ];
    }

    /**
     * Show promotion details
     */
    public function showPromotionDetails(int $promotionId): array
    {
        try {
            $promotion = Promotion::findOrFail($promotionId);

            $message = "📢 <b>Promotion Details</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $message .= "<b>{$promotion->title}</b>\n";
            $message .= "{$promotion->description}\n\n";

            $message .= "💰 <b>Offer:</b>\n";
            if ($promotion->discount_percentage) {
                $message .= "   {$promotion->discount_percentage}% discount\n";
            } elseif ($promotion->discount_amount) {
                $message .= "   {$promotion->discount_amount} ETB discount\n";
            }

            if ($promotion->code) {
                $message .= "   Code: <code>{$promotion->code}</code>\n";
            }

            $message .= "\n📅 <b>Valid Period:</b>\n";
            $message .= "   From: {$promotion->valid_from->format('M d, Y H:i')}\n";
            $message .= "   To: {$promotion->valid_until->format('M d, Y H:i')}\n\n";

            $message .= "📝 <b>Terms:</b>\n";
            $message .= "   • Valid for new bookings only\n";
            $message .= "   • Cannot be combined with other offers\n";
            $message .= "   • Subject to availability\n";

            return [
                'success' => true,
                'message' => $message,
                'buttons' => [[
                    ['text' => '📅 Book Now', 'callback_data' => 'menu_search'],
                    ['text' => '🔙 Back', 'callback_data' => 'promo_menu'],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error showing promotion details', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Promotion not found.',
            ];
        }
    }

    /**
     * Track promotion click
     */
    public function trackClick(int $broadcastId): void
    {
        try {
            $broadcast = PromotionBroadcast::findOrFail($broadcastId);
            $broadcast->update([
                'clicked_at' => now(),
                'status' => 'clicked',
            ]);

            Log::info('Promotion click tracked', ['broadcast_id' => $broadcastId]);
        } catch (\Exception $e) {
            Log::error('Error tracking promotion click', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Track promotion conversion
     */
    public function trackConversion(int $broadcastId): void
    {
        try {
            $broadcast = PromotionBroadcast::findOrFail($broadcastId);
            $broadcast->update([
                'booked_at' => now(),
                'status' => 'converted',
            ]);

            Log::info('Promotion conversion tracked', ['broadcast_id' => $broadcastId]);
        } catch (\Exception $e) {
            Log::error('Error tracking promotion conversion', ['error' => $e->getMessage()]);
        }
    }
}
