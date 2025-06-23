<?php

namespace BestSellers\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use BestSellers\Api\DTO\BestSellerParams;
use BestSellers\Api\DTO\BestSellerProductDTO;
use BestSellers\Service\BestSellerService;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Api\Bridge\Propel\Service\ApiResourcePropelTransformerService;
use Thelia\Api\Resource\Product;
use Thelia\Model\LangQuery;
use Thelia\Model\ProductQuery;

readonly class BestSellerProductProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack      $requestStack,
        private BestSellerService $bestSellerService,
        private ApiResourcePropelTransformerService $apiResourcePropelTransformerService,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $productQuery = new ProductQuery();
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('BestSellerService request not set');
        }
        $orders = $request->query->all('order');
        $bestSellerInput = new BestSellerParams();
        $bestSellerInput
            ->setOrders($orders)
            ->setOnlySoldProducts(filter_var($request->query->get('onlySoldProducts'), FILTER_VALIDATE_BOOLEAN))
            ->setEndDate($request->query->get('endDate'))
            ->setStartDate($request->query->get('startDate'));

        $productQuery = $this->bestSellerService->filterBestSellerProduct($bestSellerInput, $productQuery);
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
                return (new BestSellerProductDTO())
                    ->setProduct($productResource)
                    ->setSoldAmount($product->getVirtualColumn('sold_quantity'))
                    ->setSoldQuality($product->getVirtualColumn('sold_amount'))
                    ->setSoldRatio($product->getVirtualColumn('sale_ratio'));
            },
            iterator_to_array($productQuery->find())
        );
    }
}
