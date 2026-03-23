<?php

namespace App\Concerns;

use App\ShoppingListItemQuantityUnit;
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
            'color' => $this->shopColorRules(),
        ];
    }

    /**
     * Get the validation rules used to validate shopping list item data.
     *
     * @return array<string, array<int, Rule|array<mixed>|string>>
     */
    protected function shoppingListItemDataRules(ShoppingListItemQuantityUnit|string $quantityUnit = ShoppingListItemQuantityUnit::Unit): array
    {
        return [
            'name' => $this->itemNameRules(),
            'quantity' => $this->quantityRules($quantityUnit),
            'quantity_unit' => $this->quantityUnitRules(),
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
    protected function quantityRules(ShoppingListItemQuantityUnit|string $quantityUnit = ShoppingListItemQuantityUnit::Unit): array
    {
        $quantityUnit = $quantityUnit instanceof ShoppingListItemQuantityUnit
            ? $quantityUnit
            : ShoppingListItemQuantityUnit::from($quantityUnit);

        if ($quantityUnit->usesDecimalQuantity()) {
            return ['required', 'numeric', 'decimal:0,2', 'min:0.01'];
        }

        return ['required', 'integer', 'min:1'];
    }

    /**
     * Get the validation rules used to validate an item quantity unit.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function quantityUnitRules(): array
    {
        return [
            'required',
            'string',
            ValidationRule::in(array_map(
                static fn (ShoppingListItemQuantityUnit $quantityUnit): string => $quantityUnit->value,
                ShoppingListItemQuantityUnit::cases(),
            )),
        ];
    }

    /**
     * Get the validation rules used to validate a shop header color.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function shopColorRules(): array
    {
        return ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
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
