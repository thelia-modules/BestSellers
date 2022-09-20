<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace BestSellers\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Map\OrderProductTableMap;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderProductQuery;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;

/**
 * Class PurchasedWithLoop
 * @package BestSellers\Loop
 * @method string getProductRef()
 */
class PurchasedWithLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAnyTypeArgument('product_ref', null, true),
            new Argument(
                'order',
                new TypeCollection(
                    new EnumListType(
                        [
                            'sold_count', 'sold_count_reverse',
                        ]
                    )
                ),
                'sold_count_reverse'
            ),
        );
    }

    public function buildModelCriteria()
    {
        $ref = $this->getProductRef();

        /*
            select product_ref, count(product_ref) from order_product
            where
             product_ref <> 'AIOSS325'
            and
             order_id in (select order_id from order_product where product_ref = 'AIOSS325')
            and
              product_ref in (select ref from product where visible = 1)
            group by product_ref
            order by count(product_ref) desc
         */

        $query = OrderProductQuery::create()
            ->withColumn('count('.OrderProductTableMap::COL_PRODUCT_REF.')', 'sold_count')
            ->filterByProductRef($ref, Criteria::NOT_EQUAL)
            // Find products ordered with our product
            ->where(OrderProductTableMap::COL_ORDER_ID . ' in (
                select '.OrderProductTableMap::COL_ORDER_ID.'
                from '.OrderProductTableMap::TABLE_NAME.'
                where '.OrderProductTableMap::COL_PRODUCT_REF.' = ?)', $ref)
            // That still exists and are visible
            ->where(
                OrderProductTableMap::COL_PRODUCT_REF.' in (
                select '.ProductTableMap::COL_REF.'
                from '.ProductTableMap::TABLE_NAME.'
                where '.ProductTableMap::COL_VISIBLE.' = 1)'
            )
            ->groupByProductRef()
        ;

        $orders  = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case "sold_count":
                    $query->orderBy('sold_count', Criteria::ASC);
                    break;
                case "sold_count_reverse":
                    $query->orderBy('sold_count', Criteria::DESC);
                    break;
            }
        }

        return $query;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult|void
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var OrderProduct $result */
        foreach ($loopResult->getResultDataCollection() as $result) {
            $loopResult->addRow(
                (new LoopResultRow())
                    ->set("PRODUCT_REF", $result->getProductRef())
                    ->set("SOLD_COUNT", $result->getVirtualColumn('sold_count'))
            );
        }

        return $loopResult;
    }
}
