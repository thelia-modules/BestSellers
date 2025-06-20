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

/*      email : thelia@cqfdev.fr */
/*      web : http://www.cqfdev.fr */

/*      For the full copyright and license information, please view the LICENSE */
/*      file that was distributed with this source code. */

namespace BestSellers\Loop;

use BestSellers\Api\DTO\BestSellerParams;
use BestSellers\Service\BestSellerService;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Product;
use Thelia\Model\ProductQuery;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;

/**
 * Class BestSellerLoop.
 *
 * @method string getStartDate()
 * @method string getEndDate()
 * @method bool getOnlySoldProducts()
 */
class BestSellerLoop extends Product
{
    public function __construct(private readonly BestSellerService $bestSellerService)
    {
    }

    protected function getArgDefinitions(): ArgumentCollection
    {
        $args = parent::getArgDefinitions();

        return $args
            ->addArguments([
                new Argument(
                    'order',
                    new TypeCollection(
                        new EnumListType([
                            'id',
                            'id_reverse',
                            'alpha',
                            'alpha_reverse',
                            'min_price',
                            'max_price',
                            'manual',
                            'manual_reverse',
                            'created',
                            'created_reverse',
                            'updated',
                            'updated_reverse',
                            'ref',
                            'ref_reverse',
                            'visible',
                            'visible_reverse',
                            'position',
                            'position_reverse',
                            'promo',
                            'new',
                            'random',
                            'given_id',
                            'sold_count',
                            'sold_count_reverse',
                            'sold_amount',
                            'sold_amount_reverse',
                            'sale_ratio',
                            'sale_ratio_reverse',
                        ])
                    ),
                    'alpha'
                ),
            ])
            ->addArgument(Argument::createBooleanTypeArgument('only_sold_products', false))
            ->addArgument(Argument::createAlphaNumStringTypeArgument('start_date'))
            ->addArgument(Argument::createAlphaNumStringTypeArgument('end_date'));
    }

    public function buildModelCriteria(): ProductQuery|ModelCriteria
    {
        $query = parent::buildModelCriteria();
        $bestSellerInput = new BestSellerParams();
        $bestSellerInput
            ->setOrders($this->getOrder())
            ->setOnlySoldProducts($this->getOnlySoldProducts())
            ->setEndDate($this->getEndDate())
            ->setStartDate($this->getStartDate());

        return $this->bestSellerService->filterBestSellerProduct(bestSeller: $bestSellerInput, query: $query);
    }

    protected function addOutputFields(
        LoopResultRow $loopResultRow,
                      $item
    ): void
    {
        $loopResultRow
            ->set('SOLD_QUANTITY', $item->getVirtualColumn('sold_quantity'))
            ->set('SOLD_AMOUNT', $item->getVirtualColumn('sold_amount'))
            ->set('SALE_RATIO', $item->getVirtualColumn('sale_ratio'));
    }
}
