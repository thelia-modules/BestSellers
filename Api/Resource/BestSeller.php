<?php

namespace BestSellers\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use BestSellers\Api\DTO\BestSellerProductDTO;
use BestSellers\Api\DTO\PurchasedWithDTO;
use BestSellers\Api\State\BestSellerProductProvider;
use BestSellers\Api\State\PurchasedWithProvider;
use Thelia\Api\Resource\Product;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/bestseller/by_sales',
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'startDate',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'example' => '2025-06-09 00:00:00'
                        ],
                        'description' => 'Start date for filtering sales',
                    ],
                    [
                        'name' => 'endDate',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'format' => 'date-time',
                            'example' => '2025-06-09 00:00:00'
                        ],
                        'description' => 'End date for filtering sales',
                    ],
                    [
                        'name' => 'order[]',
                        'in' => 'query',
                        'required' => false,
                        'explode' => true,
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'enum' => [
                                    'sold_count',
                                    'sold_count_reverse',
                                    'sold_amount',
                                    'sold_amount_reverse',
                                    'sale_ratio',
                                    'sale_ratio_reverse'
                                ]
                            ]
                        ],
                        'description' => 'Sorting criteria',
                    ],
                    [
                        'name' => 'onlySoldProducts',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'boolean',
                            'example' => true
                        ],
                        'description' => 'Filter to only include products that have been sold',
                    ],
                ]
            ],
            normalizationContext: ['groups' => [BestSellerProductDTO::GROUP_READ,Product::GROUP_FRONT_READ]],
            output: BestSellerProductDTO::class,
            provider: BestSellerProductProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/bestseller/purchased_with',
            openapiContext: [
                'summary' => 'List products purchased together with a given product',
                'description' => 'Returns visible products that have been purchased in the same orders as the specified product, including the number of times they were purchased together (sold_count).',
                'parameters' => [
                    [
                        'name' => 'productRef',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                        'description' => 'Reference of the base product to find co-purchased products with.',
                        'example' => 'PROD_ABC123',
                    ],
                    [
                        'name' => 'orders',
                        'in' => 'query',
                        'required' => false,
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                                'enum' => ['sold_count', 'sold_count_reverse'],
                            ],
                        ],
                        'style' => 'form',
                        'explode' => true,
                        'example' => ['sold_count_reverse'],
                    ],
                ]
            ],
            normalizationContext: ['groups' => [PurchasedWithDTO::GROUP_READ,Product::GROUP_FRONT_READ]],
            output: PurchasedWithDTO::class,
            provider: PurchasedWithProvider::class,
        )
    ],
)]
final class BestSeller
{
}
