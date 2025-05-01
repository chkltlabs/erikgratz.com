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
        Schema::create('loan_against_savings', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance');
            $table->string('reason')->nullable();
            $table->date('loan_date');
            $table->date('paid_on');
            $table->boolean('is_paid')->default(false);
            $table->unsignedInteger('card_id')->nullable();
        });
    }
};
