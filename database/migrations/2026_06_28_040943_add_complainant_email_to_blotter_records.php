<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->string('complainant_email')->nullable()->after('complainant_contact');
        });
    }

    public function down(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropColumn('complainant_email');
        });
    }
};
