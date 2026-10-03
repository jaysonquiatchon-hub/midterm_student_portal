<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('programs', 'department_id')) {
            Schema::table('programs', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        if (Schema::hasColumn('enrollment_applications', 'department_id')) {
            Schema::table('enrollment_applications', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        Schema::dropIfExists('departments');

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropUnique('courses_code_unique');
            $table->unique(['program_id', 'code'], 'courses_program_code_unique');
        });
    }

    public function down(): void
    {
        throw new LogicException('Department removal and program-scoped course codes cannot be safely reversed after data migration.');
    }
};
