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
            $table->string('payment_receipt_url')->nullable()->after('id_verified');
            $table->string('payment_receipt_public_id')->nullable()->after('payment_receipt_url');
            $table->enum('payment_verified', ['pending', 'verified', 'rejected'])->nullable()->after('payment_receipt_public_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_receipt_url', 'payment_receipt_public_id', 'payment_verified']);
        });
    }
};
