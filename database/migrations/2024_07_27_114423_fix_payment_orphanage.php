<?php

use App\Models\Payment;
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
        foreach (Payment::all() as $payment) {
            if ($payment->spend == null) {
                $payment->delete();
            }
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('spend_id')
                ->references('id')
                ->on('spends')
                ->onDelete('cascade');
        });
    }
};
