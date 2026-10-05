<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('expires_at');
        });

        // Announcements published before this change were emailed the moment they were posted.
        DB::table('announcements')->where('status', 'Published')->update(['emailed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('emailed_at');
        });
    }
};
