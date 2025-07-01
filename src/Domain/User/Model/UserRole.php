<?php

declare(strict_types=1);

namespace App\Domain\User\Model;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case CLIENT = 'ROLE_CLIENT';
}
