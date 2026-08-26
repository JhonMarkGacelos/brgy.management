<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('issued_at');
        });

        // Backfill existing paid records (or_number already assigned) using updated_at
        // as the best available proxy, since no payment timestamp existed before this.
        DB::table('document_requests')
            ->whereNotNull('or_number')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
