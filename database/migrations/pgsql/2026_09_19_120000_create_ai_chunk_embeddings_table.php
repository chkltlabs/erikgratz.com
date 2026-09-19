<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var string
     */
    protected $connection = 'pgsql';

    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::dropIfExists('ai_chunk_embeddings');

        Schema::create('ai_chunk_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chunk_id')->unique();
            $table->vector('embedding', 768);
            $table->string('embedding_model');
            $table->timestamps();

            $table->index('embedding_model');
        });

        DB::statement(
            'CREATE INDEX ai_chunk_embeddings_embedding_cosine_idx ON ai_chunk_embeddings USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chunk_embeddings');
    }
};
