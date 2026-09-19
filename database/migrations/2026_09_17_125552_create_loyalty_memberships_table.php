<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('loyalty_program_id');
            $table->string('tier')->nullable();
            $table->string('loyalty_number')->nullable();
            $table->foreignId('conferred_by_card_id')->nullable()->constrained('cards')->cascadeOnDelete();
            $table->unsignedInteger('points_balance')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'loyalty_program_id']);
            $table->index('loyalty_program_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_memberships');
    }
};
