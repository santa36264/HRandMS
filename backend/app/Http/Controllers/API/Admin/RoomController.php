<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\FilterRoomRequest;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    use ApiResponse;

    public function __construct(private RoomService $roomService) {}

    /**
     * GET /api/admin/rooms
     * All rooms with full filtering (admin sees inactive rooms too).
     */
    public function index(FilterRoomRequest $request): JsonResponse
    {
        $rooms = $this->roomService->filter($request->validated(), adminMode: true);

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
     * GET /api/admin/rooms/{room}
     */
    public function show(Room $room): JsonResponse
    {
        $room->loadCount(['bookings', 'reviews'])
             ->load(['bookings' => fn($q) => $q->latest()->limit(5)]);

        return $this->success(new RoomResource($room));
    }

    /**
     * POST /api/admin/rooms
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->validated());

        return $this->success(new RoomResource($room), 'Room created successfully.', 201);
    }

    /**
     * PUT /api/admin/rooms/{room}
     */
    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room = $this->roomService->update($room, $request->validated());

        return $this->success(new RoomResource($room), 'Room updated successfully.');
    }

    /**
     * DELETE /api/admin/rooms/{room}
     */
    public function destroy(Room $room): JsonResponse
    {
        try {
            $this->roomService->delete($room);
            return $this->success(null, 'Room deleted successfully.');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 409);
        }
    }
}
