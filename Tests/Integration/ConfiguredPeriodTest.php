<?php

declare(strict_types=1);

namespace BestSellers\Tests\Integration;

use BestSellers\BestSellers;
use BestSellers\Service\ConfiguredPeriod;
use Thelia\Model\ModuleConfigQuery;
use Thelia\Test\IntegrationTestCase;

final class ConfiguredPeriodTest extends IntegrationTestCase
{
    public function testAFreshInstallCountsEverySale(): void
    {
        ModuleConfigQuery::create()
            ->filterByModuleId(BestSellers::getModuleId())
            ->filterByName(['date_type', 'start_date', 'end_date', 'date_range'])
            ->delete();

        $period = (new ConfiguredPeriod())->resolve();

        self::assertSame('1970-01-01', $period['start']->format('Y-m-d'));
        self::assertSame((new \DateTime())->format('Y-m-d'), $period['end']->format('Y-m-d'));
    }

    public function testFixedDatesLeftEmptyCountEverySaleInsteadOfTodayOnly(): void
    {
        BestSellers::setConfigValue('date_type', (string) BestSellers::FIXED_DATE);
        BestSellers::setConfigValue('start_date', '');
        BestSellers::setConfigValue('end_date', '');

        $period = (new ConfiguredPeriod())->resolve();

        self::assertSame('1970-01-01 00:00:00', $period['start']->format('Y-m-d H:i:s'));
        self::assertSame((new \DateTime())->format('Y-m-d').' 23:59:59', $period['end']->format('Y-m-d H:i:s'));
    }

    public function testFixedDatesBoundTheWholeDays(): void
    {
        BestSellers::setConfigValue('date_type', (string) BestSellers::FIXED_DATE);
        BestSellers::setConfigValue('start_date', '2026-03-01');
        BestSellers::setConfigValue('end_date', '2026-03-31');

        $period = (new ConfiguredPeriod())->resolve();

        self::assertSame('2026-03-01 00:00:00', $period['start']->format('Y-m-d H:i:s'));
        self::assertSame('2026-03-31 23:59:59', $period['end']->format('Y-m-d H:i:s'));
    }

    public function testADateRangeEndsToday(): void
    {
        BestSellers::setConfigValue('date_type', (string) BestSellers::DATE_RANGE);
        BestSellers::setConfigValue('date_range', BestSellers::LAST_30_DAYS);

        $period = (new ConfiguredPeriod())->resolve();

        self::assertSame((new \DateTime('-30 days'))->format('Y-m-d'), $period['start']->format('Y-m-d'));
        self::assertSame((new \DateTime())->format('Y-m-d'), $period['end']->format('Y-m-d'));
    }

    public function testTheLastYearIsTheLastTwelveMonths(): void
    {
        BestSellers::setConfigValue('date_type', (string) BestSellers::DATE_RANGE);
        BestSellers::setConfigValue('date_range', BestSellers::LAST_YEAR);

        $period = (new ConfiguredPeriod())->resolve();

        self::assertSame((new \DateTime('-1 year'))->format('Y-m-d'), $period['start']->format('Y-m-d'));
        self::assertSame((new \DateTime())->format('Y-m-d'), $period['end']->format('Y-m-d'));
    }

    public function testAllSalesSavedAsSuchCountsEverySale(): void
    {
        BestSellers::setConfigValue('date_type', (string) BestSellers::ALL_SALES);
        BestSellers::setConfigValue('start_date', '2026-03-01');
        BestSellers::setConfigValue('date_range', BestSellers::LAST_30_DAYS);

        $period = (new ConfiguredPeriod())->resolve();

        self::assertSame('1970-01-01', $period['start']->format('Y-m-d'));
        self::assertSame((new \DateTime())->format('Y-m-d'), $period['end']->format('Y-m-d'));
    }
}
