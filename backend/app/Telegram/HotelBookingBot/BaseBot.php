<?php

namespace App\Telegram\HotelBookingBot;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

abstract class BaseBot
{
    /**
     * Bot name
     */
    protected string $botName = 'default';

    /**
     * Chat type (private, group, supergroup, channel)
     */
    protected string $chatType = 'private';

    /**
     * Current update
     */
    protected array $update = [];

    /**
     * Current message
     */
    protected array $message = [];

    /**
     * Current chat ID
     */
    protected int $chatId = 0;

    /**
     * Current user ID
     */
    protected int $userId = 0;

    /**
     * Initialize bot with update
     */
    public function handle(array $update): void
    {
        $this->update = $update;
        $this->extractUpdateData();
        $this->processUpdate();
    }

    /**
     * Extract data from update
     */
    protected function extractUpdateData(): void
    {
        if (isset($this->update['message'])) {
            $this->message = $this->update['message'];
            $this->chatId = $this->message['chat']['id'];
            $this->chatType = $this->message['chat']['type'];
            $this->userId = $this->message['from']['id'];
        } elseif (isset($this->update['callback_query'])) {
            $this->chatId = $this->update['callback_query']['message']['chat']['id'];
            $this->chatType = $this->update['callback_query']['message']['chat']['type'];
            $this->userId = $this->update['callback_query']['from']['id'];
        }
    }

    /**
     * Process the update
     */
    protected function processUpdate(): void
    {
        // Route based on chat type
        match ($this->chatType) {
            'private' => $this->handlePrivateChat(),
            'group', 'supergroup' => $this->handleGroupChat(),
            'channel' => $this->handleChannelChat(),
            default => $this->handleUnknownChat(),
        };
    }

    /**
     * Handle private chat
     */
    protected function handlePrivateChat(): void
    {
        if (isset($this->message['text'])) {
            $text = $this->message['text'];

            if (str_starts_with($text, '/')) {
                $this->handleCommand($text);
            } else {
                $this->handleMessage($text);
            }
        } elseif (isset($this->update['callback_query'])) {
            $this->handleCallbackQuery($this->update['callback_query']);
        }
    }

    /**
     * Handle group chat
     */
    protected function handleGroupChat(): void
    {
        if (isset($this->message['text'])) {
            $text = $this->message['text'];

            // Only respond to commands in groups
            if (str_starts_with($text, '/')) {
                $this->handleGroupCommand($text);
            }
        }
    }

    /**
     * Handle channel chat
     */
    protected function handleChannelChat(): void
    {
        // Channel handling logic
    }

    /**
     * Handle unknown chat type
     */
    protected function handleUnknownChat(): void
    {
        // Unknown chat type handling
    }

    /**
     * Handle command (to be implemented by subclasses)
     */
    abstract protected function handleCommand(string $command): void;

    /**
     * Handle message (to be implemented by subclasses)
     */
    abstract protected function handleMessage(string $text): void;

    /**
     * Handle callback query (to be implemented by subclasses)
     */
    abstract protected function handleCallbackQuery(array $callbackQuery): void;

    /**
     * Handle group command (to be implemented by subclasses)
     */
    abstract protected function handleGroupCommand(string $command): void;

    /**
     * Send message
     */
    protected function sendMessage(string $text, array $options = []): void
    {
        $telegram = Telegram::bot($this->botName);
        
        $params = array_merge([
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);

        $telegram->sendMessage($params);
    }

    /**
     * Send message with keyboard
     */
    protected function sendMessageWithKeyboard(string $text, array $buttons): void
    {
        $this->sendMessage($text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    /**
     * Send photo
     */
    protected function sendPhoto(string $photoUrl, string $caption = ''): void
    {
        $telegram = Telegram::bot($this->botName);
        
        $telegram->sendPhoto([
            'chat_id' => $this->chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * Send media group (multiple photos)
     */
    protected function sendMediaGroup(array $mediaGroup): void
    {
        $telegram = Telegram::bot($this->botName);
        
        $telegram->sendMediaGroup([
            'chat_id' => $this->chatId,
            'media' => json_encode($mediaGroup),
        ]);
    }

    /**
     * Answer callback query
     */
    protected function answerCallbackQuery(string $callbackId, string $text = '', bool $showAlert = false): void
    {
        $telegram = Telegram::bot($this->botName);
        
        $telegram->answerCallbackQuery([
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    /**
     * Get chat ID
     */
    public function getChatId(): int
    {
        return $this->chatId;
    }

    /**
     * Get user ID
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Get chat type
     */
    public function getChatType(): string
    {
        return $this->chatType;
    }

    /**
     * Get message
     */
    public function getMessage(): array
    {
        return $this->message;
    }

    /**
     * Get update
     */
    public function getUpdate(): array
    {
        return $this->update;
    }
}
