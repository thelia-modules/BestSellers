<?php

declare(strict_types=1);

namespace BestSellers\Sort;

use BestSellers\Api\Filter\BestSellersSalesOrderFilter;
use BestSellers\BestSellers;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Domain\Catalog\Product\ProductSortProviderInterface;

/**
 * "Best sellers" in the sort selector of the product listings, answered by BestSellersSalesOrderFilter.
 */
final readonly class BestSellersSalesSortProvider implements ProductSortProviderInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function value(): string
    {
        return 'best_sellers_sales';
    }

    public function title(): string
    {
        return $this->translator->trans('Best sellers', [], BestSellers::DOMAIN_NAME);
    }

    public function position(): int
    {
        return 30;
    }

    public function parameters(): array
    {
        return [BestSellersSalesOrderFilter::PARAMETER => 'desc'];
    }
}
