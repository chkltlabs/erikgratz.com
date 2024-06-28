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
        foreach (DB::table('spends')->get() as $spend) {
            Payment::create([
                'spend_id' => $spend->id,
                'amount' => $spend->amount,
                'is_paid' => true,
                'paid_on' => $spend->spend_at,
            ]);
        }
        Schema::table('spends', function (Blueprint $table) {
            $table->dropColumn(['amount', 'spend_for', 'spend_at', 'month_for', 'month_at']);
        });
    }
};
