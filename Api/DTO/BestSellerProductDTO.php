<?php

namespace BestSellers\Api\DTO;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;
use Thelia\Api\Resource\Product;

#[ApiResource]
class BestSellerProductDTO
{
    public const GROUP_READ = 'bestseller:read';

    #[Groups([self::GROUP_READ])]
    public Product $product;

    #[Groups([self::GROUP_READ])]
    public ?float $soldQuality;

    #[Groups([self::GROUP_READ])]
    public ?float $soldAmount;

    #[Groups([self::GROUP_READ])]
    public ?float $soldRatio;

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): BestSellerProductDTO
    {
        $this->product = $product;
        return $this;
    }

    public function getSoldAmount(): ?float
    {
        return $this->soldAmount;
    }

    public function setSoldAmount(?float $soldAmount): BestSellerProductDTO
    {
        $this->soldAmount = $soldAmount;
        return $this;
    }

    public function getSoldQuality(): ?float
    {
        return $this->soldQuality;
    }

    public function setSoldQuality(?float $soldQuality): BestSellerProductDTO
    {
        $this->soldQuality = $soldQuality;
        return $this;
    }

    public function getSoldRatio(): ?float
    {
        return $this->soldRatio;
    }

    public function setSoldRatio(?float $soldRatio): BestSellerProductDTO
    {
        $this->soldRatio = $soldRatio;
        return $this;
    }
}
