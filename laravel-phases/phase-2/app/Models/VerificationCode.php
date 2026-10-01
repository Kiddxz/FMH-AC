<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 6-digit email code for registration or password reset (capstone Fig 6.3 / 6.4).
 * The code itself is stored hashed.
 */
class VerificationCode extends Model
{
    protected $fillable = ['email', 'code', 'purpose', 'attempts', 'expires_at', 'used_at'];

    protected $hidden = ['code'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
