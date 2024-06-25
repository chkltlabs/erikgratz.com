<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(UserSeeder::class);
        $this->call(BlogPostSeeder::class);
        $this->callSilent(ContactSeeder::class);
        Activity::factory(4)->create();
        // $this->call(FriendSeeder::class);
        // $this->call(FriendPlaceRecommendationSeeder::class);
    }
}
