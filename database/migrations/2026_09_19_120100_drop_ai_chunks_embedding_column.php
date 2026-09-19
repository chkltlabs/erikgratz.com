<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ai_chunks', 'embedding')) {
            Schema::table('ai_chunks', function (Blueprint $table) {
                $table->dropColumn('embedding');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ai_chunks', 'embedding')) {
            Schema::table('ai_chunks', function (Blueprint $table) {
                $table->json('embedding')->nullable();
            });
        }
    }
};
