<?php

use App\Enums\PointsProgram;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_routes', function (Blueprint $table) {
            $table->id();
            $table->string('from_program')->default(PointsProgram::Unknown);
            $table->foreignId('loyalty_program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->decimal('base_ratio', 8, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['from_program', 'loyalty_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_routes');
    }
};
