<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Something a role is allowed to do, e.g. "pos.manage". Super Admin assigns these to roles.
 */
class Permission extends Model
{
    protected $fillable = ['slug', 'name', 'module'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
