<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The "High School" student level is now "Junior High School", to pair with "Senior High School". */
    public function up(): void
    {
        DB::table('residents')
            ->where('education', 'High School')
            ->update(['education' => 'Junior High School']);
    }

    public function down(): void
    {
        DB::table('residents')
            ->where('education', 'Junior High School')
            ->update(['education' => 'High School']);
    }
};
