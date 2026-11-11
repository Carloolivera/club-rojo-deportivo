<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'sport_id', 'started_at', 'ended_at',
        'leave_reason_id', 'leave_note',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function leaveReason(): BelongsTo
    {
        return $this->belongsTo(LeaveReason::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
