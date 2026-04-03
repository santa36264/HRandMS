<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    use ApiResponse;

    /** GET /api/admin/guests */
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'guest')
            ->withCount(['bookings', 'reviews'])
            ->with(['bookings' => fn($q) => $q->latest()->limit(1)])
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($q2) =>
                    $q2->where('name',  'like', "%{$s}%")
                       ->orWhere('email', 'like', "%{$s}%")
                       ->orWhere('phone', 'like', "%{$s}%")
                )
            )
            ->when($request->nationality, fn($q, $v) => $q->where('nationality', $v))
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'));

        $guests = $query->paginate($request->integer('per_page', 15));

        return $this->success([
            'guests'     => UserResource::collection($guests),
            'pagination' => [
                'total'        => $guests->total(),
                'per_page'     => $guests->perPage(),
                'current_page' => $guests->currentPage(),
                'last_page'    => $guests->lastPage(),
            ],
        ]);
    }

    /** GET /api/admin/guests/{guest} */
    public function show(User $guest): JsonResponse
    {
        $guest->loadCount(['bookings', 'reviews', 'payments'])
              ->load(['bookings' => fn($q) => $q->with('room')->latest()->limit(5)]);

        return $this->success([
            'guest'    => new UserResource($guest),
            'bookings' => BookingResource::collection($guest->bookings),
            'stats'    => [
                'total_bookings' => $guest->bookings_count,
                'total_reviews'  => $guest->reviews_count,
                'total_payments' => $guest->payments_count,
                'total_spent'    => $guest->payments()->where('status', 'completed')->sum('amount'),
            ],
        ]);
    }

    /** PATCH /api/admin/guests/{guest}/toggle-active */
    public function toggleActive(User $guest): JsonResponse
    {
        // We use email_verified_at as a proxy for "active" — null = suspended
        if ($guest->email_verified_at) {
            $guest->update(['email_verified_at' => null]);
            $status = 'suspended';
        } else {
            $guest->update(['email_verified_at' => now()]);
            $status = 'active';
        }

        return $this->success(['status' => $status], "Guest {$status}.");
    }

    /** DELETE /api/admin/guests/{guest} */
    public function destroy(User $guest): JsonResponse
    {
        $hasActive = $guest->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->exists();

        if ($hasActive) {
            return $this->error('Cannot delete a guest with active bookings.', 409);
        }

        $guest->delete();

        return $this->success(null, 'Guest deleted successfully.');
    }
}
