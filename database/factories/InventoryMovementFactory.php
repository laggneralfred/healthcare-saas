<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use Faker\Factory as FakerFactory;
use App\Models\InventoryProduct;
use App\Models\Practice;
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
        $faker = FakerFactory::create();
        return [
            'id'                   => $faker->uuid(),
            'practice_id'          => Practice::factory(),
            'inventory_product_id' => InventoryProduct::factory(),
            'type'                 => $faker->randomElement(['sale', 'restock', 'adjustment', 'return']),
            'quantity'             => $faker->randomElement([1, 5, 10, -1, -5, -10]),
            'unit_price'           => $faker->optional()->randomFloat(2, 1, 100),
            'reference'            => $faker->optional()->word(),
            'notes'                => $faker->optional()->sentence(),
            'created_by'           => null,
        ];
    }
}
