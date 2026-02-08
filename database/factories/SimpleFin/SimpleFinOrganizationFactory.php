<?php

namespace Database\Factories\SimpleFin;

use App\Models\SimpleFin\SimpleFinOrganization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpleFinOrganizationFactory extends Factory
{
    protected $model = SimpleFinOrganization::class;

    public function definition()
    {
        return [
            'id' => 'ORG-' . $this->faker->uuid,
            'name' => $this->faker->company,
            'domain' => $this->faker->domainName,
            'url' => $this->faker->url,
            'sfin_url' => $this->faker->url,
        ];
    }
}
