<?php

namespace Database\Factories;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogPostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BlogPost::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->bs,
            'user_id' => 1,
            'posted' => now(),
            'edited' => now(),
            'subtitle' => $this->faker->bs,
            'body' => $this->faker->bs,
            'is_public' => $this->faker->boolean,
            'tags' => [$this->faker->word, $this->faker->word, $this->faker->word, $this->faker->word],
        ];
    }
}
