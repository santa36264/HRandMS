<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(private AnalyticsService $analytics) {}

    /**
     * GET /api/admin/analytics/dashboard
     * Overview cards: revenue, bookings, rooms, today's activity.
     */
    public function dashboard(): JsonResponse
    {
        return $this->success($this->analytics->dashboardSummary());
    }

    /**
     * GET /api/admin/analytics/revenue?year=2026
     * Monthly revenue breakdown for a given year.
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = (int) $request->input('year', now()->year);

        return $this->success($this->analytics->monthlyRevenue($year));
    }

    /**
     * GET /api/admin/analytics/occupancy?year=2026
     * Monthly occupancy rates for a given year.
     */
    public function occupancy(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = (int) $request->input('year', now()->year);

        return $this->success($this->analytics->occupancyRates($year));
    }

    /**
     * GET /api/admin/analytics/payments?year=2026
     * Payment method and gateway breakdown.
     */
    public function payments(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = $request->filled('year') ? (int) $request->year : null;

        return $this->success($this->analytics->paymentMethodBreakdown($year));
    }

    /**
     * GET /api/admin/analytics/rooms?year=2026
     * Revenue and booking count per room type + top 5 rooms.
     */
    public function rooms(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year = $request->filled('year') ? (int) $request->year : null;

        return $this->success($this->analytics->roomPerformance($year));
    }
}
