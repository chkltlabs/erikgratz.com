<?php

use App\Enums\PointsProgram;

return [
    'agents' => [
        'provider' => env('TRAVEL_WALLET_AI_PROVIDER', env('CHATBOT_PROVIDER', 'gemini')),
        'model' => env('TRAVEL_WALLET_AI_MODEL', env('CHATBOT_MODEL')),
        'timeout' => (int) env('TRAVEL_WALLET_AI_TIMEOUT', env('CHATBOT_TIMEOUT', 60)),
    ],

    'default_earning_rate' => 1.0,

    'cents_per_point' => [
        PointsProgram::ChaseUltimateRewards => 2.0,
        PointsProgram::CapitalOneMiles => 1.7,
        PointsProgram::Avios => 1.5,
        PointsProgram::Aeroplan => 1.5,
        PointsProgram::CitiThankYou => 1.6,
        PointsProgram::AmExMemberRewards => 2.0,
        PointsProgram::Bilt => 1.8,
        PointsProgram::Unknown => 1.0,
    ],

    'scoring' => [
        'sub_boost' => 25,
        'sub_urgency_per_day' => 0.25,
        'benefit_value_weight' => 1.0,
        'status_boost' => 120,
        'transfer_bonus_weight' => 1.0,
    ],

    'avios_program_codes' => [
        'ba',
        'qatar',
        'aerlingus',
        'iberia',
        'finnair',
    ],

    'promo' => [
        'doctor_of_credit_rss' => env(
            'TRAVEL_WALLET_DOC_RSS',
            'https://www.doctorofcredit.com/feed/',
        ),
    ],
];
