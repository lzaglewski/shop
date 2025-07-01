<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Model\Pricing\ClientPrice;
use App\Domain\Model\Product\Product;
use App\Domain\Model\User\User;
use App\Domain\Repository\ClientPriceRepositoryInterface;

class PricingService
{
    private ClientPriceRepositoryInterface $clientPriceRepository;

    public function __construct(ClientPriceRepositoryInterface $clientPriceRepository)
    {
        $this->clientPriceRepository = $clientPriceRepository;
    }

    public function getProductPriceForClient(Product $product, User $client): float
    {
        $clientPrice = $this->clientPriceRepository->findByClientAndProduct($client, $product);
        
        if ($clientPrice !== null && $clientPrice->isActive()) {
            return $clientPrice->getPrice();
        }
        
        return $product->getBasePrice();
    }

    public function setProductPriceForClient(Product $product, User $client, float $price): ClientPrice
    {
        $clientPrice = $this->clientPriceRepository->findByClientAndProduct($client, $product);
        
        if ($clientPrice === null) {
            $clientPrice = new ClientPrice($client, $product, $price);
            $client->addClientPrice($clientPrice);
            $product->addClientPrice($clientPrice);
        } else {
            $clientPrice->setPrice($price);
            $clientPrice->setIsActive(true);
        }
        
        $this->clientPriceRepository->save($clientPrice);
        
        return $clientPrice;
    }

    public function removeProductPriceForClient(Product $product, User $client): void
    {
        $clientPrice = $this->clientPriceRepository->findByClientAndProduct($client, $product);
        
        if ($clientPrice !== null) {
            $clientPrice->setIsActive(false);
            $this->clientPriceRepository->save($clientPrice);
        }
    }
}
