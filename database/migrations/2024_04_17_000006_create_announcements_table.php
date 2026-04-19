<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('category')->default('General');
            $table->enum('audience', ['All Residents', 'Senior Citizens', 'PWD', '4Ps', 'Voters'])->default('All Residents');
            $table->enum('status', ['Draft', 'Published', 'Archived'])->default('Published');
            $table->date('published_at')->nullable();
            $table->date('expires_at')->nullable();

            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
