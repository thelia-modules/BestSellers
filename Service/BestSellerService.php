<?php

namespace BestSellers\Service;

use BestSellers\Api\DTO\BestSellerParams;
use BestSellers\BestSellers;
use BestSellers\EventListeners\BestSellersEvent;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\ProductQuery;

readonly class BestSellerService
{

    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function filterBestSellerProduct(BestSellerParams $bestSeller, ProductQuery $query): ProductQuery
    {
        $startDate = new \DateTime();
        $endDate = new \DateTime();

        $dateType = BestSellers::getConfigValue('date_type');
        $startDateString = BestSellers::getConfigValue('start_date');
        $endDateString = BestSellers::getConfigValue('end_date');

        if ($bestSeller->getEndDate() !== null) {
            $startDateString = $bestSeller->getStartDate();
        }
        if ($bestSeller->getStartDate() !== null) {
            $endDateString = $bestSeller->getEndDate();
        }

        switch ($dateType) {
            case BestSellers::FIXED_DATE:
                $dates = $this->setFixedDate($startDateString, $endDateString);
                $startDate = $dates['start_date'];
                $endDate = $dates['end_date'];
                break;
            case BestSellers::DATE_RANGE:
                $startDate = $this->setDateRange(BestSellers::getConfigValue('date_range'));
                break;
        }

        $event = new BestSellersEvent($startDate, $endDate);

        $this->dispatcher->dispatch(
            $event,
            BestSellers::GET_BEST_SELLING_PRODUCTS
        );

        $caseClause = $caseSalesClause = '';

        $productData = $event->getBestSellingProductsData();

        array_walk($productData, function ($item) use (
            &$caseClause,
            &$caseSalesClause
        ): void {
            $caseClause .= sprintf(
                'WHEN %d THEN %F ',
                $item['product_id'],
                $item['total_quantity']
            );
            $caseSalesClause .= sprintf(
                'WHEN %d THEN %F ',
                $item['product_id'],
                $item['total_sales']
            );
        });

        if (!empty($caseClause)) {
            $query
                ->withColumn(
                    'CASE ' .
                    ProductTableMap::ID .
                    ' ' .
                    $caseClause .
                    ' ELSE 0 END',
                    'sold_quantity'
                )
                ->withColumn(
                    'CASE ' .
                    ProductTableMap::ID .
                    ' ' .
                    $caseSalesClause .
                    ' ELSE 0 END',
                    'sold_amount'
                );

            if (true === $bestSeller->getOnlySoldProducts()) {
                $query->where('(CASE ' . ProductTableMap::ID . ' ' . $caseClause . ' ELSE 0 END) > 0');
            }
        } else {
            $query
                ->withColumn('(0)', 'sold_quantity')
                ->withColumn('(0)', 'sold_amount');
        }

        if ($event->getTotalSales() !== 0) {
            $query->withColumn(
                '(select 100 * sold_amount / ' . $event->getTotalSales() . ')',
                'sale_ratio'
            );
        } else {
            $query->withColumn('(0)', 'sale_ratio');
        }

        $customOrder = BestSellers::getConfigValue('order_by');

        if ($customOrder) {
            if ($customOrder === BestSellers::ORDER_BY_SALES_REVENUE) {
                $query->orderBy('sale_ratio', Criteria::DESC);
            }
            if ($customOrder === BestSellers::ORDER_BY_NUMBER_OF_SALES) {
                $query->orderBy('sold_quantity', Criteria::DESC);
            }

            return $query;
        }

        foreach ($bestSeller->getOrders() as $order) {
            switch ($order) {
                case 'sold_count':
                    $query->orderBy('sold_quantity');
                    break;
                case 'sold_count_reverse':
                    $query->orderBy('sold_quantity', Criteria::DESC);
                    break;
                case 'sold_amount':
                    $query->orderBy('sold_amount');
                    break;
                case 'sold_amount_reverse':
                    $query->orderBy('sold_amount', Criteria::DESC);
                    break;
                case 'sale_ratio':
                    $query->orderBy('sale_ratio');
                    break;
                case 'sale_ratio_reverse':
                    $query->orderBy('sale_ratio', Criteria::DESC);
                    break;
            }
        }

        return $query;
    }

    public function filterPurchasedWith(
        ProductQuery $productQuery,
        array $orders,
        string $productRef,
    ): ProductQuery
    {
        $productQuery
            ->addJoin(ProductTableMap::COL_REF, OrderProductTableMap::COL_PRODUCT_REF, Criteria::INNER_JOIN)
            ->add(OrderProductTableMap::COL_PRODUCT_REF, $productRef, Criteria::NOT_EQUAL)
            ->where(OrderProductTableMap::COL_ORDER_ID . ' IN (
                SELECT ' . OrderProductTableMap::COL_ORDER_ID . '
                FROM ' . OrderProductTableMap::TABLE_NAME . '
                WHERE ' . OrderProductTableMap::COL_PRODUCT_REF . ' = ?)', $productRef)
            ->filterByVisible(true)
            ->withColumn('COUNT(' . OrderProductTableMap::COL_PRODUCT_REF . ')', 'sold_count')
            ->groupBy(ProductTableMap::COL_REF)
        ;

        foreach ($orders as $order) {
            switch ($order) {
                case "sold_count":
                    $productQuery->orderBy('sold_count');
                    break;
                case "sold_count_reverse":
                    $productQuery->orderBy('sold_count', Criteria::DESC);
                    break;
            }
        }
        return $productQuery;
    }

    private function setFixedDate($startDateString, $endDateString): array
    {
        $startDate = new \DateTime($startDateString);
        $startDate->setTime(0, 0, 0);
        $endDate = new \DateTime($endDateString);
        $endDate->setTime(23, 59, 59);

        return ['start_date' => $startDate, 'end_date' => $endDate];
    }

    private function setDateRange($dateRange): \DateTime
    {
        return match ($dateRange) {
            BestSellers::LAST_15_DAYS => new \DateTime('-15 days'),
            BestSellers::LAST_30_DAYS => new \DateTime('-30 days'),
            BestSellers::LAST_6_MONTHS => new \DateTime('-6 months'),
            BestSellers::LAST_3_MONTHS => new \DateTime('-3 months'),
            BestSellers::LAST_YEAR => new \DateTime('first day of January last year'),
            default => new \DateTime('first day of January'),
        };
    }
}
