<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('status', 'contacted')
            ->update([
                'status' => 'follow-up',
                'assigned_user_id' => DB::raw('COALESCE(assigned_user_id, first_caller_user_id)'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally left empty: previous "contacted" state is removed from active flow.
    }
};

