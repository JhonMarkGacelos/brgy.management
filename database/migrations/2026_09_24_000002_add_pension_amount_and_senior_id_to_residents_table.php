<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->decimal('pension_amount', 10, 2)->nullable()->after('has_other_pension');
            $table->string('senior_id_url')->nullable()->after('pension_amount');
            $table->string('senior_id_public_id')->nullable()->after('senior_id_url');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['pension_amount', 'senior_id_url', 'senior_id_public_id']);
        });
    }
};
