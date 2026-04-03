<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\NegativeReviewAlert;
use App\Services\ReviewAnalyticsService;
use Illuminate\Support\Facades\Log;

class AnalyticsDashboardCommand
{
    private int $chatId;
    private int $userId;
    private ReviewAnalyticsService $analyticsService;

    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->analyticsService = new ReviewAnalyticsService();
    }

    /**
     * Show analytics dashboard
     */
    public function showDashboard(): array
    {
        try {
            $data = $this->analyticsService->getDashboardData(30);

            $trendEmoji = $data['rating_trend'] > 0 ? '📈' : ($data['rating_trend'] < 0 ? '📉' : '➡️');
            $trendText = abs($data['rating_trend']) . '%';

            $message = "📊 <b>Feedback Analytics Dashboard</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $message .= "⭐ <b>Average Rating</b>\n";
            $message .= "   {$data['average_rating']}/5.0 {$trendEmoji} {$trendText}\n\n";

            $message .= "📈 <b>Rating Distribution</b>\n";
            $message .= "   5⭐: {$data['rating_distribution']['rating_5']}\n";
            $message .= "   4⭐: {$data['rating_distribution']['rating_4']}\n";
            $message .= "   3⭐: {$data['rating_distribution']['rating_3']}\n";
            $message .= "   2⭐: {$data['rating_distribution']['rating_2']}\n";
            $message .= "   1⭐: {$data['rating_distribution']['rating_1']}\n\n";

            $message .= "📋 <b>Key Metrics</b>\n";
            $message .= "   Total Reviews: {$data['total_reviews']}\n";
            $message .= "   Completion Rate: {$data['completion_rate']}%\n";
            $message .= "   Avg Response Time: {$data['response_time']} hours\n\n";

            if (!empty($data['keywords'])) {
                $message .= "🔑 <b>Top Keywords</b>\n";
                $keywords = implode(', ', array_slice($data['keywords'], 0, 5));
                $message .= "   {$keywords}\n\n";
            }

            if ($data['negative_alerts'] > 0) {
                $message .= "⚠️ <b>Negative Reviews</b>\n";
                $message .= "   {$data['negative_alerts']} new alerts\n\n";
            }

            $buttons = [
                [
                    ['text' => '📈 Detailed Report', 'callback_data' => 'analytics_detailed'],
                    ['text' => '🏨 Room Ratings', 'callback_data' => 'analytics_rooms'],
                ],
                [
                    ['text' => '⚠️ Negative Reviews', 'callback_data' => 'analytics_negative'],
                    ['text' => '📥 Export Report', 'callback_data' => 'analytics_export'],
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
            Log::error('Error showing analytics dashboard', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading dashboard.',
            ];
        }
    }

    /**
     * Show detailed report
     */
    public function showDetailedReport(): array
    {
        try {
            $data = $this->analyticsService->getDashboardData(30);

            $message = "📈 <b>Detailed Analytics Report</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $message .= "<b>30-Day Trend</b>\n";
            foreach ($data['analytics_trend'] as $trend) {
                $bars = str_repeat('█', (int)($trend['rating'] * 2));
                $message .= "{$trend['date']}: {$bars} {$trend['rating']}\n";
            }

            $message .= "\n<b>Summary Statistics</b>\n";
            $message .= "Average Rating: {$data['average_rating']}/5.0\n";
            $message .= "Total Reviews: {$data['total_reviews']}\n";
            $message .= "Completion Rate: {$data['completion_rate']}%\n";
            $message .= "Response Time: {$data['response_time']} hours\n";

            $buttons = [
                [
                    ['text' => '🔙 Back to Dashboard', 'callback_data' => 'analytics_dashboard'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing detailed report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading report.',
            ];
        }
    }

    /**
     * Show room ratings comparison
     */
    public function showRoomRatings(): array
    {
        try {
            $data = $this->analyticsService->getDashboardData(30);

            $message = "🏨 <b>Room Type Ratings</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            foreach ($data['room_ratings'] as $room) {
                $stars = str_repeat('⭐', (int)$room['average_rating']);
                $message .= "<b>{$room['room_name']}</b>\n";
                $message .= "{$stars} {$room['average_rating']}/5.0\n";
                $message .= "Reviews: {$room['total_reviews']}\n\n";
            }

            $buttons = [
                [
                    ['text' => '🔙 Back to Dashboard', 'callback_data' => 'analytics_dashboard'],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing room ratings', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading room ratings.',
            ];
        }
    }

    /**
     * Show negative reviews
     */
    public function showNegativeReviews(): array
    {
        try {
            $alerts = NegativeReviewAlert::where('status', 'new')
                ->with('review')
                ->latest()
                ->limit(5)
                ->get();

            if ($alerts->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "✅ <b>No negative reviews</b>\n\nAll reviews are positive!",
                    'buttons' => [[
                        ['text' => '🔙 Back to Dashboard', 'callback_data' => 'analytics_dashboard'],
                    ]],
                ];
            }

            $message = "⚠️ <b>Negative Reviews Alert</b>\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $buttons = [];

            foreach ($alerts as $alert) {
                $message .= "{$alert->rating}⭐ <b>{$alert->review->user->name}</b>\n";
                $message .= "{$alert->comment}\n\n";

                $buttons[] = [
                    ['text' => "Acknowledge #{$alert->id}", 'callback_data' => "analytics_ack_{$alert->id}"],
                ];
            }

            $buttons[] = [
                ['text' => '🔙 Back to Dashboard', 'callback_data' => 'analytics_dashboard'],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing negative reviews', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading negative reviews.',
            ];
        }
    }

    /**
     * Export report
     */
    public function exportReport(): array
    {
        try {
            $data = $this->analyticsService->getDashboardData(30);

            $csv = "Date,Average Rating,Total Reviews,5-Star,4-Star,3-Star,2-Star,1-Star,Completion Rate,Response Time\n";

            foreach ($data['analytics_trend'] as $trend) {
                $csv .= "{$trend['date']},{$trend['rating']},{$trend['reviews']},,,,,\n";
            }

            $message = "📥 <b>Report Export</b>\n\n";
            $message .= "Report generated successfully!\n\n";
            $message .= "📊 <b>Summary</b>\n";
            $message .= "Average Rating: {$data['average_rating']}/5.0\n";
            $message .= "Total Reviews: {$data['total_reviews']}\n";
            $message .= "Period: Last 30 days\n\n";
            $message .= "The report has been generated and is ready for download.\n";

            Log::info('Report exported', [
                'user_id' => $this->userId,
                'average_rating' => $data['average_rating'],
                'total_reviews' => $data['total_reviews'],
            ]);

            return [
                'success' => true,
                'message' => $message,
                'buttons' => [[
                    ['text' => '🔙 Back to Dashboard', 'callback_data' => 'analytics_dashboard'],
                ]],
                'export_data' => $csv,
            ];
        } catch (\Exception $e) {
            Log::error('Error exporting report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error exporting report.',
            ];
        }
    }

    /**
     * Acknowledge negative review
     */
    public function acknowledgeNegativeReview(int $alertId): array
    {
        try {
            $success = $this->analyticsService->acknowledgeAlert($alertId, 'Acknowledged via Telegram');

            if ($success) {
                return [
                    'success' => true,
                    'message' => "✅ <b>Alert Acknowledged</b>\n\nThe negative review has been marked as acknowledged.",
                    'buttons' => [[
                        ['text' => '🔙 Back to Alerts', 'callback_data' => 'analytics_negative'],
                    ]],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '❌ Error acknowledging alert.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error acknowledging negative review', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing request.',
            ];
        }
    }
}
