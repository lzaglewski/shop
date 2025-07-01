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

    public function deleteUser(User $user): void
    {
        $this->userRepository->remove($user);
    }

    private function hashPassword(string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new User('temp', '', 'temp'), $plainPassword);
    }
}
