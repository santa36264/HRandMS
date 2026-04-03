<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReviewCollectionCallbackHandler
{
    private int $chatId;
    private int $userId;
    private string $callbackData;
    private string $callbackId;

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
     * Handle review callback
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

            $action = $parts[2];

            $response = match ($action) {
                'rating' => $this->handleRating($parts),
                'question' => $this->handleQuestion($parts),
                'submit' => $this->handleSubmit(),
                'permission' => $this->handlePermission($parts),
                'more' => $this->handleMoreQuestions(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling review callback', [
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
     * Handle rating selection
     */
    private function handleRating(array $parts): array
    {
        if (count($parts) < 4) {
            return ['message' => '❌ Invalid rating'];
        }

        $rating = (int)$parts[3];

        if ($rating < 1 || $rating > 5) {
            return ['message' => '❌ Invalid rating value'];
        }

        $command = new ReviewCollectionCommand($this->chatId, $this->userId);
        return $command->handleRatingSelection($rating);
    }

    /**
     * Handle follow-up question
     */
    private function handleQuestion(array $parts): array
    {
        if (count($parts) < 4) {
            return ['message' => '❌ Invalid question'];
        }

        $questionType = $parts[3];

        $command = new ReviewCollectionCommand($this->chatId, $this->userId);
        return $command->handleFollowUpQuestion($questionType);
    }

    /**
     * Handle submit
     */
    private function handleSubmit(): array
    {
        $command = new ReviewCollectionCommand($this->chatId, $this->userId);
        return $command->submitReview();
    }

    /**
     * Handle permission response
     */
    private function handlePermission(array $parts): array
    {
        if (count($parts) < 4) {
            return ['message' => '❌ Invalid permission'];
        }

        $permission = $parts[3] === 'yes';

        $command = new ReviewCollectionCommand($this->chatId, $this->userId);
        return $command->handlePermissionResponse($permission);
    }

    /**
     * Handle more questions
     */
    private function handleMoreQuestions(): array
    {
        $message = "📝 <b>More Questions</b>\n\n";
        $message .= "Select a question to answer:\n";

        $buttons = [
            [
                ['text' => '👍 What did you like most?', 'callback_data' => 'review_question_liked'],
            ],
            [
                ['text' => '💡 What could be improved?', 'callback_data' => 'review_question_improve'],
            ],
            [
                ['text' => '🤝 Would you recommend us?', 'callback_data' => 'review_question_recommend'],
            ],
            [
                ['text' => '✅ Submit Review', 'callback_data' => 'review_submit'],
            ],
        ];

        return [
            'success' => true,
            'message' => $message,
            'buttons' => $buttons,
        ];
    }
}
