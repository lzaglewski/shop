<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;

class ProductVisibilityService
{
    public function shouldFilterForClient(User $user): bool
    {
        return $user->getRole() === UserRole::CLIENT;
    }
}