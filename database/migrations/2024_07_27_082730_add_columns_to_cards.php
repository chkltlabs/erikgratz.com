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
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedDouble('pending')->default(0)->after('balance');
            $table->unsignedDouble('interest_free_balance_payment')->default(0)->after('limit');
            $table->unsignedDouble('interest_free_balance')->default(0)->after('limit');
            $table->unsignedDouble('interest_saving_balance')->default(0)->after('limit');

            $table->unsignedDouble('balance')->default(0)->change();
            $table->unsignedDouble('limit')->default(0)->change();
            $table->unsignedInteger('due_date')->default(1)->change();
        });
    }
};
