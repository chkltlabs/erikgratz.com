<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PgsqlMigratesWithDefaultTest extends TestCase
{
    #[Test]
    public function default_migrate_fresh_installs_pgsql_embeddings_table(): void
    {
        $this->assertTrue(Schema::connection('pgsql')->hasTable('ai_chunk_embeddings'));
    }

    #[Test]
    public function phpunit_uses_the_dedicated_pgsql_testing_database(): void
    {
        $database = (string) config('database.connections.pgsql.database');

        $this->assertStringStartsWith('testing', $database);
        $this->assertNotSame('erikgratz_vectors', $database);
    }

    #[Test]
    public function default_migrate_fresh_can_run_twice_against_pgsql(): void
    {
        $this->artisan('migrate:fresh');

        $this->assertTrue(Schema::connection('pgsql')->hasTable('ai_chunk_embeddings'));
    }
}
