<?php

declare(strict_types=1);

namespace App\Domain\Model\User;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case CLIENT = 'ROLE_CLIENT';
}
