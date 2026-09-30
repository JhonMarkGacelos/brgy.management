<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The edit-member modal used to save "Self-employed"; every other form saves "Self-Employed". */
    public function up(): void
    {
        DB::table('residents')
            ->where('employment_status', 'Self-employed')
            ->update(['employment_status' => 'Self-Employed']);
    }

    public function down(): void
    {
        // Not reversible: both spellings meant the same thing.
    }
};
