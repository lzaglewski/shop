<?php

declare(strict_types=1);

namespace App\Domain\CookieConsent\Repository;

use App\Domain\CookieConsent\Model\CookieConsent;
use App\Domain\User\Model\User;

interface CookieConsentRepositoryInterface
{
    public function findByUser(User $user): ?CookieConsent;

    public function save(CookieConsent $consent): void;
}
