<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_sessions', function (Blueprint $table) {
            $table->string('public_handle_hash', 64)->nullable()->unique()->after('ip_hash');
            $table->timestamp('expires_at')->nullable()->after('metadata');
        });

        Schema::table('ai_imported_conversations', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('external_id');
            $table->unsignedInteger('revision')->default(1)->after('import_key');
        });

        foreach (DB::table('ai_imported_conversations')->orderBy('id')->get() as $row) {
            $key = $row->external_id
                ? 'external:'.$row->source.':'.$row->external_id
                : 'legacy:'.$row->id;
            DB::table('ai_imported_conversations')->where('id', $row->id)->update(['import_key' => $key]);
        }

        Schema::table('ai_imported_conversations', function (Blueprint $table) {
            $table->unique('import_key');
        });

        Schema::table('ai_session_messages', function (Blueprint $table) {
            $table->unique(['session_id', 'sequence'], 'ai_session_messages_session_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ai_session_messages', function (Blueprint $table) {
            $table->dropUnique('ai_session_messages_session_sequence_unique');
        });

        Schema::table('ai_imported_conversations', function (Blueprint $table) {
            $table->dropUnique(['import_key']);
            $table->dropColumn(['import_key', 'revision']);
        });

        Schema::table('ai_sessions', function (Blueprint $table) {
            $table->dropUnique(['public_handle_hash']);
            $table->dropColumn(['public_handle_hash', 'expires_at']);
        });
    }
};
