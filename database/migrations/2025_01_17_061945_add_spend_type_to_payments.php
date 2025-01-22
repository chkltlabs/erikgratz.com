<?php

use App\Models\Spend;
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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('spend_type')
                ->default(getMorphAliasForClass(Spend::class));
            $table->dropForeign('payments_spend_id_foreign');
        });
    }
};
