<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE users DROP CHECK users_role_check');
        } catch (\Throwable) {
            // Constraint doesn't exist or already removed — safe to ignore.
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin', 'staff', 'resident'))");
    }
};
