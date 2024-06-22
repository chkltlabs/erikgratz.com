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
        Schema::create('spends', function (Blueprint $table) {
            $table->id();
            $table->date('spend_on');
            $table->date('spend_at');
            $table->tinyText('name');
            $table->double('amount');
            $table->tinytext('type');
            $table->tinyText('subtype')
                ->nullable();
            $table->string('month_on')
                ->storedAs('DATE_FORMAT(spend_on, "%M")');
            $table->string('month_at')
                ->storedAs('DATE_FORMAT(spend_at, "%M")');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spends');
    }
};
