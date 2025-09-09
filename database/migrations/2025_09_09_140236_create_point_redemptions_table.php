<?php

use App\Enums\PointsProgram;
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
        Schema::create('point_redemptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('activity_id');
            $table->tinytext('type');
            $table->tinyText('subtype')
                ->nullable();
            $table->date('paid_on')->nullable();
            $table->string('points_program')->default(PointsProgram::Unknown);
            $table->unsignedInteger('points_spent')->default(0);
            $table->decimal('money_spent', 8, 2)->default(0);
            $table->decimal('cash_value', 8, 2)->default(0);
            $table->timestamps();
        });
    }
};
