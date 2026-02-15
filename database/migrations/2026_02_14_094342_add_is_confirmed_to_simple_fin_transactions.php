<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('simple_fin_transactions', function (Blueprint $table) {
            $table->boolean('is_confirmed')->default(false)->after('is_pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('simple_fin_transactions', function (Blueprint $table) {
            $table->dropColumn('is_confirmed');
        });
    }
};
