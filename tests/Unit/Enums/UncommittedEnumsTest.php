<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AiChatSource;
use App\Enums\AiCorpus;
use App\Enums\AiDocumentKind;
use App\Enums\AiImportStatus;
use App\Enums\AiMessageRole;
use App\Enums\AiSurface;
use App\Enums\BenefitAppliesTo;
use App\Enums\BenefitResetAnchor;
use App\Enums\BenefitTrackingMode;
use App\Enums\BenefitUsageSource;
use App\Enums\BenefitValueKind;
use App\Enums\BookingCabin;
use App\Enums\BookingCategory;
use App\Enums\BookingChannel;
use App\Enums\LoyaltyKind;
use App\Enums\PromoStatus;
use App\Enums\RecommendationAction;
use App\Enums\ResetPeriod;
use BenSampo\Enum\Enum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UncommittedEnumsTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string<Enum>}>
     */
    public static function enumClasses(): array
    {
        return [
            'AiChatSource' => [AiChatSource::class],
            'AiCorpus' => [AiCorpus::class],
            'AiDocumentKind' => [AiDocumentKind::class],
            'AiImportStatus' => [AiImportStatus::class],
            'AiMessageRole' => [AiMessageRole::class],
            'AiSurface' => [AiSurface::class],
            'BenefitAppliesTo' => [BenefitAppliesTo::class],
            'BenefitResetAnchor' => [BenefitResetAnchor::class],
            'BenefitTrackingMode' => [BenefitTrackingMode::class],
            'BenefitUsageSource' => [BenefitUsageSource::class],
            'BenefitValueKind' => [BenefitValueKind::class],
            'BookingCabin' => [BookingCabin::class],
            'BookingCategory' => [BookingCategory::class],
            'BookingChannel' => [BookingChannel::class],
            'LoyaltyKind' => [LoyaltyKind::class],
            'PromoStatus' => [PromoStatus::class],
            'RecommendationAction' => [RecommendationAction::class],
            'ResetPeriod' => [ResetPeriod::class],
        ];
    }

    /**
     * @param  class-string<Enum>  $enum
     */
    #[Test]
    #[DataProvider('enumClasses')]
    public function cases_have_values_descriptions_and_select_options(string $enum): void
    {
        $instances = $enum::getInstances();

        $this->assertNotEmpty($instances);

        foreach ($instances as $instance) {
            $this->assertNotSame('', (string) $instance->value);
            $this->assertNotSame('', $instance->description);
            $this->assertTrue($enum::hasValue($instance->value));
        }

        $select = $enum::asSelectArray();

        $this->assertSame(count($instances), count($select));
        $this->assertSame(array_values($enum::getValues()), array_keys($select));
    }
}
