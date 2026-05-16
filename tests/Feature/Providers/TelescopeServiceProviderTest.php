<?php

namespace Tests\Feature\Providers;

use App\Models\User;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\IncomingExceptionEntry;
use Laravel\Telescope\Telescope;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\CreatesApplication;

class TelescopeServiceProviderTest extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        $this->resetTelescopeState();

        parent::tearDown();
    }

    #[Test]
    public function register_enables_telescope_dark_theme(): void
    {
        $this->registerTelescopeProvider();

        $this->assertTrue(Telescope::$useDarkTheme);
    }

    #[Test]
    public function hide_sensitive_request_details_in_non_local_environments(): void
    {
        $this->registerTelescopeProvider('production');

        $this->assertContains('_token', Telescope::$hiddenRequestParameters);
        $this->assertContains('cookie', Telescope::$hiddenRequestHeaders);
        $this->assertContains('x-csrf-token', Telescope::$hiddenRequestHeaders);
        $this->assertContains('x-xsrf-token', Telescope::$hiddenRequestHeaders);
    }

    #[Test]
    public function hide_sensitive_request_details_skipped_in_local_environment(): void
    {
        $this->registerTelescopeProvider('local');

        $this->assertNotContains('_token', Telescope::$hiddenRequestParameters);
        $this->assertNotContains('cookie', Telescope::$hiddenRequestHeaders);
    }

    #[Test]
    public function local_environment_records_all_entries(): void
    {
        $this->registerTelescopeProvider('local');

        $entry = IncomingEntry::make([])->type(EntryType::REQUEST);

        $this->assertTrue($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function production_environment_rejects_successful_requests(): void
    {
        $this->registerTelescopeProvider('production');

        $entry = IncomingEntry::make(['response_status' => 200])->type(EntryType::REQUEST);

        $this->assertFalse($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function production_environment_records_failed_requests(): void
    {
        $this->registerTelescopeProvider('production');

        $entry = IncomingEntry::make(['response_status' => 500])->type(EntryType::REQUEST);

        $this->assertTrue($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function production_environment_records_failed_jobs(): void
    {
        $this->registerTelescopeProvider('production');

        $entry = IncomingEntry::make(['status' => 'failed'])->type(EntryType::JOB);

        $this->assertTrue($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function production_environment_records_scheduled_tasks(): void
    {
        $this->registerTelescopeProvider('production');

        $entry = IncomingEntry::make([])->type(EntryType::SCHEDULED_TASK);

        $this->assertTrue($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function production_environment_records_reportable_exceptions(): void
    {
        $handler = $this->createMock(ExceptionHandler::class);
        $handler->method('shouldReport')->willReturn(true);
        $this->instance(ExceptionHandler::class, $handler);

        $this->registerTelescopeProvider('production');

        $entry = new IncomingExceptionEntry(
            new \RuntimeException('report me'),
            ['file' => '/tmp/example.php', 'line' => 1],
        );

        $this->assertTrue($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function production_environment_records_entries_with_monitored_tags(): void
    {
        $repository = $this->createMock(EntriesRepository::class);
        $repository->method('isMonitoring')->with(['monitored'])->willReturn(true);
        $this->instance(EntriesRepository::class, $repository);

        $this->registerTelescopeProvider('production');

        $entry = IncomingEntry::make([])->type(EntryType::QUERY)->tags(['monitored']);

        $this->assertTrue($this->shouldRecordEntry($entry));
    }

    #[Test]
    public function view_telescope_gate_allows_owner_email(): void
    {
        $this->registerTelescopeProvider('production');

        $user = User::factory()->make(['email' => 'erik@erikgratz.com']);

        $this->assertTrue(Gate::forUser($user)->allows('viewTelescope'));
    }

    #[Test]
    public function view_telescope_gate_denies_other_users(): void
    {
        $this->registerTelescopeProvider('production');

        $user = User::factory()->make(['email' => 'other@example.com']);

        $this->assertFalse(Gate::forUser($user)->allows('viewTelescope'));
    }

    private function registerTelescopeProvider(string $environment = 'testing'): void
    {
        $this->setApplicationEnvironment($environment);

        $this->resetTelescopeState();

        $provider = new TelescopeServiceProvider($this->app);
        $provider->register();
        $provider->boot();
    }

    private function setApplicationEnvironment(string $environment): void
    {
        config(['app.env' => $environment]);
        $this->app['env'] = $environment;
    }

    private function resetTelescopeState(): void
    {
        Telescope::$filterUsing = [];
        Telescope::$useDarkTheme = false;
        Telescope::$hiddenRequestHeaders = [
            'authorization',
            'php-auth-pw',
        ];
        Telescope::$hiddenRequestParameters = [
            'password',
            'password_confirmation',
        ];
    }

    private function shouldRecordEntry(IncomingEntry $entry): bool
    {
        $this->assertNotEmpty(Telescope::$filterUsing);

        foreach (Telescope::$filterUsing as $filter) {
            if (! $filter($entry)) {
                return false;
            }
        }

        return true;
    }
}
