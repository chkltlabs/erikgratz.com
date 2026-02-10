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
        if(app()->environment('local')) {
            \App\Models\User::where('id', 1)->update(['simple_fin_url' => env('SIMPLE_FIN_URL')]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local', function (Blueprint $table) {
            //
        });
    }
};
