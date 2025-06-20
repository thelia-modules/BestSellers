<?php

namespace BestSellers\Api\DTO;

class BestSellerParams
{
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?bool $onlySoldProducts = false;
    public ?array $orders = null;

    public function getOnlySoldProducts(): ?bool
    {
        return $this->onlySoldProducts;
    }

    public function setOnlySoldProducts(?bool $onlySoldProducts): BestSellerParams
    {
        $this->onlySoldProducts = $onlySoldProducts;
        return $this;
    }

    public function getOrders(): ?array
    {
        return $this->orders;
    }

    public function setOrders(?array $orders): BestSellerParams
    {
        $this->orders = $orders;
        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): BestSellerParams
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): BestSellerParams
    {
        $this->startDate = $startDate;
        return $this;
    }
}
