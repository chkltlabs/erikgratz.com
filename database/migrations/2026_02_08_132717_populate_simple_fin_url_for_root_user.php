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
        $rootExample = \App\Models\User::factory()->root()->make();
        \App\Models\User::whereEmail($rootExample->email)
            ->update([
                'simple_fin_url' => env('SIMPLE_FIN_URL')
            ]);
    }
};
