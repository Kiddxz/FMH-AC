<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A system account: Super Admin, Veterinarian/Admin, Staff/Receptionist or Customer.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // Columns that may be filled from a form. role_id and status are NOT here on purpose:
    // they are set only by trusted code (e.g. Super Admin screens), never directly from a form.
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'password',
    ];

    // Default values for a new account (same as the database defaults)
    protected $attributes = [
        'status' => 'active',
    ];

    // Never send these to the browser
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',   // passwords are hashed automatically when saved
        ];
    }

    // ---------- Relationships ----------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    // The customer profile linked to this account (only for Customer accounts)
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    // ---------- Helpers ----------

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Example: $user->hasRole('staff') or $user->hasRole('vet_admin', 'super_admin')
    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    // Example: $user->hasPermission('pos.manage')
    public function hasPermission(string $slug): bool
    {
        return $this->role?->permissions->contains('slug', $slug) ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
