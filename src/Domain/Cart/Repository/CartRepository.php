<?php

declare(strict_types=1);

namespace App\Domain\Cart\Repository;

use App\Domain\Cart\Model\Cart;
use App\Domain\User\Model\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function save(Cart $cart): void
    {
        $this->getEntityManager()->persist($cart);
        $this->getEntityManager()->flush();
    }

    public function remove(Cart $cart): void
    {
        $this->getEntityManager()->remove($cart);
        $this->getEntityManager()->flush();
    }

    public function findBySessionId(string $sessionId): ?Cart
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    public function findByUser(User $user): ?Cart
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function mergeAnonymousCartWithUserCart(Cart $anonymousCart, Cart $userCart): void
    {
        foreach ($anonymousCart->getItems() as $item) {
            $userCart->addItem($item);
        }
        
        $this->save($userCart);
        $this->remove($anonymousCart);
    }
}
