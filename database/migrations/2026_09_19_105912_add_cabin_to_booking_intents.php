<?php

use App\Enums\BookingCabin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_intents', function (Blueprint $table) {
            $table->string('cabin')->nullable()->default(BookingCabin::Economy);
        });
    }

    public function down(): void
    {
        Schema::table('booking_intents', function (Blueprint $table) {
            $table->dropColumn('cabin');
        });
    }
};
