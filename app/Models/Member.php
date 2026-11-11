<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'dni', 'email', 'phone', 'address',
        'birthdate', 'is_minor', 'sport_id', 'fee_category_id',
        'status', 'consent_at', 'consent_ip', 'notes',
    ];

    protected $casts = [
        'name' => 'encrypted',
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'address' => 'encrypted',
        'birthdate' => 'date',
        'is_minor' => 'boolean',
        'consent_at' => 'datetime',
    ];

    // dni is handled manually to also compute dni_hash
    protected function dni(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: function (string $value) {
                return [
                    'dni' => encrypt($value),
                    'dni_hash' => hash('sha256', $value),
                ];
            },
        );
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'member_guardian')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(MemberPeriod::class);
    }

    public function reenrollments(): HasMany
    {
        return $this->hasMany(Reenrollment::class);
    }

    public function activate(User $causedBy): void
    {
        $this->update(['status' => 'active']);
        activity()
            ->performedOn($this)
            ->causedBy($causedBy)
            ->withProperties(['status' => 'active'])
            ->log('Socio activado');
    }

    public function deactivate(User $causedBy, int $reasonId, ?string $notes = null): void
    {
        $this->update(['status' => 'inactive', 'notes' => $notes ?? $this->notes]);
        activity()
            ->performedOn($this)
            ->causedBy($causedBy)
            ->withProperties(['status' => 'inactive', 'reason_id' => $reasonId])
            ->log('Socio dado de baja');
    }

    public function suspend(User $causedBy, string $notes): void
    {
        $this->update(['status' => 'suspended']);
        activity()
            ->performedOn($this)
            ->causedBy($causedBy)
            ->withProperties(['status' => 'suspended', 'notes' => $notes])
            ->log('Socio suspendido');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySport($query, int $sportId)
    {
        return $query->where('sport_id', $sportId);
    }
}
