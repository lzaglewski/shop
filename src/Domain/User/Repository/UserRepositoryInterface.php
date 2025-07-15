<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Model\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findAll(): array;

    public function findActiveClients(): array;

    public function remove(User $user): void;
    
    public function findClientPricesForUser(User $user): array;
    
    public function findOrdersForUser(User $user): array;
    
    public function findCartsForUser(User $user): array;
    
    public function removeCart($cart): void;
}
