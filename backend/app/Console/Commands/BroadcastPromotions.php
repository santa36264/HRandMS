<?php

namespace App\Console\Commands;

use App\Models\Promotion;
use App\Models\PromotionAnalytic;
use App\Models\PromotionBroadcast;
use App\Models\User;
use App\Telegram\HotelBookingBot\Commands\PromotionCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BroadcastPromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:broadcast {--promotion-id=} {--type=}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Broadcast promotions to opted-in users via Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $promotionId = $this->option('promotion-id');
            $type = $this->option('type');

            if ($promotionId) {
                $this->broadcastPromotion((int)$promotionId);
            } elseif ($type) {
                $this->broadcastByType($type);
            } else {
                $this->broadcastActive();
            }

            $this->info('Promotions broadcast completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error broadcasting promotions', ['error' => $e->getMessage()]);
            $this->error('Error broadcasting promotions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Broadcast specific promotion
     */
    private function broadcastPromotion(int $promotionId): void
    {
        $promotion = Promotion::findOrFail($promotionId);

        if (!$promotion->is_active) {
            $this->warn("Promotion {$promotionId} is not active.");
            return;
        }

        $this->broadcastToUsers($promotion);
    }

    /**
     * Broadcast by type
     */
    private function broadcastByType(string $type): void
    {
        $promotions = Promotion::where('type', $type)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->get();

        $this->info("Broadcasting {$promotions->count()} promotions of type: {$type}");

        foreach ($promotions as $promotion) {
            $this->broadcastToUsers($promotion);
        }
    }

    /**
     * Broadcast active promotions
     */
    private function broadcastActive(): void
    {
        $promotions = Promotion::where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->get();

        $this->info("Broadcasting {$promotions->count()} active promotions");

        foreach ($promotions as $promotion) {
            $this->broadcastToUsers($promotion);
        }
    }

    /**
     * Broadcast to users
     */
    private function broadcastToUsers(Promotion $promotion): void
    {
        // Get opted-in users
        $users = User::whereHas('promotionSubscriptions', function ($q) use ($promotion) {
            $q->where('promotion_id', $promotion->id)
                ->where('opted_in', true);
        })
            ->where('telegram_user_id', '!=', null)
            ->get();

        $this->info("Sending promotion {$promotion->id} to {$users->count()} users...");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                // Determine variant for A/B testing
                $variant = $promotion->a_b_test_variant ?? ($user->id % 2 === 0 ? 'A' : 'B');

                $command = new PromotionCommand($user->telegram_user_id, $user->id);
                $result = $command->sendPromotion($promotion, $variant);

                if ($result['success']) {
                    $sentCount++;
                    $this->line("✓ Sent to user {$user->id}");
                } else {
                    $failedCount++;
                    $this->warn("✗ Failed to send to user {$user->id}");
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Error sending promotion to user', [
                    'promotion_id' => $promotion->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("✗ Error sending to user {$user->id}");
            }
        }

        // Update analytics
        $this->updateAnalytics($promotion, $sentCount);

        $this->info("Promotion {$promotion->id}: {$sentCount} sent, {$failedCount} failed");
    }

    /**
     * Update analytics
     */
    private function updateAnalytics(Promotion $promotion, int $sentCount): void
    {
        try {
            // Update overall analytics
            $overall = PromotionAnalytic::firstOrCreate(
                ['promotion_id' => $promotion->id, 'variant' => 'overall'],
                ['total_sent' => 0, 'total_clicked' => 0, 'total_converted' => 0]
            );

            $overall->increment('total_sent', $sentCount);

            // Calculate rates
            $clicked = PromotionBroadcast::where('promotion_id', $promotion->id)
                ->whereNotNull('clicked_at')
                ->count();

            $converted = PromotionBroadcast::where('promotion_id', $promotion->id)
                ->whereNotNull('booked_at')
                ->count();

            $total = PromotionBroadcast::where('promotion_id', $promotion->id)->count();

            $clickRate = $total > 0 ? ($clicked / $total) * 100 : 0;
            $conversionRate = $total > 0 ? ($converted / $total) * 100 : 0;

            $overall->update([
                'total_clicked' => $clicked,
                'total_converted' => $converted,
                'click_rate' => $clickRate,
                'conversion_rate' => $conversionRate,
            ]);

            Log::info('Analytics updated', [
                'promotion_id' => $promotion->id,
                'sent' => $sentCount,
                'click_rate' => $clickRate,
                'conversion_rate' => $conversionRate,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating analytics', ['error' => $e->getMessage()]);
        }
    }
}
