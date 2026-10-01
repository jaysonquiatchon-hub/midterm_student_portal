<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enrollment_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->nullable()->unique();
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable();
            $table->string('last_name', 60);
            $table->string('suffix', 20)->nullable();
            $table->date('birth_date');
            $table->string('gender', 30);
            $table->string('civil_status', 30)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('email')->index();
            $table->string('contact_number', 30);
            $table->string('house_block_lot', 120)->nullable();
            $table->string('street', 160)->nullable();
            $table->string('barangay', 120);
            $table->string('city', 120);
            $table->string('province', 120);
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_type', 25);
            $table->unsignedTinyInteger('year_level');
            $table->string('school_year', 9);
            $table->string('semester', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->string('sample_username', 40)->nullable();
            $table->text('sample_password')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_applications');
    }
};
