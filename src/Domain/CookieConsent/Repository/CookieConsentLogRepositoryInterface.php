<?php

declare(strict_types=1);

namespace App\Domain\CookieConsent\Repository;

use App\Domain\CookieConsent\Model\CookieConsentLog;

interface CookieConsentLogRepositoryInterface
{
    public function save(CookieConsentLog $log): void;
}
