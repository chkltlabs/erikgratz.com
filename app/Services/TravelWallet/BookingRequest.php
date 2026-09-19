<?php

declare(strict_types=1);

namespace App\Services\TravelWallet;

use App\Enums\BookingCabin;
use App\Enums\BookingCategory;

class BookingRequest
{
    /**
     * @param  list<array{points_program: string, points: int, cash?: float, program_code?: string}>  $awardQuotes
     */
    public function __construct(
        public BookingCategory $category,
        public ?int $activityId = null,
        public ?string $vendor = null,
        public ?string $destination = null,
        public ?float $cashPrice = null,
        public array $awardQuotes = [],
        public ?int $loyaltyMembershipId = null,
        public BookingCabin $cabin = new BookingCabin(BookingCabin::Economy),
    ) {}
}
