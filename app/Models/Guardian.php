<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'dni', 'phone', 'email', 'relationship'];

    protected $casts = [
        'name' => 'encrypted',
        'phone' => 'encrypted',
        'email' => 'encrypted',
    ];

    protected function dni(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: fn (?string $value) => ['dni' => $value ? encrypt($value) : null],
        );
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_guardian')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
