<?php

namespace App\Concerns;

use App\ShoppingListItemVisibility;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rule as ValidationRule;

trait ShoppingListValidationRules
{
    /**
     * Get the validation rules used to validate shop data.
     *
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    protected function shopDataRules(): array
    {
        return [
            'name' => $this->itemNameRules(),
        ];
    }

    /**
     * Get the validation rules used to validate shopping list item data.
     *
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    protected function shoppingListItemDataRules(): array
    {
        return [
            'name' => $this->itemNameRules(),
            'quantity' => $this->quantityRules(),
            'visibility' => $this->visibilityRules(),
        ];
    }

    /**
     * Get the validation rules used to validate an item or shop name.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function itemNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate an item quantity.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function quantityRules(): array
    {
        return ['required', 'integer', 'min:1'];
    }

    /**
     * Get the validation rules used to validate item visibility.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function visibilityRules(): array
    {
        return [
            'required',
            'string',
            ValidationRule::in(array_map(
                static fn (ShoppingListItemVisibility $visibility): string => $visibility->value,
                ShoppingListItemVisibility::cases(),
            )),
        ];
    }
}
