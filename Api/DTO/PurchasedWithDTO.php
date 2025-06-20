<?php

namespace BestSellers\Api\DTO;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;
use Thelia\Api\Resource\Product;

#[ApiResource]
class PurchasedWithDTO
{
    public const GROUP_READ = 'bestseller:purchased_with:read';

    #[Groups([self::GROUP_READ])]
    public Product $product;

    #[Groups([self::GROUP_READ])]
    public ?float $soldCount;

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): PurchasedWithDTO
    {
        $this->product = $product;
        return $this;
    }

    public function getSoldCount(): ?float
    {
        return $this->soldCount;
    }

    public function setSoldCount(?float $soldCount): PurchasedWithDTO
    {
        $this->soldCount = $soldCount;
        return $this;
    }
}
