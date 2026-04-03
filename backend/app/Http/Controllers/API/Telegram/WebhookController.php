<?php

namespace App\Http\Controllers\API\Telegram;

use App\Telegram\HotelBookingBot\BotFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming Telegram webhook updates for user bot
     */
    public function handle(Request $request): Response
    {
        try {
            $update = $request->all();

            Log::info('Telegram User Bot webhook received', [
                'update_id' => $update['update_id'] ?? null,
                'message_type' => $this->getUpdateType($update),
            ]);

            // Verify webhook token (optional but recommended)
            if (!$this->verifyWebhook($request)) {
                Log::warning('Invalid webhook request');
                return response()->json(['ok' => false], 403);
            }

            // Create and handle with bot
            $bot = BotFactory::create('default');
            $bot->handle($update);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify webhook authenticity
     */
    private function verifyWebhook(Request $request): bool
    {
        // Optional: Add additional verification logic here
        // For now, we trust Telegram's HTTPS connection
        return true;
    }

    /**
     * Get update type for logging
     */
    private function getUpdateType(array $update): string
    {
        if (isset($update['message'])) {
            return 'message';
        } elseif (isset($update['callback_query'])) {
            return 'callback_query';
        } elseif (isset($update['edited_message'])) {
            return 'edited_message';
        } elseif (isset($update['channel_post'])) {
            return 'channel_post';
        }
        return 'unknown';
    }
}

