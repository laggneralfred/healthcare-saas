<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Practice $practice;
    private User $user;
    private InventoryProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->practice = Practice::factory()->create();
        $this->user = User::factory()->create(['practice_id' => $this->practice->id]);
        $this->product = InventoryProduct::factory()->create([
            'practice_id' => $this->practice->id,
            'stock_quantity' => 0,
        ]);
    }

    public function test_creating_restock_movement_increases_stock(): void
    {
        $this->product->movements()->create([
            'practice_id' => $this->practice->id,
            'type' => 'restock',
            'quantity' => 10,
            'created_by' => $this->user->id,
        ]);

        $this->product->refresh();

        $this->assertEquals(10, $this->product->stock_quantity);
    }

    public function test_creating_sale_movement_decreases_stock(): void
    {
        $this->product->update(['stock_quantity' => 20]);

        $this->product->movements()->create([
            'practice_id' => $this->practice->id,
            'type' => 'sale',
            'quantity' => -5,
            'created_by' => $this->user->id,
        ]);

        $this->product->refresh();

        $this->assertEquals(15, $this->product->stock_quantity);
    }

    public function test_movement_is_scoped_to_correct_practice(): void
    {
        $practiceB = Practice::factory()->create();

        $movement = $this->product->movements()->create([
            'practice_id' => $this->practice->id,
            'type' => 'restock',
            'quantity' => 10,
        ]);

        $this->assertEquals($this->practice->id, $movement->practice_id);
    }

    public function test_multiple_movements_accumulate_stock(): void
    {
        $this->product->movements()->create([
            'practice_id' => $this->practice->id,
            'type' => 'restock',
            'quantity' => 10,
        ]);

        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock_quantity);

        $this->product->movements()->create([
            'practice_id' => $this->practice->id,
            'type' => 'restock',
            'quantity' => 5,
        ]);

        $this->product->refresh();
        $this->assertEquals(15, $this->product->stock_quantity);
    }
}
