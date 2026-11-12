<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to_user_id',
        'title',
        'content',
        'type',
        'priority',
        'note_date',
        'is_pinned',
        'is_completed',
        'completed_at',
        'completed_by_user_id',
        'checklist_items',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'checklist_items' => 'array',
        'note_date' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
