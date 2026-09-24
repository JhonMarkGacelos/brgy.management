<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->boolean('is_social_pensioner')->default(false)->after('is_senior_citizen');
            $table->boolean('has_other_pension')->default(false)->after('is_social_pensioner');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['is_social_pensioner', 'has_other_pension']);
        });
    }
};
