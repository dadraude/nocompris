<?php

namespace App;

enum ShoppingListItemQuantityUnit: string
{
    case Unit = 'u';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Liter = 'l';
    case Centiliter = 'cl';

    /**
     * Determine whether this unit should accept decimal quantities.
     */
    public function usesDecimalQuantity(): bool
    {
        return match ($this) {
            self::Unit => false,
            self::Kilogram, self::Gram, self::Liter, self::Centiliter => true,
        };
    }

    /**
     * Get the human-readable label for this unit.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unitats',
            self::Kilogram => 'Quilograms',
            self::Gram => 'Grams',
            self::Liter => 'Litres',
            self::Centiliter => 'Centilitres',
        };
    }
}
