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
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('semester', 20)->default('1st');
            $table->string('status', 20)->default('active')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['semester', 'status']);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('status');
        });
    }
};
