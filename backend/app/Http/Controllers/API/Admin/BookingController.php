<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(private BookingService $bookingService) {}

    // GET /admin/bookings
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['user', 'room'])
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($q2) =>
                    $q2->where('booking_reference', 'like', "%{$s}%")
                       ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%")
                                                         ->orWhere('email', 'like', "%{$s}%"))
                       ->orWhereHas('room', fn($r) => $r->where('name', 'like', "%{$s}%"))
                )
            )
            ->when($request->status,         fn($q, $v) => $q->where('status', $v))
            ->when($request->payment_status, fn($q, $v) => $q->where('payment_status', $v))
            ->when($request->room_type,      fn($q, $v) => $q->whereHas('room', fn($r) => $r->where('type', $v)))
            ->when($request->date_from,      fn($q, $v) => $q->whereDate('check_in_date', '>=', $v))
            ->when($request->date_to,        fn($q, $v) => $q->whereDate('check_in_date', '<=', $v));

        // Sorting
        $sortable = ['created_at', 'check_in_date', 'check_out_date', 'final_amount', 'status'];
        $sort     = in_array($request->sort_by, $sortable) ? $request->sort_by : 'created_at';
        $dir      = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $bookings = $query->paginate($request->integer('per_page', 15));

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

    // GET /admin/bookings/{id}
    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['user', 'room', 'payments'])->findOrFail($id);
        return $this->success(new BookingResource($booking));
    }

    // PATCH /admin/bookings/{id}/status
    public function updateStatus(UpdateBookingStatusRequest $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $updates = ['status' => $request->status];

        if ($request->status === 'cancelled') {
            $updates['cancellation_reason'] = $request->cancellation_reason ?? '';
            $updates['cancelled_at']        = now();
        }

        $booking->update($updates);

        return $this->success(
            new BookingResource($booking->load(['user', 'room'])),
            'Booking status updated.'
        );
    }

    // DELETE /admin/bookings/{id}
    public function destroy(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();
        return $this->success(null, 'Booking deleted.');
    }
}
