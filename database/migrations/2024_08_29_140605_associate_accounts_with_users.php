<?php

use App\Enums\AccountType;
use App\Models\Account;
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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('type')->default(AccountType::Checking)->after('id');
            $table->unsignedBigInteger('user_id')->default(1)->after('id');
        });

        $amy = User::whereName('Amy G')->first();
        if ($amy !== null) {
            Account::where('name', 'like', '%Amy%')->update(['user_id' => $amy->id]);
        }
        $erik = User::whereName('Erik G')->first();
        if ($erik !== null) {
            Account::where('name', 'like', '%Erik%')->update(['user_id' => $erik->id]);
        }
        Account::where('name', 'like', '%market%')->update(['type' => AccountType::MoneyMarket]);
        Account::where('name', 'like', '%sav%')->update(['type' => AccountType::Savings]);
        Account::where('name', 'like', '%checking%')->update(['type' => AccountType::Checking]);
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn('name');
        });

        if (app()->environment('local')) {
            Account::create([
                'user_id' => $amy->id,
                'balance' => 150,
                'type' => AccountType::Checking,
            ]);
            Account::create([
                'user_id' => $amy->id,
                'balance' => 25,
                'type' => AccountType::Savings,
            ]);
            Account::create([
                'user_id' => $amy->id,
                'balance' => 23565,
                'type' => AccountType::MoneyMarket,
            ]);
            Account::create([
                'user_id' => $erik->id,
                'balance' => 1700,
                'type' => AccountType::Checking,
            ]);
            Account::create([
                'user_id' => $erik->id,
                'balance' => 99,
                'type' => AccountType::Savings,
            ]);
            Account::create([
                'user_id' => $erik->id,
                'balance' => 300,
                'type' => AccountType::MoneyMarket,
            ]);
        }

    }
};
