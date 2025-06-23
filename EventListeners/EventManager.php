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
    protected AdapterInterface $cacheAdapter;

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

                $statusList = BestSellers::getConfigValue('order_types');
                if (!$statusList) {
                    $statusList = '2,3,4';
                }

                $query = '
                    SELECT
                        '.ProductTableMap::ID.' as product_id,
                        SUM('.OrderProductTableMap::QUANTITY.') as total_quantity,
                        SUM('.OrderProductTableMap::QUANTITY.' * IF('.OrderProductTableMap::WAS_IN_PROMO.','.OrderProductTableMap::PROMO_PRICE.', '.OrderProductTableMap::PRICE.')) as total_sales
                    FROM
                        '.OrderProductTableMap::TABLE_NAME.'
                    LEFT JOIN
                        '.OrderTableMap::TABLE_NAME.' on '.OrderTableMap::ID.' = '.OrderProductTableMap::ORDER_ID.'
                    LEFT JOIN
                        '.ProductTableMap::TABLE_NAME.' on '.ProductTableMap::REF.' = '.OrderProductTableMap::PRODUCT_REF.'
                    WHERE
                        '.OrderTableMap::CREATED_AT.' >= ?
                    AND
                        '.OrderTableMap::CREATED_AT.' <= ?
                    AND
                        '.OrderTableMap::STATUS_ID.' IN ( '.$statusList.' )
                    GROUP BY
                        '.ProductTableMap::ID.'
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

            $struct = json_decode($cacheItem->get(), true, 512, JSON_THROW_ON_ERROR);

            $event
                ->setBestSellingProductsData($struct['data'])
                ->setTotalSales($struct['total_sales'])
            ;
        } catch (InvalidArgumentException $e) {
            // Nothing to do with this, return an empty result.
        }
    }
}
