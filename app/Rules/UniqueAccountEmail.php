<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueAccountEmail implements ValidationRule
{
    public function __construct(private ?int $exceptId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $value))])
            ->when($this->exceptId, fn ($query) => $query->where('id', '<>', $this->exceptId))->exists()) {
            $fail('Cette adresse e-mail est déjà utilisée par un autre compte.');
        }
    }
}
