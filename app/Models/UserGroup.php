<?php

namespace App\Models;

use Database\Factories\UserGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    /** @use HasFactory<UserGroupFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the users that belong to the group.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class)->orderBy('name');
    }

    /**
     * Get the shops shared with the group.
     */
    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class)->orderBy('position');
    }
}
