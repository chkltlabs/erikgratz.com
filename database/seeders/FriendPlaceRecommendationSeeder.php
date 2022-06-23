<?php

namespace Database\Seeders;

use App\Models\Friend;
use App\Models\FriendPlaceRecommendation;
use Faker\Factory;
use Illuminate\Database\Seeder;

class FriendPlaceRecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FriendPlaceRecommendation::truncate();
        $faker = Factory::create();
        foreach (Friend::cursor() as $friend) {
            for ($i = 0; $i < 5; $i++) {
                FriendPlaceRecommendation::create([
                    'friend_id' => $friend->id,
                    'region' => [
                        'latitude' => $faker->latitude(
                        $friend->region['latitude'] - (mt_rand(0, 20) / 1000),
                        $friend->region['latitude'] + (mt_rand(0, 20) / 1000)),
                        'longitude' => $faker->longitude(
                            $friend->region['longitude'] - (mt_rand(0, 20) / 1000),
                            $friend->region['longitude'] + (mt_rand(0, 20) / 1000)),
                    ],
                    'review_title' => $faker->sentence,
                    'review_body' => $faker->text,
                ]);
            }
        }
    }
}
