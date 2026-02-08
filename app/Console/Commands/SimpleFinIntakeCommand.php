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
    protected $signature = 'app:simple-fin-intake {user_id} {--start-date= : Fetch transactions newer than or equal to this date (human readable format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and intake SimpleFIN data for a user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $startDateString = $this->option('start-date');

        $user = \App\Models\User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        if (!$user->simple_fin_url) {
            $this->error("User {$user->name} does not have a SimpleFIN URL set.");
            return 1;
        }

        $startDate = null;
        if ($startDateString) {
            try {
                $startDate = \Illuminate\Support\Carbon::parse($startDateString);
            } catch (\Exception $e) {
                $this->error("Invalid date format for --start-date.");
                return 1;
            }
        }

        $this->info("Fetching and starting intake for user {$user->name}...");

        try {
            \App\Services\SimpleFin\SimpleFinIntakeService::fetchAndIntake($user, $startDate);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->info("Intake completed successfully.");

        return 0;
    }
}
