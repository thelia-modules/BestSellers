<?php

declare(strict_types=1);

namespace BestSellers\Tests\Integration;

use BestSellers\BestSellers;
use BestSellers\Service\ConfigurationWriter;
use Thelia\Test\IntegrationTestCase;

final class ConfigurationWriterTest extends IntegrationTestCase
{
    public function testFixedDatesLeftEmptyClearTheSavedOnes(): void
    {
        $writer = new ConfigurationWriter();
        $writer->save('2,3,4', BestSellers::FIXED_DATE, new \DateTime('2026-03-01'), new \DateTime('2026-03-31'), BestSellers::LAST_30_DAYS, null);

        $writer->save('2,3,4', BestSellers::FIXED_DATE, null, null, BestSellers::LAST_30_DAYS, null);

        self::assertSame('', (string) BestSellers::getConfigValue('start_date'));
        self::assertSame('', (string) BestSellers::getConfigValue('end_date'));
    }

    public function testEverySentValueIsSaved(): void
    {
        (new ConfigurationWriter())->save('4,5', BestSellers::DATE_RANGE, new \DateTime('2026-03-01'), null, BestSellers::LAST_6_MONTHS, BestSellers::ORDER_BY_SALES_REVENUE);

        self::assertSame('4,5', BestSellers::getConfigValue('order_types'));
        self::assertSame((string) BestSellers::DATE_RANGE, BestSellers::getConfigValue('date_type'));
        self::assertSame('2026-03-01', BestSellers::getConfigValue('start_date'));
        self::assertSame(BestSellers::LAST_6_MONTHS, BestSellers::getConfigValue('date_range'));
        self::assertSame(BestSellers::ORDER_BY_SALES_REVENUE, BestSellers::getConfigValue('order_by'));
    }
}
