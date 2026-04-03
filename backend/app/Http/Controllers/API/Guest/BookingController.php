<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BookingService $bookingService,
        private AvailabilityService $availability,
    ) {}

    /**
     * GET /api/guest/bookings
     * List the authenticated guest's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with('room')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return $this->success([
            'bookings'   => BookingResource::collection($bookings),
            'pagination' => [
                'total'        => $bookings->total(),
                'per_page'     => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/guest/bookings/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with('room')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $this->success(new BookingResource($booking));
    }

    /**
     * GET /api/guest/bookings/preview
     * Calculate price before confirming — no DB write.
     *
     * Query: room_id, check_in_date, check_out_date, discount_amount
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'room_id'        => ['required', 'integer', 'exists:rooms,id'],
            'check_in_date'  => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'discount_amount'=> ['nullable', 'numeric', 'min:0'],
        ]);

        $room     = Room::findOrFail($request->room_id);
        $checkIn  = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $nights   = $checkIn->diffInDays($checkOut);
        $discount = (float) ($request->discount_amount ?? 0);
        $total    = round($room->price_per_night * $nights, 2);
        $final    = round($total - $discount, 2);

        $isAvailable = $this->availability->isRoomAvailable(
            $room->id,
            $request->check_in_date,
            $request->check_out_date,
        );

        return $this->success([
            'room'             => [
                'id'             => $room->id,
                'name'           => $room->name,
                'room_number'    => $room->room_number,
                'type'           => $room->type,
                'price_per_night'=> (float) $room->price_per_night,
            ],
            'check_in_date'    => $checkIn->toDateString(),
            'check_out_date'   => $checkOut->toDateString(),
            'nights'           => $nights,
            'price_per_night'  => (float) $room->price_per_night,
            'total_amount'     => $total,
            'discount_amount'  => $discount,
            'final_amount'     => $final,
            'is_available'     => $isAvailable,
            'breakdown'        => [
                'label'  => "{$nights} night" . ($nights !== 1 ? 's' : '') . " × \${$room->price_per_night}",
                'total'  => $total,
                'discount' => $discount,
                'final'  => $final,
            ],
        ]);
    }

    /**
     * POST /api/guest/bookings
     * Create a booking with full price calculation and availability guard.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create(
            $request->user(),
            $request->validated(),
        );

        $booking->load('room');

        return $this->success(
            new BookingResource($booking),
            'Booking created successfully.',
            201,
        );
    }

    /**
     * GET /api/guest/bookings/{id}/checkin-token
     * Returns a signed HMAC payload for the check-in QR code.
     * Valid for 24 hours; refreshed automatically by the frontend.
     */
    public function checkinToken(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with('room')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->findOrFail($id);

        $expiresAt = Carbon::now()->addHours(24)->timestamp;

        $payload = [
            'booking_id'        => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'room_number'       => $booking->room?->room_number,
            'guest_name'        => $request->user()->name,
            'check_in_date'     => $booking->check_in_date,
            'check_out_date'    => $booking->check_out_date,
            'expires_at'        => $expiresAt,
        ];

        $secret    = config('app.key');
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $qrPayload = base64_encode(json_encode(array_merge($payload, ['sig' => $signature])));

        return $this->success([
            'qr_payload' => $qrPayload,
            'expires_at' => $expiresAt,
            'booking'    => [
                'id'                => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'room_number'       => $booking->room?->room_number,
                'check_in_date'     => $booking->check_in_date,
                'check_out_date'    => $booking->check_out_date,
            ],
        ]);
    }

    /**
     * GET /api/guest/bookings/by-reference/{ref}
     * Look up a booking by its booking_reference — used by PaymentResultView.
     */
    public function byReference(Request $request, string $ref): JsonResponse
    {
        $booking = Booking::with('room')
            ->where('user_id', $request->user()->id)
            ->where('booking_reference', $ref)
            ->firstOrFail();

        return $this->success(new BookingResource($booking));
    }

    /**
     * DELETE /api/guest/bookings/{id}
     */
    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        $booking = Booking::where('user_id', $request->user()->id)->findOrFail($id);

        $booking = $this->bookingService->cancel($booking, (string) ($request->input('reason') ?? ''));

        return $this->success(new BookingResource($booking), 'Booking cancelled successfully.');
    }
}
