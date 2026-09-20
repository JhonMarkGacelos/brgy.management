<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blotter_records', function (Blueprint $table) {
            $table->string('incident_type_other')->nullable()->after('incident_type');
        });

        DB::table('blotter_records')->where('incident_type', 'Physical Altercation')->update(['incident_type' => 'Physical Fight']);
        DB::table('blotter_records')->where('incident_type', 'Domestic')->update(['incident_type' => 'Family / Domestic Dispute']);
    }

    public function down(): void
    {
        DB::table('blotter_records')->where('incident_type', 'Physical Fight')->update(['incident_type' => 'Physical Altercation']);
        DB::table('blotter_records')->where('incident_type', 'Family / Domestic Dispute')->update(['incident_type' => 'Domestic']);

        Schema::table('blotter_records', function (Blueprint $table) {
            $table->dropColumn('incident_type_other');
        });
    }
};
