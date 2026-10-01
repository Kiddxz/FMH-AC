<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the 4 roles: super_admin, vet_admin, staff, customer.
 */
class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';
    public const VET_ADMIN = 'vet_admin';
    public const STAFF = 'staff';
    public const CUSTOMER = 'customer';

    protected $fillable = ['name', 'slug', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
