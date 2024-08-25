<?php

use App\Models\User;
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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('monthly_pay', 10, 2)->default(0.00);
        });

        User::whereName('Erik G')->update(['monthly_pay' => 9622.32]);
    }
};
