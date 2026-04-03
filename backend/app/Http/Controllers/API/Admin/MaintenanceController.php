<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use App\Models\Room;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    use ApiResponse;

    /** GET /api/admin/maintenance */
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceSchedule::with(['room:id,name,room_number', 'assignedTo:id,name'])
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($q2) =>
                    $q2->where('title', 'like', "%{$s}%")
                       ->orWhereHas('room', fn($r) => $r->where('name', 'like', "%{$s}%")
                           ->orWhere('room_number', 'like', "%{$s}%"))
                )
            )
            ->when($request->status,   fn($q, $v) => $q->where('status', $v))
            ->when($request->priority, fn($q, $v) => $q->where('priority', $v))
            ->when($request->type,     fn($q, $v) => $q->where('type', $v))
            ->orderBy($request->input('sort_by', 'scheduled_at'), $request->input('sort_dir', 'desc'));

        $items = $query->paginate($request->integer('per_page', 15));

        // Summary counts
        $summary = [
            'total'       => MaintenanceSchedule::count(),
            'scheduled'   => MaintenanceSchedule::where('status', 'scheduled')->count(),
            'in_progress' => MaintenanceSchedule::where('status', 'in_progress')->count(),
            'completed'   => MaintenanceSchedule::where('status', 'completed')->count(),
            'overdue'     => MaintenanceSchedule::where('status', 'scheduled')
                                ->where('scheduled_at', '<', now())->count(),
        ];

        return $this->success([
            'items'      => $items->items(),
            'summary'    => $summary,
            'pagination' => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ],
        ]);
    }

    /** POST /api/admin/maintenance */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id'      => 'required|exists:rooms,id',
            'assigned_to'  => 'nullable|exists:users,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'type'         => 'required|in:cleaning,repair,inspection,renovation,other',
            'priority'     => 'required|in:low,medium,high,urgent',
            'status'       => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'scheduled_at' => 'required|date',
            'cost'         => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        $item = MaintenanceSchedule::create($data);
        $item->load(['room:id,name,room_number', 'assignedTo:id,name']);

        return $this->success($item, 'Maintenance task created.', 201);
    }

    /** PUT /api/admin/maintenance/{id} */
    public function update(Request $request, MaintenanceSchedule $maintenance): JsonResponse
    {
        $data = $request->validate([
            'room_id'      => 'sometimes|exists:rooms,id',
            'assigned_to'  => 'nullable|exists:users,id',
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'type'         => 'sometimes|in:cleaning,repair,inspection,renovation,other',
            'priority'     => 'sometimes|in:low,medium,high,urgent',
            'status'       => 'sometimes|in:scheduled,in_progress,completed,cancelled',
            'scheduled_at' => 'sometimes|date',
            'started_at'   => 'nullable|date',
            'completed_at' => 'nullable|date',
            'cost'         => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string',
        ]);

        // Auto-set timestamps
        if (isset($data['status'])) {
            if ($data['status'] === 'in_progress' && !$maintenance->started_at) {
                $data['started_at'] = now();
            }
            if ($data['status'] === 'completed' && !$maintenance->completed_at) {
                $data['completed_at'] = now();
            }
        }

        $maintenance->update($data);
        $maintenance->load(['room:id,name,room_number', 'assignedTo:id,name']);

        return $this->success($maintenance, 'Maintenance task updated.');
    }

    /** DELETE /api/admin/maintenance/{id} */
    public function destroy(MaintenanceSchedule $maintenance): JsonResponse
    {
        $maintenance->delete();
        return $this->success(null, 'Maintenance task deleted.');
    }

    /** GET /api/admin/maintenance/rooms-list  — for dropdown */
    public function roomsList(): JsonResponse
    {
        $rooms = Room::select('id', 'name', 'room_number')->orderBy('room_number')->get();
        return $this->success($rooms);
    }

    /** GET /api/admin/maintenance/staff-list  — for dropdown */
    public function staffList(): JsonResponse
    {
        $staff = User::whereIn('role', ['admin', 'staff'])->select('id', 'name')->orderBy('name')->get();
        return $this->success($staff);
    }
}
