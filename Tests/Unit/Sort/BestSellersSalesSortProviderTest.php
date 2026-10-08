<?php

declare(strict_types=1);

namespace BestSellers\Tests\Unit\Sort;

use BestSellers\Api\Filter\BestSellersSalesOrderFilter;
use BestSellers\Sort\BestSellersSalesSortProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BestSellersSalesSortProviderTest extends TestCase
{
    public function testTheSortIsDeclaredForTheThemeAndAnsweredByTheModuleFilter(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())->method('trans')->with('Best sellers', [], 'bestsellers')->willReturn('Meilleures ventes');

        $provider = new BestSellersSalesSortProvider($translator);

        self::assertSame('best_sellers_sales', $provider->value());
        self::assertSame(30, $provider->position());
        self::assertSame('Meilleures ventes', $provider->title());
        self::assertSame([BestSellersSalesOrderFilter::PARAMETER => 'desc'], $provider->parameters());
        self::assertSame('best_sellers_sales_order', BestSellersSalesOrderFilter::PARAMETER);
    }
}
