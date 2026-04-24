<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Employment, Business, Remittance, Pension, 4Ps, Others
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('households', function (Blueprint $table) {
            $table->string('classification')->nullable()->after('purok');
            $table->decimal('welfare_score', 5, 2)->nullable()->after('classification');
            $table->decimal('per_capita_income', 10, 2)->nullable()->after('welfare_score');
        });

        // Seed per-capita thresholds into settings
        $seeds = [
            ['key' => 'per_capita_extremely_poor', 'value' => '1500'],
            ['key' => 'per_capita_poor',            'value' => '2500'],
            ['key' => 'per_capita_near_poor',       'value' => '3500'],
            ['key' => 'per_capita_vulnerable',      'value' => '5000'],
        ];
        foreach ($seeds as $s) {
            DB::table('settings')->insertOrIgnore(array_merge($s, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('income_sources');
        Schema::table('households', function (Blueprint $table) {
            $table->dropColumn(['classification', 'welfare_score', 'per_capita_income']);
        });
    }
};
