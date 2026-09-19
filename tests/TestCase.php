<?php

namespace Tests;

use App\Database\PgsqlMigrationHooks;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;
use JMac\Testing\Traits\AdditionalAssertions;

abstract class TestCase extends BaseTestCase
{
    use AdditionalAssertions, CreatesApplication, RefreshDatabase;

    /**
     * @var list<string>
     */
    protected $connectionsToTransact = ['mysql', 'pgsql'];

    protected static ?string $pgsqlTestDatabase = null;

    protected function beforeRefreshingDatabase()
    {
        $this->configurePgsqlTestDatabase();

        if (! RefreshDatabaseState::$migrated) {
            PgsqlMigrationHooks::wipePgsqlSchema();
        }
    }

    protected function configurePgsqlTestDatabase(): void
    {
        if (self::$pgsqlTestDatabase !== null) {
            $this->switchPgsqlDatabase(self::$pgsqlTestDatabase);

            return;
        }

        $base = (string) config('database.connections.pgsql.database');
        $token = ParallelTesting::token();
        $database = $token !== false && $token !== ''
            ? $base.'_test_'.$token
            : $base;

        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new \InvalidArgumentException("Invalid pgsql test database name [{$database}].");
        }

        $this->ensurePgsqlDatabaseExists($database);
        $this->switchPgsqlDatabase($database);
        self::$pgsqlTestDatabase = $database;
    }

    protected function switchPgsqlDatabase(string $database): void
    {
        config(['database.connections.pgsql.database' => $database]);
        DB::purge('pgsql');
    }

    protected function ensurePgsqlDatabaseExists(string $database): void
    {
        $admin = config('database.connections.pgsql');
        $admin['database'] = 'postgres';
        config(['database.connections.pgsql_admin' => $admin]);
        DB::purge('pgsql_admin');

        try {
            Schema::connection('pgsql_admin')->createDatabase($database);
        } catch (QueryException $e) {
            if (! str_contains(strtolower($e->getMessage()), 'already exists')) {
                throw $e;
            }
        } finally {
            DB::purge('pgsql_admin');
        }
    }
}
