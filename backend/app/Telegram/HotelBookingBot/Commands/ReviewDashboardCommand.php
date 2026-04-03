<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Review;
use App\Models\ReviewCollectionSession;
use Illuminate\Support\Facades\Log;

class ReviewDashboardCommand
{
    private int $chatId;
    private int $userId;

    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Show review dashboard
     */
    public function showDashboard(): array
    {
        try {
            $stats = $this->getReviewStats();

            $message = "⭐ <b>Review Dashboard</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📊 <b>Overall Statistics</b>\n";
            $message .= "Total Reviews: {$stats['total_reviews']}\n";
            $message .= "Average Rating: {$stats['average_rating']}/5.0\n";
            $message .= "Public Reviews: {$stats['public_reviews']}\n";
            $message .= "Response Rate: {$stats['response_rate']}%\n\n";

            $message .= "⭐ <b>Rating Distribution</b>\n";
            $message .= "5⭐ Excellent: {$stats['rating_5']} ({$stats['rating_5_percent']}%)\n";
            $message .= "4⭐ Very Good: {$stats['rating_4']} ({$stats['rating_4_percent']}%)\n";
            $message .= "3⭐ Good: {$stats['rating_3']} ({$stats['rating_3_percent']}%)\n";
            $message .= "2⭐ Fair: {$stats['rating_2']} ({$stats['rating_2_percent']}%)\n";
            $message .= "1⭐ Poor: {$stats['rating_1']} ({$stats['rating_1_percent']}%)\n\n";

            $message .= "📝 <b>Recent Reviews</b>\n";
            $message .= "Pending Responses: {$stats['pending_responses']}\n";
            $message .= "Last Review: {$stats['last_review_date']}\n";

            $buttons = [
                [
                    ['text' => '📋 View All Reviews', 'callback_data' => 'review_view_all'],
                    ['text' => '💬 Reply to Reviews', 'callback_data' => 'review_reply'],
                ],
                [
                    ['text' => '🔙 Back', 'callback_data' => 'menu_main'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing review dashboard', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading dashboard.',
            ];
        }
    }

    /**
     * Get review statistics
     */
    private function getReviewStats(): array
    {
        $reviews = Review::where('is_public', true)->get();
        $sessions = ReviewCollectionSession::where('status', 'completed')->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        $rating1 = $reviews->where('rating', 1)->count();
        $rating2 = $reviews->where('rating', 2)->count();
        $rating3 = $reviews->where('rating', 3)->count();
        $rating4 = $reviews->where('rating', 4)->count();
        $rating5 = $reviews->where('rating', 5)->count();

        $rating1Percent = $totalReviews > 0 ? round(($rating1 / $totalReviews) * 100) : 0;
        $rating2Percent = $totalReviews > 0 ? round(($rating2 / $totalReviews) * 100) : 0;
        $rating3Percent = $totalReviews > 0 ? round(($rating3 / $totalReviews) * 100) : 0;
        $rating4Percent = $totalReviews > 0 ? round(($rating4 / $totalReviews) * 100) : 0;
        $rating5Percent = $totalReviews > 0 ? round(($rating5 / $totalReviews) * 100) : 0;

        $publicReviews = $reviews->where('is_public', true)->count();
        $totalSessions = $sessions->count();
        $responseRate = $totalSessions > 0 ? round(($totalReviews / $totalSessions) * 100) : 0;

        $pendingResponses = ReviewCollectionSession::where('status', 'completed')
            ->whereNull('admin_reply')
            ->count();

        $lastReview = $reviews->sortByDesc('created_at')->first();
        $lastReviewDate = $lastReview ? $lastReview->created_at->format('M d, Y') : 'N/A';

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'public_reviews' => $publicReviews,
            'response_rate' => $responseRate,
            'rating_1' => $rating1,
            'rating_2' => $rating2,
            'rating_3' => $rating3,
            'rating_4' => $rating4,
            'rating_5' => $rating5,
            'rating_1_percent' => $rating1Percent,
            'rating_2_percent' => $rating2Percent,
            'rating_3_percent' => $rating3Percent,
            'rating_4_percent' => $rating4Percent,
            'rating_5_percent' => $rating5Percent,
            'pending_responses' => $pendingResponses,
            'last_review_date' => $lastReviewDate,
        ];
    }

    /**
     * Show recent reviews for admin response
     */
    public function showReviewsForResponse(): array
    {
        try {
            $reviews = Review::where('is_public', true)
                ->latest()
                ->limit(5)
                ->get();

            if ($reviews->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "📭 <b>No reviews to respond to.</b>\n\nCheck back later!",
                    'buttons' => [[
                        ['text' => '🔙 Back', 'callback_data' => 'review_dashboard'],
                    ]],
                ];
            }

            $message = "💬 <b>Recent Reviews</b>\n\n";
            $buttons = [];

            foreach ($reviews as $review) {
                $rating = str_repeat('⭐', $review->rating);
                $message .= "{$rating} <b>{$review->user->name}</b>\n";
                $message .= "   {$review->comment}\n\n";

                $buttons[] = [
                    ['text' => "Reply to {$review->user->name}", 'callback_data' => "review_reply_{$review->id}"],
                ];
            }

            $buttons[] = [
                ['text' => '🔙 Back', 'callback_data' => 'review_dashboard'],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing reviews for response', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading reviews.',
            ];
        }
    }
}
