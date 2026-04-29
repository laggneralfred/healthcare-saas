<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->string('method_key');
            $table->string('display_name');
            $table->boolean('enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['practice_id', 'method_key']);
            $table->index(['practice_id', 'enabled', 'sort_order']);
        });

        $methods = [
            'cash' => 'Cash',
            'check' => 'Check',
            'card_external' => 'Card (external)',
            'other' => 'Other',
            'comped' => 'Comped / no charge',
        ];
        $now = now();
        $rows = [];

        foreach (DB::table('practices')->pluck('id') as $practiceId) {
            foreach ($methods as $methodKey => $displayName) {
                $rows[] = [
                    'practice_id' => $practiceId,
                    'method_key' => $methodKey,
                    'display_name' => $displayName,
                    'enabled' => true,
                    'sort_order' => array_search($methodKey, array_keys($methods), true) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('practice_payment_methods')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_payment_methods');
    }
};
