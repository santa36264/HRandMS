<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\AvailabilityRequest;
use App\Http\Requests\Room\FilterRoomRequest;
use App\Http\Resources\RoomResource;
use App\Services\AvailabilityService;
use App\Services\RoomService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    use ApiResponse;

    public function __construct(
        private RoomService $roomService,
        private AvailabilityService $availability,
    ) {}

    /**
     * GET /api/guest/stats
     * Public hotel stats for the home page.
     */
    public function stats(): JsonResponse
    {
        $totalRooms    = DB::table('rooms')->where('is_active', true)->whereNull('deleted_at')->count();
        $totalGuests   = DB::table('users')->where('role', 'guest')->count();
        $totalBookings = DB::table('bookings')->whereNotIn('status', ['cancelled'])->count();
        $avgRating     = DB::table('reviews')->where('is_approved', true)->avg('rating');

        return $this->success([
            'total_rooms'    => $totalRooms,
            'total_guests'   => $totalGuests,
            'total_bookings' => $totalBookings,
            'average_rating' => $avgRating ? round($avgRating, 1) : null,
        ]);
    }

    /**
     * GET /api/guest/rooms
     * Browse rooms with optional filters (active rooms only).
     *
     * Query: type, capacity, min_price, max_price, floor,
     *        amenities[], search, sort_by, sort_dir, per_page
     */
    public function index(FilterRoomRequest $request): JsonResponse
    {
        $rooms = $this->roomService->filter($request->validated(), adminMode: false);

        return $this->success([
            'rooms'      => RoomResource::collection($rooms),
            'pagination' => [
                'total'        => $rooms->total(),
                'per_page'     => $rooms->perPage(),
                'current_page' => $rooms->currentPage(),
                'last_page'    => $rooms->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/guest/rooms/{id}
     */
    public function show(int $id): JsonResponse
    {
        $room = $this->roomService->findForGuest($id);
        $room->loadAvg('reviews', 'rating');

        return $this->success(new RoomResource($room));
    }

    /**
     * GET /api/guest/rooms/availability
     * Returns available rooms for a date range with optional filters.
     *
     * Query: check_in*, check_out*, guests, type, min_price, max_price
     */
    public function availability(AvailabilityRequest $request): JsonResponse
    {
        $rooms = $this->availability->getAvailableRooms(
            checkIn:  $request->check_in,
            checkOut: $request->check_out,
            guests:   $request->integer('guests') ?: null,
            type:     $request->input('type'),
            minPrice: $request->filled('min_price') ? $request->float('min_price') : null,
            maxPrice: $request->filled('max_price') ? $request->float('max_price') : null,
        );

        $nights = Carbon::parse($request->check_in)->diffInDays($request->check_out);

        return $this->success([
            'check_in'    => $request->check_in,
            'check_out'   => $request->check_out,
            'nights'      => $nights,
            'rooms_found' => $rooms->count(),
            'rooms'       => RoomResource::collection($rooms),
        ]);
    }

    /**
     * GET /api/guest/rooms/{id}/booked-dates
     * Returns blocked date ranges for calendar UI.
     *
     * Query: from (optional), until (optional)
     */
    public function bookedDates(Request $request, int $id): JsonResponse
    {
        $this->roomService->findForGuest($id);

        $dates = $this->availability->getBookedDates(
            $id,
            $request->input('from'),
            $request->input('until'),
        );

        return $this->success(['booked_dates' => $dates]);
    }
}
