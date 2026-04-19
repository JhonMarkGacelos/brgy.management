<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])->nullable();
            $table->string('nationality')->default('Filipino');
            $table->string('relationship_to_head')->nullable(); // e.g. Head, Spouse, Son, Daughter
            $table->boolean('is_head')->default(false);
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('occupation')->nullable();
            $table->string('education')->nullable();
            $table->boolean('is_4ps')->default(false);
            $table->boolean('is_senior_citizen')->default(false);
            $table->boolean('is_pwd')->default(false);
            $table->boolean('is_solo_parent')->default(false);
            $table->boolean('is_voter')->default(false);
            $table->boolean('is_indigent')->default(false);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
