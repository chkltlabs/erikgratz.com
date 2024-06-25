<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SpendSubtype extends Enum
{
    const IncomeErikJob = 'income_erik_job';
    const IncomeOther = 'income_other';
    const HousingRent = 'housing_rent';
    const HousingHotel = 'housing_hotel';
    const HousingFree = 'housing_free';

    const TransportFlight = 'transport_flight';
    const TransportTrain = 'transport_train';
    const TransportCarHire = 'transport_car_hire';
    const TransportCarRent = 'transport_car_rent';

    const LivingFoodGroceries = 'living_food_groceries';
    const LivingFoodRestaurant = 'living_food_restaurant';
    const LivingFoodMichelin = 'living_food_michelin';

    const CatsGeneral = 'cats_general';
    const CatsOther = 'cats_other';

    const Experience = 'experience';

    const Other = 'other';

    public static function getFilteredSet(SpendType|string|null $filterFor)
    {
        if (is_null($filterFor)){
            return self::asSelectArray();
        }
        if ($filterFor instanceOf SpendType)
        {
            $filterFor = $filterFor->value();
        }
        return array_filter(
            self::asSelectArray(),
            fn ($val, $key) => str_contains($key, $filterFor),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
