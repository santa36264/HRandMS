<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Requests\Review\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    /**
     * GET /guest/reviews
     * Public: approved reviews (optionally filtered by room_id).
     * Authenticated: also returns the user's own reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['room', 'booking', 'user'])
            ->where('is_approved', true);

        // Filter by room if provided (public room detail page)
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->integer('room_id'));
        }

        // If authenticated, also include the user's own unapproved reviews
        if ($request->user()) {
            $query = Review::with(['room', 'booking', 'user'])
                ->where(function ($q) use ($request) {
                    $q->where('is_approved', true)
                      ->orWhere('user_id', $request->user()->id);
                });

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->integer('room_id'));
            }
        }

        $reviews = $query->latest()->paginate($request->integer('per_page', 10));

        return $this->success([
            'reviews'    => ReviewResource::collection($reviews),
            'pagination' => [
                'total'        => $reviews->total(),
                'per_page'     => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * POST /guest/reviews
     * Submit a review for a completed booking.
     * One review per booking, only after check-out.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();

        $booking = Booking::where('user_id', $request->user()->id)
            ->findOrFail($data['booking_id']);

        // Allow review if checked_out, OR if confirmed/paid and check-out date has passed
        $pastCheckout = $booking->check_out_date && $booking->check_out_date < now()->toDateString();
        $reviewable   = $booking->status === 'checked_out'
            || ($pastCheckout && in_array($booking->status, ['confirmed', 'paid']));

        if (! $reviewable) {
            return $this->error('You can only review completed stays.', 422);
        }

        // One review per booking
        if ($booking->review()->exists()) {
            return $this->error('You have already reviewed this booking.', 422);
        }

        $review = Review::create([
            ...$data,
            'user_id'    => $request->user()->id,
            'room_id'    => $booking->room_id,
            'is_approved'=> false,
        ]);

        return $this->success(
            new ReviewResource($review->load(['room', 'booking'])),
            'Review submitted. It will appear after approval.',
            201
        );
    }

    /**
     * PUT /guest/reviews/{id}
     * Edit own review (only if not yet approved).
     */
    public function update(UpdateReviewRequest $request, int $id): JsonResponse
    {
        $review = Review::where('user_id', $request->user()->id)->findOrFail($id);

        if ($review->is_approved) {
            return $this->error('Approved reviews cannot be edited.', 403);
        }

        $review->update($request->validated());

        return $this->success(new ReviewResource($review->fresh(['room', 'booking'])), 'Review updated.');
    }

    /**
     * DELETE /guest/reviews/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $review = Review::where('user_id', $request->user()->id)->findOrFail($id);

        if ($review->is_approved) {
            return $this->error('Approved reviews cannot be deleted.', 403);
        }

        $review->delete();

        return $this->success(null, 'Review deleted.');
    }
}
