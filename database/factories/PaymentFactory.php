<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Payment;
use App\Models\PeriodicSpend;
use App\Models\Spend;
use App\Models\V2\Question as NewQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => rand(10, 4000),
            'is_paid' => $this->faker->boolean(),
            'paid_on' => Carbon::now(),
            'spend_type' => self::spendType(),
            'spend_id' => function ($attrs) {
                return Relation::getMorphedModel($attrs['spend_type'])::factory();
            },
//            'spend_id' => Spend::factory(),
//            'spend_type' => collect([PeriodicSpend::class, Spend::class])->random()::getMorphClass(),
            'card_id' => Card::factory(),
        ];
    }

    public static function spendType()
    {
        return collect([
            getMorphAliasForClass(PeriodicSpend::class),
            getMorphAliasForClass(Spend::class),
        ])->random();
    }
}
