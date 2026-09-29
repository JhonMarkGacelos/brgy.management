<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('public_id')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('tables')->default(0);
            $table->unsignedInteger('rows')->default(0);
            $table->string('trigger', 20);
            $table->string('status', 20);
            $table->text('error')->nullable();
            $table->string('emailed_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
