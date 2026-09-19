<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_benefits', function (Blueprint $table) {
            $table->json('allowed_vendors')->nullable();
            $table->json('max_apply_per_use')->nullable();
            $table->boolean('award_only')->default(false);
            $table->decimal('auto_assume_amount', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('card_benefits', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_vendors',
                'max_apply_per_use',
                'award_only',
                'auto_assume_amount',
            ]);
        });
    }
};
