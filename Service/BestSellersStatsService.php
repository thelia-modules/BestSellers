<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BestSellers\Service;

use BestSellers\BestSellers;
use BestSellers\EventListeners\BestSellersEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\ProductQuery;

/**
 * Computes best-seller statistics by reproducing the data path of the
 * best_selling_products loop without relying on the Smarty {loop} machinery.
 */
class BestSellersStatsService
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * Resolves the configured analysis period (fixed dates or date range).
     *
     * @return array{start: \DateTime, end: \DateTime}
     */
    public function resolvePeriod(?string $startDate = null, ?string $endDate = null): array
    {
        $dateType = BestSellers::getConfigValue('date_type');

        if ($startDate !== null && $endDate !== null) {
            $start = new \DateTime($startDate);
            $start->setTime(0, 0, 0);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59);

            return ['start' => $start, 'end' => $end];
        }

        $start = new \DateTime();
        $end = new \DateTime();

        switch ($dateType) {
            case BestSellers::FIXED_DATE:
                $startString = BestSellers::getConfigValue('start_date');
                $endString = BestSellers::getConfigValue('end_date');
                $start = new \DateTime($startString ?: '1970-01-01');
                $start->setTime(0, 0, 0);
                $end = new \DateTime($endString ?: 'now');
                $end->setTime(23, 59, 59);
                break;
            case BestSellers::DATE_RANGE:
                $start = $this->dateFromRange(BestSellers::getConfigValue('date_range'));
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Dispatches the best-sellers computation event and returns the raw data
     * keyed by product id, plus the global total sales amount.
     *
     * @return array{byProduct: array<int, array{quantity: float, amount: float}>, totalSales: float}
     */
    public function compute(\DateTime $start, \DateTime $end): array
    {
        $event = new BestSellersEvent($start, $end);
        $this->dispatcher->dispatch($event, BestSellers::GET_BEST_SELLING_PRODUCTS);

        $byProduct = [];
        foreach ($event->getBestSellingProductsData() as $row) {
            $byProduct[(int) $row['product_id']] = [
                'quantity' => (float) $row['total_quantity'],
                'amount' => (float) $row['total_sales'],
            ];
        }

        return [
            'byProduct' => $byProduct,
            'totalSales' => (float) $event->getTotalSales(),
        ];
    }

    /**
     * Stats for a single product, or null when it has no sales in the period.
     *
     * @return array{quantity: float, amount: float, ratio: float}|null
     */
    public function statsForProduct(int $productId, \DateTime $start, \DateTime $end): ?array
    {
        $computed = $this->compute($start, $end);

        if (!isset($computed['byProduct'][$productId])) {
            return null;
        }

        $data = $computed['byProduct'][$productId];
        $ratio = $computed['totalSales'] > 0 ? 100 * $data['amount'] / $computed['totalSales'] : 0.0;

        return [
            'quantity' => $data['quantity'],
            'amount' => $data['amount'],
            'ratio' => $ratio,
        ];
    }

    /**
     * Best-seller rows ready for display, sorted and paginated.
     *
     * @return array{rows: list<array{id:int, ref:string, title:string, quantity:float, amount:float, ratio:float}>, total:int}
     */
    public function bestSellers(
        \DateTime $start,
        \DateTime $end,
        string $order,
        int $page,
        int $limit,
        string $locale,
    ): array {
        $computed = $this->compute($start, $end);
        $byProduct = $computed['byProduct'];
        $totalSales = $computed['totalSales'];

        if (empty($byProduct)) {
            return ['rows' => [], 'total' => 0];
        }

        $products = ProductQuery::create()
            ->filterById(array_keys($byProduct), \Propel\Runtime\ActiveQuery\Criteria::IN)
            ->find();

        $rows = [];
        foreach ($products as $product) {
            $id = (int) $product->getId();
            $data = $byProduct[$id];
            $ratio = $totalSales > 0 ? 100 * $data['amount'] / $totalSales : 0.0;

            $rows[] = [
                'id' => $id,
                'ref' => $product->getRef(),
                'title' => $product->setLocale($locale)->getTitle(),
                'quantity' => $data['quantity'],
                'amount' => $data['amount'],
                'ratio' => $ratio,
            ];
        }

        $rows = $this->sortRows($rows, $order);

        $total = \count($rows);
        $offset = max(0, ($page - 1) * $limit);
        $rows = \array_slice($rows, $offset, $limit);

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param list<array{id:int, ref:string, title:string, quantity:float, amount:float, ratio:float}> $rows
     *
     * @return list<array{id:int, ref:string, title:string, quantity:float, amount:float, ratio:float}>
     */
    private function sortRows(array $rows, string $order): array
    {
        $comparators = [
            'ref' => static fn ($a, $b) => strcmp($a['ref'], $b['ref']),
            'ref_reverse' => static fn ($a, $b) => strcmp($b['ref'], $a['ref']),
            'alpha' => static fn ($a, $b) => strcmp($a['title'], $b['title']),
            'alpha_reverse' => static fn ($a, $b) => strcmp($b['title'], $a['title']),
            'sold_count' => static fn ($a, $b) => $a['quantity'] <=> $b['quantity'],
            'sold_count_reverse' => static fn ($a, $b) => $b['quantity'] <=> $a['quantity'],
            'sold_amount' => static fn ($a, $b) => $a['amount'] <=> $b['amount'],
            'sold_amount_reverse' => static fn ($a, $b) => $b['amount'] <=> $a['amount'],
            'sale_ratio' => static fn ($a, $b) => $a['ratio'] <=> $b['ratio'],
            'sale_ratio_reverse' => static fn ($a, $b) => $b['ratio'] <=> $a['ratio'],
        ];

        $comparator = $comparators[$order] ?? $comparators['sold_count_reverse'];
        usort($rows, $comparator);

        return $rows;
    }

    private function dateFromRange(?string $dateRange): \DateTime
    {
        return match ($dateRange) {
            BestSellers::LAST_15_DAYS => new \DateTime('-15 days'),
            BestSellers::LAST_30_DAYS => new \DateTime('-30 days'),
            BestSellers::LAST_6_MONTHS => new \DateTime('-6 months'),
            BestSellers::LAST_3_MONTHS => new \DateTime('-3 months'),
            BestSellers::THIS_YEAR => new \DateTime('first day of January'),
            BestSellers::LAST_YEAR => new \DateTime('first day of January last year'),
            default => new \DateTime('1970-01-01'),
        };
    }
}
