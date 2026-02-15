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
        Schema::create('simple_fin_rules', function (Blueprint $table) {
            $table->id();
            $table->string('pattern');
            $table->string('spend_type');
            $table->unsignedBigInteger('spend_id');
            $table->timestamps();

            $table->index(['spend_type', 'spend_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('simple_fin_rules');
    }
};
