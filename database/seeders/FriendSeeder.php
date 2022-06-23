<?php

namespace Database\Seeders;

use App\Models\Friend;
use Illuminate\Database\Seeder;

class FriendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Friend::truncate();

        Friend::create([
            'name' => 'John',
            'region' => [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
            ],
            'color' => '#08189c',
        ]);
        Friend::create([
            'name' => 'Jacob',
            'region' => [
                'latitude' => 33.7490,
                'longitude' => -84.3880,
            ],
            'color' => '#c7dba8',
        ]);
        Friend::create([
            'name' => 'Jingleheimerschmidt',
            'region' => [
                'latitude' => 32.7157,
                'longitude' => -117.1611,
            ],
            'color' => '#9d384b',
        ]);
    }
}
