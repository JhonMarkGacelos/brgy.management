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
            $table->string('id_photo_url')->nullable()->after('or_number');
            $table->string('id_photo_public_id')->nullable()->after('id_photo_url');
            $table->enum('id_verified', ['pending', 'verified', 'rejected'])->default('pending')->after('id_photo_public_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['id_photo_url', 'id_photo_public_id', 'id_verified']);
        });
    }
};
