<?php

namespace Database\Factories;

use App\Models\InventoryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryProduct>
 */
class InventoryProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Herbal Formula', 'Single Herb', 'Supplement', 'Other'];
        $units = ['bottle', 'packet', 'gram', 'capsule', 'tablet'];

        $category = $this->faker->randomElement($categories);

        $prices = match ($category) {
            'Herbal Formula' => [18, 25, 35, 45],
            'Single Herb' => [8, 10, 12, 15],
            'Supplement' => [20, 25, 30, 35],
            default => [15, 20, 25],
        };

        $sellingPrice = $this->faker->randomElement($prices);
        $costPrice = $sellingPrice * $this->faker->randomFloat(2, 0.4, 0.7);

        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'sku' => strtoupper($this->faker->bothify('INV-####')),
            'description' => $this->faker->optional()->sentence(),
            'category' => $category,
            'unit' => $this->faker->randomElement($units),
            'selling_price' => $sellingPrice,
            'cost_price' => round($costPrice, 2),
            'stock_quantity' => 0,
            'low_stock_threshold' => 10,
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
