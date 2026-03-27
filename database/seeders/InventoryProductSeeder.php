<?php

namespace Database\Seeders;

use App\Models\InventoryProduct;
use App\Models\Practice;
use Illuminate\Database\Seeder;

class InventoryProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the demo practice
        $practice = Practice::where('slug', 'serenity-acupuncture')->first();

        if (!$practice) {
            return;
        }

        // Herbal Formulas
        $formulas = [
            ['name' => 'Xiao Yao San', 'price' => 28, 'cost' => 12, 'stock' => 15, 'low_threshold' => 5],
            ['name' => 'Gui Pi Wan', 'price' => 32, 'cost' => 14, 'stock' => 8, 'low_threshold' => 5],
            ['name' => 'Liu Wei Di Huang Wan', 'price' => 35, 'cost' => 16, 'stock' => 20, 'low_threshold' => 8],
            ['name' => 'Ba Zhen Tang', 'price' => 40, 'cost' => 18, 'stock' => 12, 'low_threshold' => 5],
            ['name' => 'Yin Qiao San', 'price' => 24, 'cost' => 10, 'stock' => 3, 'low_threshold' => 10],
            ['name' => 'Long Dan Xie Gan Wan', 'price' => 30, 'cost' => 13, 'stock' => 18, 'low_threshold' => 6],
            ['name' => 'Bu Zhong Yi Qi Tang', 'price' => 36, 'cost' => 16, 'stock' => 25, 'low_threshold' => 8],
            ['name' => 'Tian Wang Bu Xin Dan', 'price' => 38, 'cost' => 17, 'stock' => 14, 'low_threshold' => 6],
        ];

        foreach ($formulas as $formula) {
            InventoryProduct::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'practice_id' => $practice->id,
                'name' => $formula['name'],
                'sku' => strtoupper(str_replace(' ', '', substr($formula['name'], 0, 6))) . '-HF',
                'description' => 'Traditional Chinese herbal formula',
                'category' => 'Herbal Formula',
                'unit' => 'bottle',
                'selling_price' => $formula['price'],
                'cost_price' => $formula['cost'],
                'stock_quantity' => $formula['stock'],
                'low_stock_threshold' => $formula['low_threshold'],
                'is_active' => true,
            ]);
        }

        // Single Herbs
        $singleHerbs = [
            ['name' => 'Huang Qi', 'price' => 12, 'cost' => 5, 'stock' => 50, 'low_threshold' => 20],
            ['name' => 'Dang Gui', 'price' => 15, 'cost' => 7, 'stock' => 40, 'low_threshold' => 15],
            ['name' => 'Gou Qi Zi', 'price' => 14, 'cost' => 6, 'stock' => 8, 'low_threshold' => 20],
            ['name' => 'He Shou Wu', 'price' => 13, 'cost' => 6, 'stock' => 35, 'low_threshold' => 15],
        ];

        foreach ($singleHerbs as $herb) {
            InventoryProduct::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'practice_id' => $practice->id,
                'name' => $herb['name'],
                'sku' => strtoupper(str_replace(' ', '', substr($herb['name'], 0, 6))) . '-SH',
                'description' => 'Single herb medicinal ingredient',
                'category' => 'Single Herb',
                'unit' => 'gram',
                'selling_price' => $herb['price'],
                'cost_price' => $herb['cost'],
                'stock_quantity' => $herb['stock'],
                'low_stock_threshold' => $herb['low_threshold'],
                'is_active' => true,
            ]);
        }

        // Supplements
        $supplements = [
            ['name' => 'Magnesium Glycinate', 'price' => 28, 'cost' => 12, 'stock' => 22, 'low_threshold' => 10],
            ['name' => 'Vitamin D3', 'price' => 24, 'cost' => 10, 'stock' => 18, 'low_threshold' => 10],
            ['name' => 'Fish Oil', 'price' => 32, 'cost' => 14, 'stock' => 12, 'low_threshold' => 8],
        ];

        foreach ($supplements as $supplement) {
            InventoryProduct::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'practice_id' => $practice->id,
                'name' => $supplement['name'],
                'sku' => strtoupper(str_replace(' ', '', substr($supplement['name'], 0, 6))) . '-SUP',
                'description' => 'Quality supplement for wellness support',
                'category' => 'Supplement',
                'unit' => 'capsule',
                'selling_price' => $supplement['price'],
                'cost_price' => $supplement['cost'],
                'stock_quantity' => $supplement['stock'],
                'low_stock_threshold' => $supplement['low_threshold'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Inventory products seeded for Serenity Acupuncture & Wellness');
    }
}
