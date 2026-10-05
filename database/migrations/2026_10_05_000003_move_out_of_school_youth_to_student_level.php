<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** "Out of School Youth" moved from the employment statuses to the student levels. */
    public function up(): void
    {
        DB::table('residents')
            ->where('employment_status', 'Out of School Youth')
            ->update(['employment_status' => 'Student', 'education' => 'Out of School Youth']);
    }

    public function down(): void
    {
        DB::table('residents')
            ->where('employment_status', 'Student')
            ->where('education', 'Out of School Youth')
            ->update(['employment_status' => 'Out of School Youth', 'education' => null]);
    }
};
