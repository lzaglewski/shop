<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\User\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    
    public function findById(int $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function findAll(): array;
    
    public function findActiveClients(): array;
    
    public function remove(User $user): void;
}
