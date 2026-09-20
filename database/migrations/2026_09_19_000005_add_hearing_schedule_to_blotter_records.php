<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->date('hearing_date')->nullable()->after('status');
            $table->time('hearing_time')->nullable()->after('hearing_date');
        });
    }

    public function down(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropColumn(['hearing_date', 'hearing_time']);
        });
    }
};
