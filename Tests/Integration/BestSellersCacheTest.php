<?php

declare(strict_types=1);

namespace BestSellers\Tests\Integration;

use BestSellers\BestSellers;
use BestSellers\EventListeners\BestSellersEvent;
use BestSellers\EventListeners\EventManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Thelia\Test\IntegrationTestCase;

final class BestSellersCacheTest extends IntegrationTestCase
{
    public function testChangingTheCountedStatusesDoesNotServeTheFiguresOfTheFormerOnes(): void
    {
        $cache = new ArrayAdapter();
        $listener = new EventManager($cache);
        $start = new \DateTime('2026-01-01');
        $end = new \DateTime('2026-01-31');

        BestSellers::setConfigValue('order_types', '2,3,4');
        $listener->calculateBestSellers(new BestSellersEvent($start, $end));

        BestSellers::setConfigValue('order_types', '5');
        $listener->calculateBestSellers(new BestSellersEvent($start, $end));

        self::assertCount(2, $cache->getValues(), 'One computation per list of statuses over the same period.');
    }
}
