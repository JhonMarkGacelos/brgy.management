<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blotter_records', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->string('incident_type');
            $table->string('location')->nullable();

            // Parties
            $table->string('complainant_name');
            $table->string('complainant_address')->nullable();
            $table->string('complainant_contact')->nullable();
            $table->string('respondent_name');
            $table->string('respondent_address')->nullable();
            $table->string('respondent_contact')->nullable();
            $table->string('witnesses')->nullable();

            // Details
            $table->text('narrative');
            $table->text('action_taken')->nullable();

            // Workflow
            $table->enum('status', [
                'Open',
                'Pending Official',
                'Under Mediation',
                'Settled',
                'Referred',
                'Returned w/ Remarks',
            ])->default('Open');
            $table->text('remarks')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Ownership
            $table->foreignId('filed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained('residents')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blotter_records');
    }
};
