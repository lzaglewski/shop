<?php

declare(strict_types=1);

namespace App\Application\Pricing;

use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Pricing\Repository\ClientPriceRepositoryInterface;
use App\Domain\Pricing\Service\PricingService;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;

class ClientPriceService
{
    private ClientPriceRepositoryInterface $clientPriceRepository;
    private PricingService $pricingService;

    public function __construct(
        ClientPriceRepositoryInterface $clientPriceRepository,
        PricingService $pricingService
    ) {
        $this->clientPriceRepository = $clientPriceRepository;
        $this->pricingService = $pricingService;
    }

    public function setClientPrice(User $client, Product $product, float $price): ClientPrice
    {
        return $this->pricingService->setProductPriceForClient($product, $client, $price);
    }

    public function getClientPrice(User $client, Product $product): float
    {
        return $this->pricingService->getProductPriceForClient($product, $client);
    }

    public function removeClientPrice(User $client, Product $product): void
    {
        $this->pricingService->removeProductPriceForClient($product, $client);
    }

    public function getClientPricesForProduct(Product $product): array
    {
        return $this->clientPriceRepository->findByProduct($product);
    }

    public function getClientPricesForClient(User $client): array
    {
        return $this->clientPriceRepository->findByClient($client);
    }

    public function getAllClientPricesForClient(User $client): array
    {
        return $this->clientPriceRepository->findAllByClient($client);
    }

    public function bulkSetClientPrices(User $client, array $productPrices): void
    {
        foreach ($productPrices as $productId => $data) {
            $product = $data['product'] ?? null;
            $priceValue = $data['price'] ?? null;

            if ($product instanceof Product && is_numeric($priceValue)) {
                $this->setClientPrice($client, $product, (float)$priceValue);
            }
        }
    }

    public function getAllClientPrices(): array
    {
        return $this->clientPriceRepository->findAll();
    }

    public function getClientPriceById(int $id): ?ClientPrice
    {
        return $this->clientPriceRepository->findById($id);
    }

    /**
     * Get a client price by client and product
     */
    public function getClientPriceByClientAndProduct(User $client, Product $product): ?ClientPrice
    {
        return $this->clientPriceRepository->findOneBy([
            'client' => $client,
            'product' => $product
        ]);
    }

    public function saveClientPrice(ClientPrice $clientPrice): void
    {
        $this->clientPriceRepository->save($clientPrice);
    }

    public function deleteClientPrice(ClientPrice $clientPrice): void
    {
        $this->clientPriceRepository->remove($clientPrice);
    }

    /**
     * Get all products that are visible to a specific client
     * A product is visible to a client if there is an active ClientPrice entry for it
     */
    public function getVisibleProductsForClient(User $client): array
    {
        $clientPrices = $this->getClientPricesForClient($client);
        $visibleProducts = [];

        foreach ($clientPrices as $clientPrice) {
            if ($clientPrice->isActive()) {
                $visibleProducts[] = $clientPrice->getProduct();
            }
        }

        return $visibleProducts;
    }

    /**
     * Check if a product is visible to a specific client
     * A product is visible to a client if there is an active ClientPrice entry for it
     */
    public function isProductVisibleToClient(Product $product, User $client): bool
    {
        $clientPrice = $this->clientPriceRepository->findOneBy([
            'client' => $client,
            'product' => $product
        ]);

        // If no client price exists, the product is NOT visible to the client
        if (!$clientPrice) {
            return false;
        }

        // Only show active client prices
        return $clientPrice->isActive();
    }
}
