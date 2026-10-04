<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'status')) {
            DB::table('students')
                ->whereNotIn('status', ['active', 'inactive', 'dropped'])
                ->update(['status' => 'inactive']);
        }
    }

    /** The status normalization is retained so later updates are not overwritten. */
    public function down(): void {}
};
