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
            $table->double('pending')->default(0)->after('balance');
            $table->double('interest_free_balance_payment')->default(0)->after('limit');
            $table->double('interest_free_balance')->default(0)->after('limit');
            $table->double('interest_saving_balance')->default(0)->after('limit');

            $table->double('balance')->default(0)->change();
            $table->double('limit')->default(0)->change();
            $table->dropColumn('due_date');
            $table->unsignedInteger('due_date')->default(1);
        });
    }
};
