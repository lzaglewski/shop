<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Order\Model\Order;
use App\Domain\Cart\Model\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineUserRepository implements UserRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findByLogin(string $login): ?User
    {
        return $this->repository->findOneBy(['login' => strtolower(trim($login))]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findActiveClients(): array
    {
        return $this->repository->findBy([
            'isActive' => true,
            'role' => UserRole::CLIENT
        ]);
    }

    public function remove(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
    
    public function findClientPricesForUser(User $user): array
    {
        return $this->entityManager->getRepository(ClientPrice::class)
            ->findBy(['client' => $user]);
    }
    
    public function findOrdersForUser(User $user): array
    {
        return $this->entityManager->getRepository(Order::class)
            ->findBy(['user' => $user]);
    }
    
    public function findCartsForUser(User $user): array
    {
        return $this->entityManager->getRepository(Cart::class)
            ->findBy(['user' => $user]);
    }
    
    public function removeCart($cart): void
    {
        $this->entityManager->remove($cart);
        $this->entityManager->flush();
    }
}
