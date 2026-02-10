<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Laravel\Prompts\Output\ConsoleOutput;

class SimpleFinDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::where('id', 1)->update(['simple_fin_url' => env('SIMPLE_FIN_URL')]);

        Artisan::call('app:simple-fin-intake', ['--start-date' => '2025-01-01'], new ConsoleOutput());
    }
}
