<?php

declare(strict_types=1);

namespace BestSellers\Tests\Unit\Api;

use BestSellers\Api\Filter\BestSellersSalesOrderFilter;
use BestSellers\Service\BestSellersStatsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Api\Resource\Product;

/**
 * The sales order is one CASE over the quantities of the module's aggregate: no join, no query per product.
 */
final class BestSellersSalesOrderFilterTest extends TestCase
{
    private const SOLD = [
        13 => ['quantity' => 21.0, 'amount' => 420.0],
        4 => ['quantity' => 3.0, 'amount' => 60.0],
    ];

    private const EXPECTED_CASE = 'CASE product.id WHEN 13 THEN 21.000000 WHEN 4 THEN 3.000000 ELSE 0 END';

    public function testTheBestSellersComeFirst(): void
    {
        $query = $this->createMock(ModelCriteria::class);
        $query->expects(self::once())->method('withColumn')->with(self::EXPECTED_CASE, 'best_sellers_sold_quantity')->willReturnSelf();
        $query->expects(self::once())->method('addDescendingOrderByColumn')->with('best_sellers_sold_quantity')->willReturnSelf();
        $query->expects(self::never())->method('addAscendingOrderByColumn');

        $this->filter(self::SOLD)->apply($query, Product::class, null, ['filters' => ['best_sellers_sales_order' => 'desc']]);
    }

    public function testTheAscendingOrderPutsTheLeastSoldFirst(): void
    {
        $query = $this->createMock(ModelCriteria::class);
        $query->expects(self::once())->method('withColumn')->with(self::EXPECTED_CASE, 'best_sellers_sold_quantity')->willReturnSelf();
        $query->expects(self::once())->method('addAscendingOrderByColumn')->with('best_sellers_sold_quantity')->willReturnSelf();

        $this->filter(self::SOLD)->apply($query, Product::class, null, ['filters' => ['best_sellers_sales_order' => 'ASC']]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function ignoredFilters(): iterable
    {
        yield 'unknown direction' => [['best_sellers_sales_order' => 'sideways']];
        yield 'not a scalar' => [['best_sellers_sales_order' => ['desc']]];
        yield 'another parameter' => [['untaxed_price_order' => 'desc']];
    }

    /**
     * @param array<string, mixed> $filters
     */
    #[DataProvider('ignoredFilters')]
    public function testAnythingElseLeavesTheQueryAlone(array $filters): void
    {
        $query = $this->createMock(ModelCriteria::class);
        $query->expects(self::never())->method('withColumn');
        $query->expects(self::never())->method('addDescendingOrderByColumn');
        $query->expects(self::never())->method('addAscendingOrderByColumn');

        $this->filter(self::SOLD)->apply($query, Product::class, null, ['filters' => $filters]);
    }

    public function testAShopWithoutSalesKeepsItsOrder(): void
    {
        $query = $this->createMock(ModelCriteria::class);
        $query->expects(self::never())->method('withColumn');
        $query->expects(self::never())->method('addDescendingOrderByColumn');

        $this->filter([])->apply($query, Product::class, null, ['filters' => ['best_sellers_sales_order' => 'desc']]);
    }

    /**
     * @param array<int, array{quantity: float, amount: float}> $sold
     */
    private function filter(array $sold): BestSellersSalesOrderFilter
    {
        $statsService = $this->createStub(BestSellersStatsService::class);
        $statsService->method('resolvePeriod')->willReturn(['start' => new \DateTime('1970-01-01'), 'end' => new \DateTime()]);
        $statsService->method('compute')->willReturn(['byProduct' => $sold, 'totalSales' => 480.0]);

        return new BestSellersSalesOrderFilter($statsService);
    }
}
