<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('simple_fin_transactions', function (Blueprint $table) {
            // Polymorphic relation to Spend or PeriodicSpend
            $table->nullableMorphs('spend'); // adds spend_type & spend_id (nullable) + index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_fin_transactions', function (Blueprint $table) {
            $table->dropMorphs('spend');
        });
    }
};
