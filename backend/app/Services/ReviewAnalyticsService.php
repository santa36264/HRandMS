<?php

namespace App\Services;

use App\Models\NegativeReviewAlert;
use App\Models\Review;
use App\Models\ReviewAnalytics;
use App\Models\ReviewCollectionSession;
use App\Models\RoomTypeRating;
use Illuminate\Support\Facades\Log;

class ReviewAnalyticsService
{
    /**
     * Generate daily analytics
     */
    public function generateDailyAnalytics(string $date = null): void
    {
        try {
            $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();

            $reviews = Review::whereDate('created_at', $date)->get();

            if ($reviews->isEmpty()) {
                return;
            }

            $totalReviews = $reviews->count();
            $averageRating = round($reviews->avg('rating'), 2);

            $rating1 = $reviews->where('rating', 1)->count();
            $rating2 = $reviews->where('rating', 2)->count();
            $rating3 = $reviews->where('rating', 3)->count();
            $rating4 = $reviews->where('rating', 4)->count();
            $rating5 = $reviews->where('rating', 5)->count();

            $completionRate = $this->calculateCompletionRate($date);
            $responseTime = $this->calculateAverageResponseTime($date);
            $keywords = $this->extractKeywords($reviews);

            ReviewAnalytics::updateOrCreate(
                ['date' => $date],
                [
                    'average_rating' => $averageRating,
                    'total_reviews' => $totalReviews,
                    'rating_1' => $rating1,
                    'rating_2' => $rating2,
                    'rating_3' => $rating3,
                    'rating_4' => $rating4,
                    'rating_5' => $rating5,
                    'completion_rate' => $completionRate,
                    'response_time_hours' => $responseTime,
                    'keywords' => $keywords,
                ]
            );

            Log::info('Daily analytics generated', [
                'date' => $date,
                'average_rating' => $averageRating,
                'total_reviews' => $totalReviews,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating daily analytics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update room type ratings
     */
    public function updateRoomTypeRatings(): void
    {
        try {
            $reviews = Review::with('booking.room')->get();

            $roomRatings = $reviews->groupBy('booking.room_id');

            foreach ($roomRatings as $roomId => $roomReviews) {
                $totalReviews = $roomReviews->count();
                $averageRating = round($roomReviews->avg('rating'), 2);

                $rating1 = $roomReviews->where('rating', 1)->count();
                $rating2 = $roomReviews->where('rating', 2)->count();
                $rating3 = $roomReviews->where('rating', 3)->count();
                $rating4 = $roomReviews->where('rating', 4)->count();
                $rating5 = $roomReviews->where('rating', 5)->count();

                RoomTypeRating::updateOrCreate(
                    ['room_id' => $roomId],
                    [
                        'average_rating' => $averageRating,
                        'total_reviews' => $totalReviews,
                        'rating_1' => $rating1,
                        'rating_2' => $rating2,
                        'rating_3' => $rating3,
                        'rating_4' => $rating4,
                        'rating_5' => $rating5,
                    ]
                );
            }

            Log::info('Room type ratings updated');
        } catch (\Exception $e) {
            Log::error('Error updating room type ratings', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check for negative reviews and create alerts
     */
    public function checkNegativeReviews(): void
    {
        try {
            $negativeReviews = Review::where('rating', '<', 3)
                ->whereDoesntHave('negativeAlert')
                ->get();

            foreach ($negativeReviews as $review) {
                NegativeReviewAlert::create([
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'status' => 'new',
                ]);

                Log::warning('Negative review alert created', [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error checking negative reviews', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate(string $date): int
    {
        try {
            $totalSessions = ReviewCollectionSession::whereDate('created_at', $date)->count();
            $completedSessions = ReviewCollectionSession::where('status', 'completed')
                ->whereDate('completed_at', $date)
                ->count();

            return $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;
        } catch (\Exception $e) {
            Log::error('Error calculating completion rate', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Calculate average response time
     */
    private function calculateAverageResponseTime(string $date): ?int
    {
        try {
            $sessions = ReviewCollectionSession::where('status', 'completed')
                ->whereDate('completed_at', $date)
                ->get();

            if ($sessions->isEmpty()) {
                return null;
            }

            $totalHours = 0;
            foreach ($sessions as $session) {
                if ($session->rating_given_at && $session->completed_at) {
                    $hours = $session->completed_at->diffInHours($session->rating_given_at);
                    $totalHours += $hours;
                }
            }

            return round($totalHours / $sessions->count());
        } catch (\Exception $e) {
            Log::error('Error calculating response time', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract keywords from reviews
     */
    private function extractKeywords(object $reviews): array
    {
        try {
            $keywords = [];
            $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'was', 'are', 'were', 'be', 'been', 'being'];

            foreach ($reviews as $review) {
                $words = str_word_count(strtolower($review->comment), 1);
                foreach ($words as $word) {
                    if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                        $keywords[$word] = ($keywords[$word] ?? 0) + 1;
                    }
                }
            }

            arsort($keywords);
            return array_slice(array_keys($keywords), 0, 10);
        } catch (\Exception $e) {
            Log::error('Error extracting keywords', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get analytics dashboard data
     */
    public function getDashboardData(int $days = 30): array
    {
        try {
            $startDate = now()->subDays($days)->toDateString();
            $endDate = now()->toDateString();

            $analytics = ReviewAnalytics::whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->get();

            $currentAnalytics = ReviewAnalytics::where('date', $endDate)->first();
            $previousAnalytics = ReviewAnalytics::where('date', now()->subDay()->toDateString())->first();

            $averageRating = $currentAnalytics?->average_rating ?? 0;
            $ratingTrend = $this->calculateTrend($currentAnalytics?->average_rating, $previousAnalytics?->average_rating);

            $negativeAlerts = NegativeReviewAlert::where('status', 'new')->count();

            $roomRatings = RoomTypeRating::with('room')
                ->orderByDesc('average_rating')
                ->get();

            return [
                'average_rating' => $averageRating,
                'rating_trend' => $ratingTrend,
                'total_reviews' => $currentAnalytics?->total_reviews ?? 0,
                'completion_rate' => $currentAnalytics?->completion_rate ?? 0,
                'response_time' => $currentAnalytics?->response_time_hours ?? 0,
                'rating_distribution' => [
                    'rating_5' => $currentAnalytics?->rating_5 ?? 0,
                    'rating_4' => $currentAnalytics?->rating_4 ?? 0,
                    'rating_3' => $currentAnalytics?->rating_3 ?? 0,
                    'rating_2' => $currentAnalytics?->rating_2 ?? 0,
                    'rating_1' => $currentAnalytics?->rating_1 ?? 0,
                ],
                'keywords' => $currentAnalytics?->keywords ?? [],
                'negative_alerts' => $negativeAlerts,
                'room_ratings' => $roomRatings->map(fn($r) => [
                    'room_name' => $r->room->name,
                    'average_rating' => $r->average_rating,
                    'total_reviews' => $r->total_reviews,
                ]),
                'analytics_trend' => $analytics->map(fn($a) => [
                    'date' => $a->date->format('M d'),
                    'rating' => $a->average_rating,
                    'reviews' => $a->total_reviews,
                ]),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting dashboard data', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate trend percentage
     */
    private function calculateTrend(?float $current, ?float $previous): float
    {
        if (!$current || !$previous) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Acknowledge negative review alert
     */
    public function acknowledgeAlert(int $alertId, string $notes = ''): bool
    {
        try {
            $alert = NegativeReviewAlert::find($alertId);

            if (!$alert) {
                return false;
            }

            $alert->update([
                'status' => 'acknowledged',
                'admin_notes' => $notes,
                'acknowledged_at' => now(),
            ]);

            Log::info('Negative review alert acknowledged', ['alert_id' => $alertId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error acknowledging alert', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resolve negative review alert
     */
    public function resolveAlert(int $alertId, string $resolution = ''): bool
    {
        try {
            $alert = NegativeReviewAlert::find($alertId);

            if (!$alert) {
                return false;
            }

            $alert->update([
                'status' => 'resolved',
                'admin_notes' => $resolution,
                'resolved_at' => now(),
            ]);

            Log::info('Negative review alert resolved', ['alert_id' => $alertId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error resolving alert', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
