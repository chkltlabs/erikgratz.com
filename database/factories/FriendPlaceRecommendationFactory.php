<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\FriendPlaceRecommendation;

class FriendPlaceRecommendationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FriendPlaceRecommendation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'friend_id' => \App\Models\Friend::factory(),
            'review_title' => $this->faker->word(),
            'review_body' => $this->faker->text(),
            'region' => $this->faker->word(),
        ];
    }
}
