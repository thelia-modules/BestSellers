<?php

namespace BestSellers\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use BestSellers\Api\DTO\PurchasedWithDTO;
use BestSellers\Service\BestSellerService;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Api\Bridge\Propel\Service\ApiResourcePropelTransformerService;
use Thelia\Api\Resource\Product;
use Thelia\Model\LangQuery;
use Thelia\Model\ProductQuery;

readonly class PurchasedWithProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack      $requestStack,
        private BestSellerService $bestSellerService,
        private ApiResourcePropelTransformerService $apiResourcePropelTransformerService,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('BestSellerService request not set');
        }
        $productRef = $request->query->get('productRef');
        if(!$productRef) {
            throw new \RuntimeException('BestSellerService productRef not set');
        }
        $product = ProductQuery::create()->filterByRef($productRef)->findOne();
        if(!$product) {
            throw new \RuntimeException(sprintf("Product not found with ref %s'", $productRef));
        }
        $productQuery = $this->bestSellerService->filterPurchasedWith(
            productQuery: ProductQuery::create(),
            orders: [$request->query->get('orders')],
            productRef: $request->query->get('productRef')
        );
        $langs = LangQuery::create()->filterByActive(1)->find();
        return array_map(
            function ($product) use ($context, $langs) {
                /** @var Product $productResource */
                $productResource = $this->apiResourcePropelTransformerService->modelToResource(
                    resourceClass: Product::class,
                    propelModel: $product,
                    context: $context,
                    langs: $langs
                );
                return (new PurchasedWithDTO())
                    ->setProduct($productResource)
                    ->setSoldCount($product->getVirtualColumn('sold_count'));
            },
            iterator_to_array($productQuery->find())
        );
    }
}
