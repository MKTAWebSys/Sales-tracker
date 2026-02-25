<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('website');
            $table->string('phone', 64)->nullable()->after('contact_person');

            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropColumn(['contact_person', 'phone']);
        });
    }
};
