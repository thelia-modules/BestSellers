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

use BestSellers\Service\BestSellerService;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Product;
use Thelia\Model\ProductQuery;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;

/**
 * Class PurchasedWithLoop
 * @package BestSellers\Loop
 * @method string getProductRef()
 * @method string[]    getOrder()
 */
class PurchasedWithLoop extends BaseLoop implements PropelSearchLoopInterface
{
    public function __construct(private readonly BestSellerService $bestSellerService)
    {
    }

    protected function getArgDefinitions(): ArgumentCollection
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

    public function buildModelCriteria(): ProductQuery|ModelCriteria
    {
        $query = ProductQuery::create();
        return $this->bestSellerService->filterPurchasedWith(
          productQuery: $query,
          orders: $this->getOrder(),
          productRef:  $this->getProductRef(),
        );
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var Product $result */
        foreach ($loopResult->getResultDataCollection() as $result) {
            $loopResult->addRow(
                (new LoopResultRow())
                    ->set("PRODUCT_REF", $result->getRef())
                    ->set("SOLD_COUNT", $result->getVirtualColumn('sold_count'))
            );
        }

        return $loopResult;
    }
}
