<?php

use App\Models\Card;
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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('balance')->default(0);
            $table->unsignedInteger('limit')->default(0);
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('card_id')
                ->references('id')
                ->on('cards');
        });

        Card::create(['name' => 'CS Reserve']);
        Card::create(['name' => 'CS Preferred']);
        Card::create(['name' => 'Cap1 Venture X']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cards');
    }
};
