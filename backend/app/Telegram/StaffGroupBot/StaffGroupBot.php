<?php

namespace App\Telegram\StaffGroupBot;

use App\Models\StaffNotification;
use Illuminate\Support\Facades\Log;

class StaffGroupBot
{
    private int $groupId;
    private int $userId;
    private string $userName;
    private array $message;

    public function __construct(array $update)
    {
        $this->message = $update['message'] ?? [];
        $this->groupId = $this->message['chat']['id'] ?? 0;
        $this->userId = $this->message['from']['id'] ?? 0;
        $this->userName = $this->message['from']['username'] ?? 'Unknown';
    }

    /**
     * Handle incoming message
     */
    public function handle(): void
    {
        try {
            $text = $this->message['text'] ?? '';
            $replyTo = $this->message['reply_to_message'] ?? null;

            if (!$text) {
                return;
            }

            // Check if this is a reply to a notification
            if ($replyTo && isset($replyTo['text'])) {
                $this->handleTaskAssignment($text, $replyTo['text']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling staff group message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle task assignment from staff reply
     */
    private function handleTaskAssignment(string $response, string $originalMessage): void
    {
        try {
            // Extract notification ID from original message if available
            $notificationId = $this->extractNotificationId($originalMessage);

            if ($notificationId) {
                $notification = StaffNotification::find($notificationId);
                if ($notification) {
                    $notification->update([
                        'status' => 'acknowledged',
                        'data' => array_merge($notification->data ?? [], [
                            'assigned_to' => $this->userName,
                            'assigned_at' => now(),
                            'staff_response' => $response,
                        ]),
                    ]);

                    Log::info('Staff Task Assignment', [
                        'notification_id' => $notificationId,
                        'assigned_to' => $this->userName,
                        'response' => $response,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling task assignment', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Extract notification ID from message
     */
    private function extractNotificationId(string $message): ?int
    {
        // Look for pattern like "Notification ID: 123"
        if (preg_match('/Notification ID: (\d+)/', $message, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
