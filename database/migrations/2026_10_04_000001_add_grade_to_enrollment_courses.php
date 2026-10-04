<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('enrollment_course', 'grade')) {
            Schema::table('enrollment_course', function (Blueprint $table): void {
                $table->decimal('grade', 4, 2)->nullable();
            });
        }

        if (Schema::hasTable('course_student') && Schema::hasColumn('course_student', 'enrollment_id')) {
            DB::transaction(function (): void {
                $legacyGrades = DB::table('course_student')
                    ->whereNotNull('enrollment_id')
                    ->whereNotNull('grade')
                    ->get(['enrollment_id', 'course_id', 'grade']);

                foreach ($legacyGrades as $legacyGrade) {
                    DB::table('enrollment_course')
                        ->where('enrollment_id', $legacyGrade->enrollment_id)
                        ->where('course_id', $legacyGrade->course_id)
                        ->whereNull('grade')
                        ->update(['grade' => $legacyGrade->grade]);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_student') && Schema::hasColumn('course_student', 'enrollment_id')) {
            DB::transaction(function (): void {
                $enrollmentGrades = DB::table('enrollment_course')
                    ->whereNotNull('grade')
                    ->get(['enrollment_id', 'course_id', 'grade']);

                foreach ($enrollmentGrades as $enrollmentGrade) {
                    DB::table('course_student')
                        ->where('enrollment_id', $enrollmentGrade->enrollment_id)
                        ->where('course_id', $enrollmentGrade->course_id)
                        ->update(['grade' => $enrollmentGrade->grade]);
                }
            });
        }

        if (Schema::hasColumn('enrollment_course', 'grade')) {
            Schema::table('enrollment_course', function (Blueprint $table): void {
                $table->dropColumn('grade');
            });
        }
    }
};
