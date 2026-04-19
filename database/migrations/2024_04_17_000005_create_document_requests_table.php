<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->enum('document_type', [
                'Barangay Clearance',
                'Certificate of Indigency',
                'Certificate of Residency',
                'Business Clearance',
            ]);
            $table->string('purpose');
            $table->decimal('fee', 8, 2)->default(0);
            $table->string('or_number')->nullable();

            // Status
            $table->enum('status', [
                'Pending',
                'Pending Official',
                'Approved',
                'Issued',
                'Rejected',
            ])->default('Pending');
            $table->text('remarks')->nullable();
            $table->timestamp('issued_at')->nullable();

            // Ownership
            $table->foreignId('resident_id')->nullable()->constrained('residents')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
