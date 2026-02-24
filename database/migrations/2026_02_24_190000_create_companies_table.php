<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ico', 32)->nullable();
            $table->string('website')->nullable();
            $table->string('status', 50)->default('new');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('status');
            $table->index('assigned_user_id');
            $table->index('ico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
