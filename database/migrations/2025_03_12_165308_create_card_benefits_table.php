<?php

use App\Enums\ResetPeriod;
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
        Schema::create('card_benefits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->string('benefit');
            $table->string('description', 2048)->nullable();
            $table->boolean('is_useable')->default(true);
            $table->boolean('is_used')->default(false);
            $table->decimal('value')->nullable();
            $table->string('reset_period')->default(ResetPeriod::NoReset);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('card_benefits');
    }
};
