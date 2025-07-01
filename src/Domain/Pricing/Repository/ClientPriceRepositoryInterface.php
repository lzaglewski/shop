<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Pricing\ClientPrice;
use App\Domain\Model\Product\Product;
use App\Domain\Model\User\User;

interface ClientPriceRepositoryInterface
{
    public function save(ClientPrice $clientPrice): void;
    
    public function findById(int $id): ?ClientPrice;
    
    public function findByClientAndProduct(User $client, Product $product): ?ClientPrice;
    
    public function findByClient(User $client): array;
    
    public function findByProduct(Product $product): array;
    
    public function findAll(): array;
    
    public function remove(ClientPrice $clientPrice): void;
}
