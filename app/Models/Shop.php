<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_group_id',
        'name',
        'color',
        'position',
    ];

    /**
     * Get the owner of the shop.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group that can access the shop.
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /**
     * Get the shopping list items for the shop.
     */
    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('position');
    }

    /**
     * Determine whether the shop has visible pending items for the given user.
     */
    public function hasVisiblePendingItemsFor(User $user): bool
    {
        if (array_key_exists('visible_pending_items_count', $this->attributes)) {
            return (int) $this->getAttribute('visible_pending_items_count') > 0;
        }

        return $this->shoppingListItems()
            ->visibleTo($user)
            ->where('purchased', false)
            ->exists();
    }

    /**
     * Scope the query to shops visible to the given user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id);

            if ($user->user_group_id !== null) {
                $query->orWhere('user_group_id', $user->user_group_id);
            }
        });
    }

    /**
     * Determine whether the shop is visible to the given user.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->user_group_id !== null
            && $this->user_group_id === $user->user_group_id;
    }
}
