<?php

namespace App\Rules;

use App\Models\Member;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueDni implements ValidationRule
{
    public function __construct(private ?int $exceptMemberId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hash = hash('sha256', trim((string) $value));
        $query = Member::where('dni_hash', $hash);

        if ($this->exceptMemberId) {
            $query->where('id', '!=', $this->exceptMemberId);
        }

        if ($query->exists()) {
            $fail('Ya existe un socio registrado con ese DNI.');
        }
    }
}
