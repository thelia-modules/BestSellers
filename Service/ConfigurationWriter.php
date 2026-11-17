<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BestSellers\Service;

use BestSellers\BestSellers;

/**
 * Saves what the configuration page sends. A value the page did not send keeps the one saved,
 * except the fixed dates: a date left empty clears the saved one.
 */
final readonly class ConfigurationWriter
{
    public function save(
        ?string $orderTypes,
        int|string|null $dateType,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        ?string $dateRange,
        ?string $orderBy,
    ): void {
        BestSellers::setConfigValue('start_date', $startDate?->format('Y-m-d') ?? '');
        BestSellers::setConfigValue('end_date', $endDate?->format('Y-m-d') ?? '');

        if (null !== $orderTypes) {
            BestSellers::setConfigValue('order_types', $orderTypes);
        }

        if (null !== $dateRange) {
            BestSellers::setConfigValue('date_range', $dateRange);
        }

        if (null !== $dateType) {
            BestSellers::setConfigValue('date_type', (string) $dateType);
        }

        BestSellers::setConfigValue('order_by', (string) $orderBy);
    }
}
