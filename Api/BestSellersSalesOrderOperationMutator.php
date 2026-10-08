<?php

declare(strict_types=1);

namespace BestSellers\Api;

use ApiPlatform\Metadata\AsOperationMutator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\OperationMutatorInterface;
use BestSellers\Api\Filter\BestSellersSalesOrderFilter;

/**
 * Lets the front product collection be ordered by sales (`best_sellers_sales_order=desc`), without the core knowing
 * the module.
 */
#[AsOperationMutator(operationName: '_api_/front/products_get_collection')]
final readonly class BestSellersSalesOrderOperationMutator implements OperationMutatorInterface
{
    public function __invoke(Operation $operation): Operation
    {
        return $operation->withFilters([...($operation->getFilters() ?? []), BestSellersSalesOrderFilter::class]);
    }
}
