<?php

namespace App\Models;

use App\ShoppingListItemQuantityUnit;
use App\ShoppingListItemVisibility;
use Database\Factories\ShoppingListItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    /** @use HasFactory<ShoppingListItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shop_id',
        'user_id',
        'name',
        'quantity',
        'quantity_unit',
        'visibility',
        'purchased',
        'purchased_at',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchased' => 'boolean',
            'purchased_at' => 'datetime',
            'quantity_unit' => ShoppingListItemQuantityUnit::class,
            'visibility' => ShoppingListItemVisibility::class,
        ];
    }

    /**
     * Get the quantity as it should be displayed in the UI.
     */
    public function formattedQuantity(): string
    {
        $quantityUnit = $this->quantity_unit instanceof ShoppingListItemQuantityUnit
            ? $this->quantity_unit
            : ShoppingListItemQuantityUnit::Unit;

        if (! $quantityUnit->usesDecimalQuantity()) {
            return sprintf('%d %s', (int) round((float) $this->quantity), $quantityUnit->value);
        }

        $formattedQuantity = number_format((float) $this->quantity, 2, ',', '');
        $formattedQuantity = rtrim(rtrim($formattedQuantity, '0'), ',');

        return "{$formattedQuantity} {$quantityUnit->value}";
    }

    /**
     * Update the purchased state and keep its timestamp in sync.
     */
    public function updatePurchaseState(bool $purchased): void
    {
        $this->update([
            'purchased' => $purchased,
            'purchased_at' => $purchased ? now() : null,
        ]);
    }

    /**
     * Get the shop that owns the item.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the creator of the item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope the query to items visible to the given user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->whereHas('shop', fn (Builder $query): Builder => $query->visibleTo($user))
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->orWhere('visibility', ShoppingListItemVisibility::Public->value);
            });
    }

    /**
     * Determine whether the item is visible to the given user.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->visibility === ShoppingListItemVisibility::Public
            && $this->shop->isVisibleTo($user);
    }
}
