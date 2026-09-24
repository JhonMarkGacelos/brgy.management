<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('fourps_id_url')->nullable()->after('is_4ps');
            $table->string('fourps_id_public_id')->nullable()->after('fourps_id_url');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['fourps_id_url', 'fourps_id_public_id']);
        });
    }
};
