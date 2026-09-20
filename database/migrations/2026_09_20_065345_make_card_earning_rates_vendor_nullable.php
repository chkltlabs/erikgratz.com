<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_earning_rates', function (Blueprint $table) {
            $table->string('vendor')->nullable()->default(null)->change();
        });

        DB::table('card_earning_rates')->where('vendor', '')->update(['vendor' => null]);
    }

    public function down(): void
    {
        DB::table('card_earning_rates')->whereNull('vendor')->update(['vendor' => '']);

        Schema::table('card_earning_rates', function (Blueprint $table) {
            $table->string('vendor')->nullable(false)->default('')->change();
        });
    }
};
