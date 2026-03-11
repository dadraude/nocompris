<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\ShoppingListItemVisibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingListItem>
 */
class ShoppingListItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'quantity' => fake()->numberBetween(1, 5),
            'visibility' => ShoppingListItemVisibility::Public,
            'purchased' => false,
            'position' => 1,
        ];
    }

    /**
     * Make the item private to its creator.
     */
    public function asPrivate(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => ShoppingListItemVisibility::Private,
        ]);
    }
}
