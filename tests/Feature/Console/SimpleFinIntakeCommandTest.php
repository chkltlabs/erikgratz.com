<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SimpleFinIntakeCommand;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimpleFinIntakeCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fails_with_invalid_start_date_option(): void
    {
        $this->artisan('app:simple-fin-intake', [
            '--start-date' => 'not-a-date',
        ])->expectsOutput('Invalid date format for --start-date.')
          ->assertExitCode(1);
    }

    public function test_exits_cleanly_when_no_users_have_simplefin_url(): void
    {
        $this->artisan('app:simple-fin-intake')
            ->expectsOutput('No users with SimpleFIN URL found.')
            ->assertExitCode(0);
    }

    public function test_errors_when_specific_user_not_found(): void
    {
        $this->artisan('app:simple-fin-intake', [
            'user_id' => 999999,
        ])->expectsOutput('User with ID 999999 not found.')
          ->assertExitCode(1);
    }

    public function test_processes_specific_user_successfully(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice',
            'simple_fin_url' => 'https://example.test/token',
        ]);

        Http::fake([
            'https://example.test/token/accounts*' => Http::response([
                'accounts' => [],
            ], 200),
        ]);

        $this->artisan('app:simple-fin-intake', [
            'user_id' => $user->id,
            '--start-date' => now()->toDateString(),
        ])->expectsOutput(' ')
          ->expectsOutput('Fetching and starting intake for user Alice...')
          ->expectsOutput('  Data received successfully from SimpleFIN (non-pending).')
          ->expectsOutput('Intake completed successfully for Alice.')
          ->assertExitCode(0);
    }

    public function test_handles_exception_from_service_gracefully(): void
    {
        $user = User::factory()->create([
            'name' => 'Bob',
            'simple_fin_url' => 'https://example.test/token',
        ]);

        Http::fake([
            'https://example.test/token/accounts*' => Http::response('err', 500),
        ]);

        $this->artisan('app:simple-fin-intake', [
            'user_id' => $user->id,
        ])->expectsOutput(' ')
          ->expectsOutput('Fetching and starting intake for user Bob...')
          ->expectsOutput('Failed for Bob: Failed to fetch data from SimpleFIN: err')
          ->assertExitCode(0);
    }

    public function test_processes_all_users_with_simplefin_url_when_no_argument_given(): void
    {
        $a = User::factory()->create(['name' => 'A', 'simple_fin_url' => 'x://a']);
        $b = User::factory()->create(['name' => 'B', 'simple_fin_url' => 'x://b']);
        // user without url is ignored
        User::factory()->create(['name' => 'C', 'simple_fin_url' => null]);

        Http::fake([
            'x://a/accounts*' => Http::response(['accounts' => []], 200),
            'x://b/accounts*' => Http::response(['accounts' => []], 200),
        ]);

        $this->artisan('app:simple-fin-intake')
            ->expectsOutput(' ')
            ->expectsOutput('Fetching and starting intake for user A...')
            ->expectsOutput(' ')
            ->expectsOutput('Fetching and starting intake for user B...')
            ->assertExitCode(0);
    }

}
