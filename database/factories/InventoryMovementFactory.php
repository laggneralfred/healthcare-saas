<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'type' => $this->faker->randomElement(['sale', 'restock', 'adjustment', 'return']),
            'quantity' => $this->faker->randomElement([1, 5, 10, -1, -5, -10]),
            'unit_price' => $this->faker->optional()->randomFloat(2, 1, 100),
            'reference' => $this->faker->optional()->word(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
