<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Cases filed directly as Settled/Referred never got a closing date; use their last update as the best available. */
    public function up(): void
    {
        DB::table('blotter_records')
            ->whereIn('status', ['Settled', 'Referred'])
            ->whereNull('resolved_at')
            ->update(['resolved_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        // Not reversible: the original (missing) value carried no information.
    }
};
