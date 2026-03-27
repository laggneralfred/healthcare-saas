<?php

namespace Tests\Feature;

use App\Models\InventoryProduct;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InventoryProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Practice $practiceA;
    private Practice $practiceB;
    private User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->practiceA = Practice::factory()->create(['name' => 'Practice A']);
        $this->practiceB = Practice::factory()->create(['name' => 'Practice B']);

        $this->userA = User::factory()->create(['practice_id' => $this->practiceA->id]);
    }

    public function test_practice_a_cannot_see_practice_b_products(): void
    {
        $productB = InventoryProduct::factory()->create(['practice_id' => $this->practiceB->id]);

        $this->actingAs($this->userA);

        $products = InventoryProduct::get();

        $this->assertFalse($products->contains($productB));
    }

    public function test_creating_product_sets_correct_default_stock(): void
    {
        $product = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'name' => 'Test Product',
        ]);

        $this->assertEquals(0, $product->stock_quantity);
    }

    public function test_is_low_stock_returns_true_when_at_threshold(): void
    {
        $product = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'stock_quantity' => 10,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_below_threshold(): void
    {
        $product = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    public function test_is_low_stock_returns_false_when_above_threshold(): void
    {
        $product = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'stock_quantity' => 15,
            'low_stock_threshold' => 10,
        ]);

        $this->assertFalse($product->isLowStock());
    }

    public function test_soft_deleted_products_do_not_appear_in_active_scope(): void
    {
        $activeProduct = InventoryProduct::factory()->create(['practice_id' => $this->practiceA->id]);
        $deletedProduct = InventoryProduct::factory()->create(['practice_id' => $this->practiceA->id]);

        $deletedProduct->delete();

        $this->actingAs($this->userA);

        $products = InventoryProduct::get();

        $this->assertTrue($products->contains($activeProduct));
        $this->assertFalse($products->contains($deletedProduct));
    }

    public function test_active_scope_filters_inactive_products(): void
    {
        $activeProduct = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'is_active' => true,
        ]);
        $inactiveProduct = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->userA);

        $activeProducts = InventoryProduct::active()->get();

        $this->assertTrue($activeProducts->contains($activeProduct));
        $this->assertFalse($activeProducts->contains($inactiveProduct));
    }

    public function test_low_stock_scope_filters_low_stock_products(): void
    {
        $lowStockProduct = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
        ]);
        $normalProduct = InventoryProduct::factory()->create([
            'practice_id' => $this->practiceA->id,
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
        ]);

        $this->actingAs($this->userA);

        $lowStockProducts = InventoryProduct::lowStock()->get();

        $this->assertTrue($lowStockProducts->contains($lowStockProduct));
        $this->assertFalse($lowStockProducts->contains($normalProduct));
    }
}
