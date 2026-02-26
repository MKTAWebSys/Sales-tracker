<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('first_caller_user_id')
                ->nullable()
                ->after('assigned_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('first_caller_assigned_at')
                ->nullable()
                ->after('first_caller_user_id');

            $table->timestamp('first_contacted_at')
                ->nullable()
                ->after('first_caller_assigned_at');

            $table->index(['status', 'first_caller_user_id', 'first_caller_assigned_at'], 'companies_call_queue_idx');
            $table->index('first_contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_call_queue_idx');
            $table->dropIndex(['first_contacted_at']);
            $table->dropConstrainedForeignId('first_caller_user_id');
            $table->dropColumn(['first_caller_assigned_at', 'first_contacted_at']);
        });
    }
};
