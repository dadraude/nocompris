<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_group_id' => null,
            'name' => fake()->company(),
            'position' => 1,
        ];
    }

    /**
     * Share the shop with the given group.
     */
    public function forGroup(?UserGroup $userGroup = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_group_id' => $userGroup?->getKey() ?? UserGroup::factory(),
        ]);
    }
}
