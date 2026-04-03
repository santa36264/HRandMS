<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramService
{
    private string $bot;

    public function __construct(string $bot = 'default')
    {
        $this->bot = $bot;
    }

    /**
     * Get the Telegram instance for the current bot
     */
    private function getTelegram()
    {
        return Telegram::bot($this->bot);
    }

    /**
     * Send a text message
     */
    public function sendMessage(int $chatId, string $text, array $options = []): bool
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $this->getTelegram()->sendMessage($params);
            return true;
        } catch (\Exception $e) {
            Log::error("Error sending {$this->bot} bot message", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Send a message with inline keyboard
     */
    public function sendMessageWithKeyboard(int $chatId, string $text, array $buttons): bool
    {
        try {
            $this->getTelegram()->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $buttons,
                ]),
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error sending {$this->bot} bot message with keyboard", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Edit a message
     */
    public function editMessage(int $chatId, int $messageId, string $text, array $options = []): bool
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options);

            $this->getTelegram()->editMessageText($params);
            return true;
        } catch (\Exception $e) {
            Log::error("Error editing {$this->bot} bot message", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
            return false;
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $chatId, int $messageId): bool
    {
        try {
            $this->getTelegram()->deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error deleting {$this->bot} bot message", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
            return false;
        }
    }

    /**
     * Send a photo
     */
    public function sendPhoto(int $chatId, string $photoUrl, string $caption = '', array $options = []): bool
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ], $options);

            $this->getTelegram()->sendPhoto($params);
            return true;
        } catch (\Exception $e) {
            Log::error("Error sending {$this->bot} bot photo", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Send a document
     */
    public function sendDocument(int $chatId, string $documentUrl, string $caption = ''): bool
    {
        try {
            $this->getTelegram()->sendDocument([
                'chat_id' => $chatId,
                'document' => $documentUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error sending {$this->bot} bot document", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Answer callback query
     */
    public function answerCallbackQuery(string $callbackId, string $text = '', bool $showAlert = false): bool
    {
        try {
            $this->getTelegram()->answerCallbackQuery([
                'callback_query_id' => $callbackId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error answering {$this->bot} bot callback query", [
                'error' => $e->getMessage(),
                'callback_id' => $callbackId,
            ]);
            return false;
        }
    }

    /**
     * Get bot info
     */
    public function getMe(): ?array
    {
        try {
            $response = $this->getTelegram()->getMe();
            return $response->toArray();
        } catch (\Exception $e) {
            Log::error("Error getting {$this->bot} bot info", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): ?array
    {
        try {
            $response = $this->getTelegram()->getWebhookInfo();
            return $response->toArray();
        } catch (\Exception $e) {
            Log::error("Error getting {$this->bot} bot webhook info", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Set webhook
     */
    public function setWebhook(string $url): bool
    {
        try {
            $this->getTelegram()->setWebhook([
                'url' => $url,
                'allowed_updates' => ['message', 'callback_query'],
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error setting {$this->bot} bot webhook", [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return false;
        }
    }

    /**
     * Remove webhook
     */
    public function removeWebhook(): bool
    {
        try {
            $this->getTelegram()->setWebhook(['url' => '']);
            return true;
        } catch (\Exception $e) {
            Log::error("Error removing {$this->bot} bot webhook", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
