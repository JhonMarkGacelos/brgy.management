<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->string('pwd_id_url')->nullable()->after('is_pwd');
            $table->string('pwd_id_public_id')->nullable()->after('pwd_id_url');
            $table->string('solo_parent_id_url')->nullable()->after('is_solo_parent');
            $table->string('solo_parent_id_public_id')->nullable()->after('solo_parent_id_url');
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['pwd_id_url', 'pwd_id_public_id', 'solo_parent_id_url', 'solo_parent_id_public_id']);
        });
    }
};
