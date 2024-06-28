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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spend_id');
            $table->unsignedDouble('amount');
            $table->boolean('is_paid')->default(false);
            $table->date('paid_on')->nullable();
            $table->unsignedBigInteger('card_id')->nullable();
            $table->timestamps();
        });
    }
};
