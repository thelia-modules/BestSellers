<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 21/05/2018 16:34
 */

namespace BestSellers\EventListeners;

use DateTime;
use Thelia\Core\Event\ActionEvent;

class BestSellersEvent extends ActionEvent
{
    protected DateTime $startDate;

    protected DateTime $endDate;

    protected array $bestSellingProductsData = [];

    protected int $totalSales = 0;


    public function __construct(DateTime $startDate = null, DateTime $endDate = null)
    {
        $this->startDate = $startDate ?? new DateTime("1970-01-01");
        $this->endDate = $endDate ?? new DateTime();
    }


    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(DateTime $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(DateTime $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getBestSellingProductsData(): array
    {
        return ! empty($this->bestSellingProductsData) ? $this->bestSellingProductsData : [];
    }

    public function setBestSellingProductsData(array $bestSellingProductsData): static
    {
        $this->bestSellingProductsData = $bestSellingProductsData;
        return $this;
    }

    public function getTotalSales(): int
    {
        return $this->totalSales;
    }

    public function setTotalSales(float $totalSales): static
    {
        $this->totalSales = $totalSales;
        return $this;
    }
}
