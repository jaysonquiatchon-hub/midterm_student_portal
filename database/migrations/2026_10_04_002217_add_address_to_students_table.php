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
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'address')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->text('address')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('students', 'address')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('address');
            });
        }
    }
};
