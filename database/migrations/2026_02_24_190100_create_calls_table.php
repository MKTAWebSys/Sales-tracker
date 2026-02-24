<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('caller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handed_over_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at');
            $table->string('outcome', 50)->default('pending');
            $table->text('summary')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('meeting_planned_at')->nullable();
            $table->timestamps();

            $table->index('called_at');
            $table->index('outcome');
            $table->index('caller_id');
            $table->index(['company_id', 'called_at']);
            $table->index('next_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
