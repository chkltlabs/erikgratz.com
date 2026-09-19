<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_imported_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('title', 2048);
            $table->string('model')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->timestamp('imported_at');
            $table->longText('raw_payload')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
        });

        Schema::create('ai_imported_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('ai_imported_conversations')
                ->cascadeOnDelete();
            $table->string('role');
            $table->longText('content');
            $table->unsignedInteger('sequence');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'sequence']);
        });

        Schema::create('ai_documents', function (Blueprint $table) {
            $table->id();
            $table->string('corpus');
            $table->string('kind');
            $table->string('title', 2048);
            $table->foreignId('imported_conversation_id')
                ->nullable()
                ->constrained('ai_imported_conversations')
                ->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('corpus');
        });

        Schema::create('ai_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('ai_documents')
                ->cascadeOnDelete();
            $table->longText('content');
            $table->unsignedInteger('position');
            $table->unsignedInteger('token_count')->default(0);
            $table->string('embedding_model')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'position']);
        });

        Schema::create('ai_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('surface');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('surface');
        });

        Schema::create('ai_session_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('ai_sessions')
                ->cascadeOnDelete();
            $table->string('role');
            $table->longText('content');
            $table->unsignedInteger('sequence');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_session_messages');
        Schema::dropIfExists('ai_sessions');
        Schema::dropIfExists('ai_chunks');
        Schema::dropIfExists('ai_documents');
        Schema::dropIfExists('ai_imported_messages');
        Schema::dropIfExists('ai_imported_conversations');
    }
};
