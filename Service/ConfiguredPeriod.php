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
 * The period of sales the module configuration asks for, read the same way by the loop,
 * the statistics and the API sort.
 */
final readonly class ConfiguredPeriod
{
    /**
     * @return array{start: \DateTime, end: \DateTime}
     */
    public function resolve(): array
    {
        // No period configured (a fresh install): every sale counts, as the module documents.
        $start = new \DateTime('1970-01-01');
        $end = new \DateTime();

        switch (BestSellers::getConfigValue('date_type')) {
            case BestSellers::FIXED_DATE:
                // A fixed date left empty leaves that end of the period open.
                $start = new \DateTime(BestSellers::getConfigValue('start_date') ?: '1970-01-01');
                $start->setTime(0, 0, 0);
                $end = new \DateTime(BestSellers::getConfigValue('end_date') ?: 'now');
                $end->setTime(23, 59, 59);
                break;
            case BestSellers::DATE_RANGE:
                $start = $this->startOfRange(BestSellers::getConfigValue('date_range'));
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function startOfRange(?string $dateRange): \DateTime
    {
        return match ($dateRange) {
            BestSellers::LAST_15_DAYS => new \DateTime('-15 days'),
            BestSellers::LAST_30_DAYS => new \DateTime('-30 days'),
            BestSellers::LAST_6_MONTHS => new \DateTime('-6 months'),
            BestSellers::LAST_3_MONTHS => new \DateTime('-3 months'),
            BestSellers::THIS_YEAR => new \DateTime('first day of January'),
            BestSellers::LAST_YEAR => new \DateTime('-1 year'),
            default => new \DateTime('1970-01-01'),
        };
    }
}
