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
        Schema::create('enrollment_application_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_application_id');
            $table->string('requirement_key', 80);
            $table->string('label', 180);
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index('enrollment_application_id', 'enroll_app_docs_app_idx');
            $table->unique(
                ['enrollment_application_id', 'requirement_key'],
                'enroll_app_docs_requirement_unique',
            );
            $table->foreign('enrollment_application_id', 'enroll_app_docs_application_fk')
                ->references('id')
                ->on('enrollment_applications')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_application_documents');
    }
};
