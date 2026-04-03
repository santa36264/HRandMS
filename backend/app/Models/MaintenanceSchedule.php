<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id', 'assigned_to', 'title', 'description',
        'type', 'priority', 'status',
        'scheduled_at', 'started_at', 'completed_at',
        'cost', 'notes',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'cost'          => 'decimal:2',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeScheduled($query)   { return $query->where('status', 'scheduled'); }
    public function scopeInProgress($query)  { return $query->where('status', 'in_progress'); }
    public function scopeCompleted($query)   { return $query->where('status', 'completed'); }
    public function scopeUrgent($query)      { return $query->where('priority', 'urgent'); }
    public function scopeUpcoming($query)    { return $query->where('scheduled_at', '>=', now()); }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at->isPast();
    }

    public function duration(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMinutes($this->completed_at);
        }
        return null;
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
