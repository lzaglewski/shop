<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Repository;

use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;

interface ClientPriceRepositoryInterface
{
    public function save(ClientPrice $clientPrice): void;

    public function findById(int $id): ?ClientPrice;

    public function findByClientAndProduct(User $client, Product $product): ?ClientPrice;

    public function findByClient(User $client): array;

    public function findByProduct(Product $product): array;

    public function findAll(): array;
    
    public function findOneBy(array $criteria): ?ClientPrice;

    public function remove(ClientPrice $clientPrice): void;
}
