<?php

use App\Models\Account;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedDouble('balance')->default(0);
            $table->timestamps();
        });

        Account::create([
            'name' => 'Amy Checking',
            'balance' => 187.88,
        ]);

        Account::create([
            'name' => 'Amy Savings',
            'balance' => 94.13,
        ]);

        Account::create([
            'name' => 'Amy Money Market',
            'balance' => 23259.68,
        ]);
        Account::create([
            'name' => 'Erik Checking',
            'balance' => 565.60,
        ]);

        Account::create([
            'name' => 'Erik Savings',
            'balance' => 466.00,
        ]);

        Account::create([
            'name' => 'Erik Money Market',
            'balance' => 30.32,
        ]);
    }
};
