<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    // =========================================================================
    // MONTHLY REVENUE
    // =========================================================================

    /**
     * Revenue grouped by month for the given year range.
     * Returns completed payments only.
     */
    public function monthlyRevenue(int $year, ?int $months = 12): array
    {
        $from  = Carbon::create($year, 1, 1)->startOfDay();
        $until = Carbon::create($year, $months, 1)->endOfMonth();

        $rows = DB::table('payments')
            ->selectRaw("
                DATE_FORMAT(paid_at, '%Y-%m') AS month,
                COUNT(*)                       AS transactions,
                SUM(amount)                    AS revenue,
                AVG(amount)                    AS avg_transaction
            ")
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $until])
            ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill every month even if no data
        $result = [];
        for ($m = 1; $m <= $months; $m++) {
            $key = Carbon::create($year, $m, 1)->format('Y-m');
            $row = $rows->get($key);
            $result[] = [
                'month'           => $key,
                'month_label'     => Carbon::create($year, $m, 1)->format('M Y'),
                'revenue'         => (float) ($row->revenue         ?? 0),
                'transactions'    => (int)   ($row->transactions    ?? 0),
                'avg_transaction' => (float) round($row->avg_transaction ?? 0, 2),
            ];
        }

        $total = array_sum(array_column($result, 'revenue'));
        $peak  = collect($result)->sortByDesc('revenue')->first();

        return [
            'year'          => $year,
            'total_revenue' => round($total, 2),
            'peak_month'    => $peak['month_label'] ?? null,
            'peak_revenue'  => $peak['revenue']     ?? 0,
            'monthly'       => $result,
        ];
    }

    // =========================================================================
    // OCCUPANCY RATES
    // =========================================================================

    /**
     * Occupancy rate per month = booked nights / (total rooms × days in month).
     */
    public function occupancyRates(int $year): array
    {
        $totalRooms = DB::table('rooms')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        if ($totalRooms === 0) return ['year' => $year, 'total_rooms' => 0, 'monthly' => []];

        // Sum of nights booked per month (checked_out or checked_in bookings)
        $rows = DB::table('bookings')
            ->selectRaw("
                DATE_FORMAT(check_in_date, '%Y-%m')                          AS month,
                SUM(DATEDIFF(check_out_date, check_in_date))                 AS booked_nights,
                COUNT(*)                                                      AS bookings_count,
                SUM(final_amount)                                             AS booking_revenue
            ")
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereYear('check_in_date', $year)
            ->groupByRaw("DATE_FORMAT(check_in_date, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $date        = Carbon::create($year, $m, 1);
            $key         = $date->format('Y-m');
            $daysInMonth = $date->daysInMonth;
            $capacity    = $totalRooms * $daysInMonth;
            $row         = $rows->get($key);
            $bookedNights = (int) ($row->booked_nights ?? 0);

            $result[] = [
                'month'           => $key,
                'month_label'     => $date->format('M Y'),
                'booked_nights'   => $bookedNights,
                'available_nights'=> $capacity,
                'occupancy_rate'  => $capacity > 0 ? round(($bookedNights / $capacity) * 100, 1) : 0,
                'bookings_count'  => (int)   ($row->bookings_count  ?? 0),
                'booking_revenue' => (float) ($row->booking_revenue ?? 0),
            ];
        }

        $avgOccupancy = round(collect($result)->avg('occupancy_rate'), 1);
        $peak         = collect($result)->sortByDesc('occupancy_rate')->first();

        return [
            'year'            => $year,
            'total_rooms'     => $totalRooms,
            'avg_occupancy'   => $avgOccupancy,
            'peak_month'      => $peak['month_label']    ?? null,
            'peak_occupancy'  => $peak['occupancy_rate'] ?? 0,
            'monthly'         => $result,
        ];
    }

    // =========================================================================
    // PAYMENT METHOD BREAKDOWN
    // =========================================================================

    /**
     * Revenue and transaction count grouped by gateway/method.
     */
    public function paymentMethodBreakdown(?int $year = null): array
    {
        $query = DB::table('payments')->where('status', 'completed');

        if ($year) $query->whereYear('paid_at', $year);

        $total = (float) (clone $query)->sum('amount');

        $byGateway = (clone $query)
            ->selectRaw("
                gateway,
                COUNT(*)    AS transactions,
                SUM(amount) AS revenue,
                AVG(amount) AS avg_amount
            ")
            ->groupBy('gateway')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($r) => [
                'gateway'      => $r->gateway,
                'label'        => $this->gatewayLabel($r->gateway),
                'transactions' => (int)   $r->transactions,
                'revenue'      => (float) round($r->revenue, 2),
                'avg_amount'   => (float) round($r->avg_amount, 2),
                'percentage'   => $total > 0 ? round(($r->revenue / $total) * 100, 1) : 0,
            ])
            ->values()
            ->toArray();

        $byMethod = (clone $query)
            ->selectRaw("
                method,
                COUNT(*)    AS transactions,
                SUM(amount) AS revenue
            ")
            ->groupBy('method')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($r) => [
                'method'       => $r->method,
                'transactions' => (int)   $r->transactions,
                'revenue'      => (float) round($r->revenue, 2),
                'percentage'   => $total > 0 ? round(($r->revenue / $total) * 100, 1) : 0,
            ])
            ->values()
            ->toArray();

        return [
            'year'          => $year,
            'total_revenue' => round($total, 2),
            'by_gateway'    => $byGateway,
            'by_method'     => $byMethod,
        ];
    }

    // =========================================================================
    // DASHBOARD SUMMARY
    // =========================================================================

    /**
     * Single endpoint for the admin dashboard overview cards.
     */
    public function dashboardSummary(): array
    {
        $today     = now()->toDateString();
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        // Revenue
        $revenueThisMonth = (float) DB::table('payments')
            ->where('status', 'completed')
            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$thisMonth])
            ->sum('amount');

        $revenueLastMonth = (float) DB::table('payments')
            ->where('status', 'completed')
            ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$lastMonth])
            ->sum('amount');

        // Bookings
        $bookingStats = DB::table('bookings')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending'    THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'confirmed'  THEN 1 ELSE 0 END) AS confirmed,
                SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) AS checked_in,
                SUM(CASE WHEN status = 'cancelled'  THEN 1 ELSE 0 END) AS cancelled
            ")
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])
            ->first();

        // Rooms
        $roomStats = DB::table('rooms')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'available'   THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status = 'occupied'    THEN 1 ELSE 0 END) AS occupied,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance
            ")
            ->first();

        // Today's check-ins / check-outs
        $todayCheckins  = DB::table('bookings')->where('check_in_date', $today)->whereIn('status', ['confirmed', 'checked_in'])->count();
        $todayCheckouts = DB::table('bookings')->where('check_out_date', $today)->where('status', 'checked_in')->count();

        // New guests this month
        $newGuests = DB::table('users')->where('role', 'guest')
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$thisMonth])
            ->count();

        // Revenue trend (%)
        $revenueTrend = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : null;

        return [
            'revenue' => [
                'this_month'  => round($revenueThisMonth, 2),
                'last_month'  => round($revenueLastMonth, 2),
                'trend_pct'   => $revenueTrend,
                'currency'    => 'ETB',
            ],
            'bookings' => [
                'this_month' => (int) $bookingStats->total,
                'pending'    => (int) $bookingStats->pending,
                'confirmed'  => (int) $bookingStats->confirmed,
                'checked_in' => (int) $bookingStats->checked_in,
                'cancelled'  => (int) $bookingStats->cancelled,
            ],
            'rooms' => [
                'total'       => (int) $roomStats->total,
                'available'   => (int) $roomStats->available,
                'occupied'    => (int) $roomStats->occupied,
                'maintenance' => (int) $roomStats->maintenance,
                'occupancy_rate' => $roomStats->total > 0
                    ? round(($roomStats->occupied / $roomStats->total) * 100, 1)
                    : 0,
            ],
            'today' => [
                'check_ins'   => $todayCheckins,
                'check_outs'  => $todayCheckouts,
                'date'        => $today,
            ],
            'guests' => [
                'new_this_month' => $newGuests,
            ],
        ];
    }

    // =========================================================================
    // ROOM PERFORMANCE
    // =========================================================================

    /**
     * Revenue and booking count per room type.
     */
    public function roomPerformance(?int $year = null): array
    {
        $query = DB::table('bookings')
            ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->whereIn('bookings.status', ['confirmed', 'checked_in', 'checked_out']);

        if ($year) $query->whereYear('bookings.check_in_date', $year);

        $byType = (clone $query)
            ->selectRaw("
                rooms.type,
                COUNT(bookings.id)                              AS bookings,
                SUM(DATEDIFF(check_out_date, check_in_date))   AS total_nights,
                SUM(bookings.final_amount)                      AS revenue,
                AVG(bookings.final_amount)                      AS avg_booking_value
            ")
            ->groupBy('rooms.type')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($r) => [
                'type'              => $r->type,
                'bookings'          => (int)   $r->bookings,
                'total_nights'      => (int)   $r->total_nights,
                'revenue'           => (float) round($r->revenue, 2),
                'avg_booking_value' => (float) round($r->avg_booking_value, 2),
            ])
            ->values()
            ->toArray();

        $topRooms = (clone $query)
            ->selectRaw("
                rooms.id,
                rooms.room_number,
                rooms.name,
                rooms.type,
                COUNT(bookings.id)   AS bookings,
                SUM(final_amount)    AS revenue
            ")
            ->groupBy('rooms.id', 'rooms.room_number', 'rooms.name', 'rooms.type')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'room_number' => $r->room_number,
                'name'        => $r->name,
                'type'        => $r->type,
                'bookings'    => (int)   $r->bookings,
                'revenue'     => (float) round($r->revenue, 2),
            ])
            ->toArray();

        return [
            'year'      => $year,
            'by_type'   => $byType,
            'top_rooms' => $topRooms,
        ];
    }

    // =========================================================================
    // PRIVATE
    // =========================================================================

    private function gatewayLabel(string $gateway): string
    {
        return match ($gateway) {
            'telebirr' => 'Telebirr',
            'cbe_birr' => 'CBE Birr',
            'cash'     => 'Cash',
            default    => ucfirst(str_replace('_', ' ', $gateway)),
        };
    }
}
