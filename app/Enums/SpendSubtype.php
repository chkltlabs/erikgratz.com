<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static IncomeErikJob()
 * @method static static IncomeOther()
 * @method static static HousingRent()
 * @method static static HousingHotel()
 * @method static static HousingFree()
 * @method static static TransportFlight()
 * @method static static TransportTrain()
 * @method static static TransportCarHire()
 * @method static static TransportCarRent()
 * @method static static LivingBillPhone()
 * @method static static LivingBillHealth()
 * @method static static LivingBillStudentDebt()
 * @method static static LivingBillInsurance()
 * @method static static LivingBillCreditCardFee()
 * @method static static LivingFoodGroceries()
 * @method static static LivingFoodRestaurant()
 * @method static static LivingFoodMichelin()
 * @method static static CatsGeneral()
 * @method static static CatsOther()
 * @method static static Experience()
 * @method static static ExperienceOther()
 * @method static static ExperienceDiving()
 * @method static static SubscriptionMedia()
 * @method static static SubscriptionTech()
 * @method static static SubscriptionCreative()
 * @method static static SubscriptionBusiness()
 * @method static static Other()
 */
final class SpendSubtype extends Enum
{
    const IncomeErikJob = 'income_erik_job';

    const IncomeOther = 'income_other';

    const HousingRent = 'housing_rent';

    const HousingHotel = 'housing_hotel';

    const HousingFree = 'housing_free';

    const TransportFerry = 'transport_ferry';
    const TransportFlight = 'transport_flight';

    const TransportTrain = 'transport_train';

    const TransportCarHire = 'transport_car_hire';

    const TransportCarRent = 'transport_car_rent';

    const LivingBillPhone = 'living_bill_phone';

    const LivingBillHealth = 'living_bill_health';

    const LivingBillStudentDebt = 'living_bill_student_debt';

    const LivingBillInsurance = 'living_bill_insurance';

    const LivingBillCreditCardFee = 'living_bill_credit_card_fee';

    const LivingFoodGroceries = 'living_food_groceries';

    const LivingFoodRestaurant = 'living_food_restaurant';

    const LivingFoodMichelin = 'living_food_michelin';

    const CatsGeneral = 'cats_general';

    const CatsOther = 'cats_other';

    const Experience = 'experience';

    const ExperienceOther = 'experience_other';

    const ExperienceDiving = 'experience_diving';

    const SubscriptionMedia = 'subscription_media';

    const SubscriptionTech = 'subscription_tech';

    const SubscriptionCreative = 'subscription_creative';

    const SubscriptionBusiness = 'subscription_business';

    const Other = 'other';

    public static function getFilteredSet(SpendType|string|null $filterFor)
    {
        if (is_null($filterFor)) {
            return self::asSelectArray();
        }
        if ($filterFor instanceof SpendType) {
            $filterFor = $filterFor->value();
        }

        return array_filter(
            self::asSelectArray(),
            fn ($val, $key) => str_contains($key, $filterFor),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
