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
 * Saves what the configuration page sends. A fixed date left empty clears the saved one.
 */
final readonly class ConfigurationWriter
{
    /**
     * @param list<int|string> $orderStatusIds
     * @param string|null      $startDate      Y-m-d, or empty to clear it
     * @param string|null      $endDate        Y-m-d, or empty to clear it
     */
    public function save(
        array $orderStatusIds,
        int $dateType,
        ?string $startDate,
        ?string $endDate,
        ?string $dateRange,
        ?string $orderBy,
    ): void {
        $statusIds = array_map('intval', $orderStatusIds);
        sort($statusIds);

        BestSellers::setConfigValue('order_types', implode(',', $statusIds));
        BestSellers::setConfigValue('date_type', (string) $dateType);
        BestSellers::setConfigValue('start_date', $startDate ?? '');
        BestSellers::setConfigValue('end_date', $endDate ?? '');

        if (null !== $dateRange) {
            BestSellers::setConfigValue('date_range', $dateRange);
        }

        BestSellers::setConfigValue('order_by', (string) $orderBy);
    }
}
