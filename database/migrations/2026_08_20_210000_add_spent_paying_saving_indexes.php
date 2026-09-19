<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->index('due_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['is_paid', 'paid_on']);
            $table->index(['spend_type', 'spend_id']);
        });

        Schema::table('loan_against_savings', function (Blueprint $table) {
            $table->index(['is_paid', 'paid_on']);
        });

        Schema::table('state_dumps', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['is_paid', 'paid_on']);
            $table->dropIndex(['spend_type', 'spend_id']);
        });

        Schema::table('loan_against_savings', function (Blueprint $table) {
            $table->dropIndex(['is_paid', 'paid_on']);
        });

        Schema::table('state_dumps', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
