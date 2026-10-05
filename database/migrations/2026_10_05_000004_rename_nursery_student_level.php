<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The "Nursery" student level is now "Daycare". */
    public function up(): void
    {
        DB::table('residents')
            ->where('education', 'Nursery')
            ->update(['education' => 'Daycare']);
    }

    public function down(): void
    {
        DB::table('residents')
            ->where('education', 'Daycare')
            ->update(['education' => 'Nursery']);
    }
};
