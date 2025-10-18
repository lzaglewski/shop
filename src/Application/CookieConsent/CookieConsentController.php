<?php

declare(strict_types=1);

namespace App\Application\CookieConsent;

use App\Domain\User\Model\User;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/consents', name: 'api_cookie_consent_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CookieConsentController extends AbstractController
{
    public function __construct(private CookieConsentService $consentService)
    {
    }
    /** @noinspection PhpUnused @see public/js/consent.js */
    #[Route('', name: 'get', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $preferences = $this->consentService->getPreferencesForUser($user);

        return $this->json([
            'data' => $preferences,
        ]);
    }

    /** @noinspection PhpUnused @see public/js/consent.js */
    #[Route('', name: 'update', methods: ['PATCH'])]
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $this->decodePayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid payload'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $preferences = $this->consentService->savePreferencesForUser(
            $user,
            $payload,
            'update',
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        );

        return $this->json([
            'data' => $preferences,
        ]);
    }

    /** @noinspection PhpUnused @see public/js/consent.js */
    #[Route('/migrate', name: 'migrate', methods: ['POST'])]
    public function migratePreferences(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $this->decodePayload($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid payload'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $preferences = $this->consentService->savePreferencesForUser(
            $user,
            $payload,
            'migrate',
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        );

        return $this->json([
            'data' => $preferences,
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(Request $request): ?array
    {
        $content = $request->getContent();

        if ($content === '' || $content === null) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
