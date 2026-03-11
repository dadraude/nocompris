<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\ShoppingListItemVisibility;

class ShoppingListItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShoppingListItem $shoppingListItem): bool
    {
        return $shoppingListItem->isVisibleTo($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Shop $shop): bool
    {
        return $shop->isVisibleTo($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShoppingListItem $shoppingListItem): bool
    {
        if ($shoppingListItem->visibility === ShoppingListItemVisibility::Private) {
            return $shoppingListItem->user_id === $user->id;
        }

        return $shoppingListItem->shop->isVisibleTo($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShoppingListItem $shoppingListItem): bool
    {
        return $this->update($user, $shoppingListItem);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ShoppingListItem $shoppingListItem): bool
    {
        return $this->update($user, $shoppingListItem);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ShoppingListItem $shoppingListItem): bool
    {
        return $this->update($user, $shoppingListItem);
    }
}
