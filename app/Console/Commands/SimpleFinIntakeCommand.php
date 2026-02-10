<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SimpleFinIntakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:simple-fin-intake {user_id?} {--start-date= : Fetch transactions newer than or equal to this date (human readable format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and intake SimpleFIN data for users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $startDateString = $this->option('start-date');

        $startDate = null;
        if ($startDateString) {
            try {
                $startDate = \Illuminate\Support\Carbon::parse($startDateString);
            } catch (\Exception $e) {
                $this->error("Invalid date format for --start-date.");
                return 1;
            }
        }

        if ($userId) {
            $user = \App\Models\User::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }

            $this->processUser($user, $startDate);
        } else {
            $users = \App\Models\User::whereNotNull('simple_fin_url')->get();

            if ($users->isEmpty()) {
                $this->info("No users with SimpleFIN URL found.");
                return 0;
            }

            foreach ($users as $user) {
                $this->processUser($user, $startDate);
            }
        }

        return 0;
    }

    /**
     * Process intake for a single user.
     *
     * @param \App\Models\User $user
     * @param \Illuminate\Support\Carbon|null $startDate
     * @return void
     */
    protected function processUser($user, $startDate)
    {
        if (!$user->simple_fin_url) {
            $this->error("User {$user->name} does not have a SimpleFIN URL set.");
            return;
        }
        $this->info(' ');
        $this->info("Fetching and starting intake for user {$user->name}...");

        try {
            $command = $this;
            \App\Services\SimpleFin\SimpleFinIntakeService::fetchAndIntake(
                $user,
                $startDate,
                function (string $message) use ($command) {
                    $command->info('  ' . $message);
                }
            );
            $this->info("Intake completed successfully for {$user->name}.");
        } catch (\Exception $e) {
            $this->error("Failed for {$user->name}: " . $e->getMessage());
        }
    }
}
