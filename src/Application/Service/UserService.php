<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private UserRepositoryInterface $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function createUser(
        string $email,
        string $plainPassword,
        string $companyName,
        ?string $taxId = null,
        UserRole $role = UserRole::CLIENT
    ): User {
        $user = new User(
            $email,
            $this->hashPassword($plainPassword),
            $companyName,
            $taxId,
            $role
        );

        $this->userRepository->save($user);

        return $user;
    }

    public function updateUser(
        User $user,
        string $email,
        string $companyName,
        ?string $taxId = null
    ): User {
        $user->setEmail($email);
        $user->setCompanyName($companyName);
        $user->setTaxId($taxId);

        $this->userRepository->save($user);

        return $user;
    }

    public function changePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->hashPassword($plainPassword));
        $this->userRepository->save($user);
    }

    public function activateUser(User $user): void
    {
        $user->setIsActive(true);
        $this->userRepository->save($user);
    }

    public function deactivateUser(User $user): void
    {
        $user->setIsActive(false);
        $this->userRepository->save($user);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function getActiveClients(): array
    {
        return $this->userRepository->findActiveClients();
    }

    public function saveUser(User $user): void
    {
        $this->userRepository->save($user);
    }

    public function canDeleteUser(User $user): array
    {
        $errors = [];
        
        if ($user->getRole() === UserRole::CLIENT) {
            // Check for existing client prices
            $clientPrices = $this->userRepository->findClientPricesForUser($user);
            if (!empty($clientPrices)) {
                $errors[] = 'User has associated client prices. Please remove all client prices first.';
            }
            
            // Check for existing orders
            $orders = $this->userRepository->findOrdersForUser($user);
            if (!empty($orders)) {
                $errors[] = 'User has associated orders. Please consider deactivating the user instead.';
            }
        }
        
        return $errors;
    }

    public function deleteUser(User $user): void
    {
        // Clean up carts first (these can be safely removed)
        if ($user->getRole() === UserRole::CLIENT) {
            $carts = $this->userRepository->findCartsForUser($user);
            foreach ($carts as $cart) {
                $this->userRepository->removeCart($cart);
            }
        }
        
        $this->userRepository->remove($user);
    }

    private function hashPassword(string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new User('temp', '', 'temp'), $plainPassword);
    }
}
