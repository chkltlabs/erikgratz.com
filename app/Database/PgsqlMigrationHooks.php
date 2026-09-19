<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

class PgsqlMigrationHooks
{
    public static function register(): void
    {
        Event::listen(CommandStarting::class, self::wipeWhenDefaultSchemaIsReset(...));
    }

    public static function wipeWhenDefaultSchemaIsReset(CommandStarting $event): void
    {
        if (! in_array($event->command, ['db:wipe', 'migrate:fresh'], true)) {
            return;
        }

        if ($event->input->getOption('database') === 'pgsql') {
            return;
        }

        self::wipePgsqlSchema();
    }

    public static function wipePgsqlSchema(): void
    {
        $connection = DB::connection('pgsql');
        $schema = Schema::connection('pgsql');

        $schema->dropIfExists('ai_chunk_embeddings');
        $schema->dropAllTables();
        $connection->disconnect();
    }
}
