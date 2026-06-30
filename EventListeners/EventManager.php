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

/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */

/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */

namespace BestSellers\EventListeners;

use BestSellers\BestSellers;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Propel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\OrderTableMap;
use Thelia\Model\Map\ProductTableMap;

class EventManager extends BaseAction implements EventSubscriberInterface
{
    /** @var AdapterInterface */
    protected $cacheAdapter;

    /**
     * DigressivePriceListener constructor.
     */
    public function __construct(AdapterInterface $cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BestSellers::GET_BEST_SELLING_PRODUCTS => ['calculateBestSellers', 128],
        ];
    }

    public function calculateBestSellers(BestSellersEvent $event): void
    {
        $cacheKey = sprintf(
            'best_sellers_%s_%s',
            $event->getStartDate()->format('Y-m-d'),
            $event->getEndDate()->format('Y-m-d')
        );

        try {
            $cacheItem = $this->cacheAdapter->getItem($cacheKey);

            if (!$cacheItem->isHit()) {
                /** @var PdoConnection $con */
                $con = Propel::getConnection();

                $rawStatusList = BestSellers::getConfigValue('order_types');
                $statusIntegers = array_filter(array_map('intval', explode(',', (string) $rawStatusList)));
                $statusList = $statusIntegers ? implode(',', $statusIntegers) : '2,3,4';

                $query = '
                    SELECT
                        '.ProductTableMap::COL_ID.' as product_id,
                        SUM('.OrderProductTableMap::COL_QUANTITY.') as total_quantity,
                        SUM('.OrderProductTableMap::COL_QUANTITY.' * IF('.OrderProductTableMap::COL_WAS_IN_PROMO.','.OrderProductTableMap::COL_PROMO_PRICE.', '.OrderProductTableMap::COL_PRICE.')) as total_sales
                    FROM
                        '.OrderProductTableMap::TABLE_NAME.'
                    LEFT JOIN
                        '.OrderTableMap::TABLE_NAME.' on '.OrderTableMap::COL_ID.' = '.OrderProductTableMap::COL_ORDER_ID.'
                    LEFT JOIN
                        '.ProductTableMap::TABLE_NAME.' on '.ProductTableMap::COL_REF.' = '.OrderProductTableMap::COL_PRODUCT_REF.'
                    WHERE
                        '.OrderTableMap::COL_CREATED_AT.' >= ?
                    AND
                        '.OrderTableMap::COL_CREATED_AT.' <= ?
                    AND
                        '.OrderTableMap::COL_STATUS_ID.' IN ( '.$statusList.' )
                    GROUP BY
                        '.ProductTableMap::COL_ID.'
                    ORDER BY
                        total_quantity desc
                    ';

                $query = preg_replace('/order([^_])/', '`order`$1', $query);

                $stmt = $con->prepare($query);

                $startDate = $event->getStartDate()->format('Y-m-d H:i:s');
                $endDate = $event->getEndDate()->format('Y-m-d H:i:s');

                $stmt->bindParam(1, $startDate);
                $stmt->bindParam(2, $endDate);

                $res = $stmt->execute();

                $data = [];

                $totalSales = 0;

                while ($res && $result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $data[] = $result;

                    $totalSales += $result['total_sales'];
                }

                $struct = [
                    'data' => $data,
                    'total_sales' => $totalSales,
                ];

                $cacheItem
                    ->set(json_encode($struct))
                    ->expiresAfter(60 * BestSellers::CACHE_LIFETIME_IN_MINUTES)
                ;

                $this->cacheAdapter->save($cacheItem);
            }

            $struct = json_decode($cacheItem->get(), true);

            $event
                ->setBestSellingProductsData($struct['data'])
                ->setTotalSales($struct['total_sales'])
            ;
        } catch (InvalidArgumentException $e) {
            // Nothing to do with this, return an empty result.
        }
    }
}
