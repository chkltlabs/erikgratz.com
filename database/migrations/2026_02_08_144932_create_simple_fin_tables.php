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
        Schema::create('simple_fin_organizations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('url')->nullable();
            $table->string('sfin_url')->nullable();
            $table->timestamps();
        });

        Schema::create('simple_fin_accounts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('simple_fin_organization_id');
            $table->string('name');
            $table->string('currency', 3);
            $table->decimal('balance', 15, 2);
            $table->decimal('available_balance', 15, 2);
            $table->timestamp('balance_date');
            $table->timestamps();

            $table->foreign('simple_fin_organization_id')->references('id')->on('simple_fin_organizations')->cascadeOnDelete();
        });

        Schema::create('simple_fin_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('simple_fin_account_id');
            $table->timestamp('posted');
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->string('payee')->nullable();
            $table->text('memo')->nullable();
            $table->timestamp('transacted_at')->nullable();
            $table->timestamps();

            $table->foreign('simple_fin_account_id')->references('id')->on('simple_fin_accounts')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('simple_fin_transactions');
        Schema::dropIfExists('simple_fin_accounts');
        Schema::dropIfExists('simple_fin_organizations');
    }
};
