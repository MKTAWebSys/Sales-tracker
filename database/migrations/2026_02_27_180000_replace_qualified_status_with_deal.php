<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('status', 'qualified')
            ->update(['status' => 'deal']);
    }

    public function down(): void
    {
        DB::table('companies')
            ->where('status', 'deal')
            ->update(['status' => 'qualified']);
    }
};

