<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** "Junior High School" and "Senior High School" student levels are now "Junior High" and "Senior High". */
    public function up(): void
    {
        DB::table('residents')->where('education', 'Junior High School')->update(['education' => 'Junior High']);
        DB::table('residents')->where('education', 'Senior High School')->update(['education' => 'Senior High']);
    }

    public function down(): void
    {
        DB::table('residents')->where('education', 'Junior High')->update(['education' => 'Junior High School']);
        DB::table('residents')->where('education', 'Senior High')->update(['education' => 'Senior High School']);
    }
};
