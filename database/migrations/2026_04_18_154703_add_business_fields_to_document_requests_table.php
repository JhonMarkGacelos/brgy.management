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
        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('purpose');
            $table->string('business_type')->nullable()->after('business_name');
            $table->string('business_address')->nullable()->after('business_type');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['business_name', 'business_type', 'business_address']);
        });
    }
};
