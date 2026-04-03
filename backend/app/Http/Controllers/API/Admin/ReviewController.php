<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    /** GET /api/admin/reviews */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user:id,name,email', 'room:id,name,room_number'])
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($q2) =>
                    $q2->where('title',   'like', "%{$s}%")
                       ->orWhere('comment', 'like', "%{$s}%")
                       ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$s}%"))
                )
            )
            ->when($request->filled('approved'), fn($q) =>
                $q->where('is_approved', (bool) $request->approved)
            )
            ->when($request->rating, fn($q, $v) => $q->where('rating', $v))
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'));

        $reviews = $query->paginate($request->integer('per_page', 15));

        $summary = [
            'total'    => Review::count(),
            'approved' => Review::where('is_approved', true)->count(),
            'pending'  => Review::where('is_approved', false)->count(),
            'avg'      => round(Review::where('is_approved', true)->avg('rating') ?? 0, 1),
        ];

        return $this->success([
            'reviews'    => ReviewResource::collection($reviews),
            'summary'    => $summary,
            'pagination' => [
                'total'        => $reviews->total(),
                'per_page'     => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
            ],
        ]);
    }

    /** PATCH /api/admin/reviews/{review}/approve */
    public function approve(Review $review): JsonResponse
    {
        $review->update(['is_approved' => true, 'approved_at' => now()]);
        return $this->success(new ReviewResource($review), 'Review approved.');
    }

    /** PATCH /api/admin/reviews/{review}/reject */
    public function reject(Review $review): JsonResponse
    {
        $review->update(['is_approved' => false, 'approved_at' => null]);
        return $this->success(new ReviewResource($review), 'Review rejected.');
    }

    /** DELETE /api/admin/reviews/{review} */
    public function destroy(Review $review): JsonResponse
    {
        $review->delete();
        return $this->success(null, 'Review deleted.');
    }
}
