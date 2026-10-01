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
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_id', 20)->nullable()->unique();
            $table->string('middle_name', 60)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('civil_status', 30)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('status', 20)->default('active')->index();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('application_id')->nullable()->unique()->constrained('enrollment_applications')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('application_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropUnique(['student_id']);
            $table->dropColumn(['student_id', 'middle_name', 'suffix', 'gender', 'civil_status', 'nationality', 'contact_number', 'status']);
        });
    }
};
