<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedInteger('points_balance')->default(0);
            $table->unsignedInteger('points_bonus')->default(0);
            $table->unsignedInteger('points_bonus_spend')->default(0);
            $table->date('date_opened')->nullable();
            $table->string('points_bonus_period')->nullable();
            $table->string('color')->default('#126bc5');
        });
    }
};
