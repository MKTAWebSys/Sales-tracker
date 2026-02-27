<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('status', 'follow-up')
            ->whereExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('meetings')
                    ->whereColumn('meetings.company_id', 'companies.id')
                    ->whereIn('meetings.status', ['planned', 'confirmed']);
            })
            ->update(['status' => 'meeting']);
    }

    public function down(): void
    {
        DB::table('companies')
            ->where('status', 'meeting')
            ->update(['status' => 'follow-up']);
    }
};

