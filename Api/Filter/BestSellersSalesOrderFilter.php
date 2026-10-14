<?php

declare(strict_types=1);

namespace BestSellers\Api\Filter;

use ApiPlatform\Metadata\Operation;
use BestSellers\Service\BestSellersStatsService;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Api\Bridge\Propel\Filter\AbstractFilter;
use Thelia\Api\Bridge\Propel\Filter\OrderFilter;
use Thelia\Model\Map\ProductTableMap;

/**
 * Orders the product collection by the quantity sold over the period configured in the module.
 *
 * The quantities come from the module's aggregate query (one GROUP BY over the order lines, cached), turned into a
 * single CASE expression: the listing query gets no join and no query per product. Products never sold rank last.
 * Added to the front collection by BestSellersSalesOrderOperationMutator.
 */
final class BestSellersSalesOrderFilter extends AbstractFilter
{
    public const PARAMETER = 'best_sellers_sales_order';

    private const SOLD_QUANTITY_COLUMN = 'best_sellers_sold_quantity';

    public function __construct(private readonly BestSellersStatsService $statsService)
    {
        parent::__construct();
    }

    protected function filterProperty(string $property, $value, ModelCriteria $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (self::PARAMETER !== $property || !\is_scalar($value)) {
            return;
        }

        $direction = strtoupper((string) $value);

        if (!\in_array($direction, [OrderFilter::DIRECTION_ASC, OrderFilter::DIRECTION_DESC], true)) {
            return;
        }

        $period = $this->statsService->resolvePeriod();
        $soldQuantities = $this->statsService->compute($period['start'], $period['end'])['byProduct'];

        if ([] === $soldQuantities) {
            return;
        }

        $cases = '';

        foreach ($soldQuantities as $productId => $sales) {
            $cases .= \sprintf('WHEN %d THEN %F ', $productId, $sales['quantity']);
        }

        // Ordered through an alias: Propel reads a dotted ORDER BY expression as table.column.
        $query->withColumn('CASE '.ProductTableMap::COL_ID.' '.$cases.'ELSE 0 END', self::SOLD_QUANTITY_COLUMN);

        if (OrderFilter::DIRECTION_ASC === $direction) {
            $query->addAscendingOrderByColumn(self::SOLD_QUANTITY_COLUMN);

            return;
        }

        $query->addDescendingOrderByColumn(self::SOLD_QUANTITY_COLUMN);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PARAMETER => [
                'property' => self::PARAMETER,
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => [
                        strtolower(OrderFilter::DIRECTION_ASC),
                        strtolower(OrderFilter::DIRECTION_DESC),
                    ],
                ],
            ],
        ];
    }
}
