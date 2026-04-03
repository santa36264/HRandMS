<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewCollectionSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReviewCollectionCommand
{
    private int $chatId;
    private int $userId;
    private string $hotelName;

    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->hotelName = config('telegram.hotel_name', 'SATAAB Hotel');
    }

    /**
     * Start review collection for checked-out guest
     */
    public function startReviewCollection(Booking $booking): array
    {
        try {
            // Check if session already exists
            $existingSession = ReviewCollectionSession::where('booking_id', $booking->id)
                ->where('status', '!=', 'completed')
                ->first();

            if ($existingSession) {
                return [
                    'success' => false,
                    'message' => '⚠️ Review collection already in progress for this booking.',
                ];
            }

            // Create review collection session
            $session = ReviewCollectionSession::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'status' => 'pending',
            ]);

            // Store session ID in cache for tracking
            Cache::put("review_session_{$this->userId}", $session->id, now()->addHours(24));

            $message = "🌟 <b>How was your stay at {$this->hotelName}?</b>\n\n";
            $message .= "We'd love to hear about your experience!\n\n";
            $message .= "Please rate your stay:\n";

            $buttons = [
                [
                    ['text' => '1⭐ Poor', 'callback_data' => 'review_rating_1'],
                    ['text' => '2⭐ Fair', 'callback_data' => 'review_rating_2'],
                ],
                [
                    ['text' => '3⭐ Good', 'callback_data' => 'review_rating_3'],
                    ['text' => '4⭐ Very Good', 'callback_data' => 'review_rating_4'],
                ],
                [
                    ['text' => '5⭐ Excellent', 'callback_data' => 'review_rating_5'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
                'session_id' => $session->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error starting review collection', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error starting review collection.',
            ];
        }
    }

    /**
     * Handle rating selection
     */
    public function handleRatingSelection(int $rating): array
    {
        try {
            $sessionId = Cache::get("review_session_{$this->userId}");

            if (!$sessionId) {
                return [
                    'success' => false,
                    'message' => '❌ Review session not found. Please try again.',
                ];
            }

            $session = ReviewCollectionSession::find($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => '❌ Review session expired.',
                ];
            }

            $session->update([
                'rating' => $rating,
                'status' => 'rating_given',
                'rating_given_at' => now(),
            ]);

            $ratingEmoji = match ($rating) {
                1 => '1⭐',
                2 => '2⭐',
                3 => '3⭐',
                4 => '4⭐',
                5 => '5⭐',
                default => '⭐',
            };

            $message = "✅ <b>Thank you for rating us {$ratingEmoji}</b>\n\n";
            $message .= "We'd love to hear more about your experience.\n\n";
            $message .= "Please answer the following questions:\n";

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
                    ['text' => '✅ Skip & Submit', 'callback_data' => 'review_submit'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
                'session_id' => $sessionId,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling rating selection', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing rating.',
            ];
        }
    }

    /**
     * Handle follow-up question
     */
    public function handleFollowUpQuestion(string $questionType): array
    {
        try {
            $sessionId = Cache::get("review_session_{$this->userId}");

            if (!$sessionId) {
                return [
                    'success' => false,
                    'message' => '❌ Review session not found.',
                ];
            }

            $questionText = match ($questionType) {
                'liked' => '👍 What did you like most about your stay?',
                'improve' => '💡 What could we improve?',
                'recommend' => '🤝 Would you recommend us to friends? (Yes/No)',
                default => 'Please share your feedback:',
            };

            $message = "<b>{$questionText}</b>\n\n";
            $message .= "Please type your response below:\n";

            // Store question type in cache
            Cache::put("review_question_{$this->userId}", $questionType, now()->addHours(1));

            return [
                'success' => true,
                'message' => $message,
                'question_type' => $questionType,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling follow-up question', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing question.',
            ];
        }
    }

    /**
     * Handle follow-up response
     */
    public function handleFollowUpResponse(string $response): array
    {
        try {
            $sessionId = Cache::get("review_session_{$this->userId}");
            $questionType = Cache::get("review_question_{$this->userId}");

            if (!$sessionId || !$questionType) {
                return [
                    'success' => false,
                    'message' => '❌ Review session not found.',
                ];
            }

            $session = ReviewCollectionSession::find($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => '❌ Review session expired.',
                ];
            }

            // Update session with response
            if ($questionType === 'liked') {
                $session->update(['what_liked' => $response]);
            } elseif ($questionType === 'improve') {
                $session->update(['improvement_suggestions' => $response]);
            } elseif ($questionType === 'recommend') {
                $recommend = strtolower($response) === 'yes' || strtolower($response) === 'y';
                $session->update(['would_recommend' => $recommend]);
            }

            // Clear question cache
            Cache::forget("review_question_{$this->userId}");

            $message = "✅ <b>Thank you for your feedback!</b>\n\n";
            $message .= "We appreciate your input and will use it to improve our services.\n\n";

            $buttons = [
                [
                    ['text' => '📝 Add More Feedback', 'callback_data' => 'review_more_questions'],
                    ['text' => '✅ Submit Review', 'callback_data' => 'review_submit'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling follow-up response', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing response.',
            ];
        }
    }

    /**
     * Submit review and ask for permission to display
     */
    public function submitReview(): array
    {
        try {
            $sessionId = Cache::get("review_session_{$this->userId}");

            if (!$sessionId) {
                return [
                    'success' => false,
                    'message' => '❌ Review session not found.',
                ];
            }

            $session = ReviewCollectionSession::find($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => '❌ Review session expired.',
                ];
            }

            $message = "🎉 <b>Thank you for your review!</b>\n\n";
            $message .= "Your feedback helps us serve you better.\n\n";
            $message .= "May we display your review publicly to help other guests?\n";

            $buttons = [
                [
                    ['text' => '✅ Yes, display my review', 'callback_data' => 'review_permission_yes'],
                    ['text' => '❌ Keep it private', 'callback_data' => 'review_permission_no'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error submitting review', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error submitting review.',
            ];
        }
    }

    /**
     * Handle permission response and create review
     */
    public function handlePermissionResponse(bool $permission): array
    {
        try {
            $sessionId = Cache::get("review_session_{$this->userId}");

            if (!$sessionId) {
                return [
                    'success' => false,
                    'message' => '❌ Review session not found.',
                ];
            }

            $session = ReviewCollectionSession::find($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => '❌ Review session expired.',
                ];
            }

            $session->update([
                'permission_to_display' => $permission,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create review in Review model
            if ($session->rating) {
                Review::create([
                    'booking_id' => $session->booking_id,
                    'user_id' => $session->user_id,
                    'rating' => $session->rating,
                    'comment' => $this->formatReviewComment($session),
                    'is_public' => $permission,
                    'status' => 'approved',
                ]);
            }

            // Clear cache
            Cache::forget("review_session_{$this->userId}");

            $permissionText = $permission ? '✅ Your review will be displayed publicly.' : '🔒 Your review is kept private.';

            $message = "🙏 <b>Thank you!</b>\n\n";
            $message .= "{$permissionText}\n\n";
            $message .= "We hope to see you again at {$this->hotelName}!\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "Special Offer: Use code WELCOME20 for 20% off your next stay!";

            Log::info('Review Collection Completed', [
                'session_id' => $sessionId,
                'booking_id' => $session->booking_id,
                'rating' => $session->rating,
                'permission_to_display' => $permission,
            ]);

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling permission response', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error completing review.',
            ];
        }
    }

    /**
     * Format review comment from session data
     */
    private function formatReviewComment(ReviewCollectionSession $session): string
    {
        $comment = '';

        if ($session->what_liked) {
            $comment .= "What I liked: {$session->what_liked}\n\n";
        }

        if ($session->improvement_suggestions) {
            $comment .= "Suggestions for improvement: {$session->improvement_suggestions}\n\n";
        }

        if ($session->would_recommend !== null) {
            $recommend = $session->would_recommend ? 'Yes' : 'No';
            $comment .= "Would recommend: {$recommend}";
        }

        return trim($comment) ?: 'No additional comments provided.';
    }
}
