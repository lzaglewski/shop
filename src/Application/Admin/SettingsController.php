<?php

declare(strict_types=1);

namespace App\Application\Admin;

use App\Application\Common\SettingsService;
use App\Application\Email\SmtpTestService;
use App\Application\Email\OvhSmtpTestService;
use App\Application\Form\SmtpSettingsType;
use App\Application\Gallery\GalleryImageUploadService;
use App\Application\Gallery\GalleryService;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
        private readonly SmtpTestService $smtpTestService,
        private readonly OvhSmtpTestService $ovhSmtpTestService,
        private readonly GalleryService $galleryService,
        private readonly GalleryImageUploadService $galleryImageUploadService
    ) {
    }

    #[Route('', name: 'admin_settings')]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAll();
        $currentCategory = $this->settingsService->getHomepageCategory();
        $currentBanner = $this->settingsService->getHomepageBanner();

        return $this->render('admin/settings/index.html.twig', [
            'categories' => $categories,
            'currentCategory' => $currentCategory,
            'currentBanner' => $currentBanner,
        ]);
    }
    //Used
    #[Route('/general', name: 'admin_settings_general')]
    public function generalSettings(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $currency = $request->request->get('currency');
            if ($currency) {
                $this->settingsService->setCurrency($currency);
                $this->addFlash('success', 'Ustawienia ogólne zostały zapisane.');
            }
            return $this->redirectToRoute('admin_settings_general');
        }

        return $this->render('admin/settings/general.html.twig', [
            'currency' => $this->settingsService->getCurrency(),
        ]);
    }
    //Used
    #[Route('/email', name: 'admin_settings_email')]
    public function emailSettings(Request $request): Response
    {
        // Load current settings
        $formData = [
            'smtp_host' => $this->settingsService->getSmtpHost(),
            'smtp_port' => $this->settingsService->getSmtpPort(),
            'smtp_username' => $this->settingsService->getSmtpUsername(),
            'smtp_password' => '', // Never show password
            'smtp_encryption' => $this->settingsService->getSmtpEncryption(),
            'mail_from_email' => $this->settingsService->getMailFromEmail(),
            'mail_from_name' => $this->settingsService->getMailFromName(),
            'mail_admin_emails' => implode(',', $this->settingsService->getMailAdminEmails()),
            'mail_notifications_enabled' => $this->settingsService->isMailNotificationsEnabled(),
        ];

        $form = $this->createForm(SmtpSettingsType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();

            // Test connection button was clicked
            if ($form->get('test_connection')->isClicked()) {
                // Use current password if no new password provided
                $password = !empty($data['smtp_password'])
                    ? $data['smtp_password']
                    : $this->settingsService->getSmtpPassword();

                // Debug: Check if we're using database values or form values
                $host = $data['smtp_host'] ?? $this->settingsService->getSmtpHost();
                $port = $data['smtp_port'] ?? $this->settingsService->getSmtpPort();
                $username = $data['smtp_username'] ?? $this->settingsService->getSmtpUsername();
                $encryption = $data['smtp_encryption'] ?? $this->settingsService->getSmtpEncryption();

                $this->addFlash('info', sprintf('Debug: Testuję z Host=%s, Port=%d, User=%s, HasPassword=%s, Encryption=%s',
                    $host, $port, $username, $password ? 'Yes' : 'No', $encryption ?? 'none'));

                $testResult = $this->smtpTestService->testConnection(
                    $host,
                    $port,
                    $username,
                    $password ?? '',
                    $encryption,
                    !empty($data['mail_from_email']) ? $data['mail_from_email'] : null
                );

                if ($testResult['success']) {
                    $this->addFlash('success', $testResult['message']);
                } else {
                    $errorMessage = $testResult['message'];
                    if (isset($testResult['debug'])) {
                        $debug = $testResult['debug'];
                        $errorMessage .= sprintf(' [Debug: Host=%s, Port=%d, User=%s, Encryption=%s, DSN=%s]',
                            $debug['host'], $debug['port'], $debug['username'],
                            $debug['encryption'] ?? 'none', $debug['dsn'] ?? 'N/A');
                    }
                    $this->addFlash('error', $errorMessage);
                }
            }
            // Save button was clicked and form is valid
            elseif ($form->get('save')->isClicked() && $form->isValid()) {
                // Save SMTP settings
                $this->settingsService->setSmtpHost($data['smtp_host']);
                $this->settingsService->setSmtpPort($data['smtp_port']);
                $this->settingsService->setSmtpUsername($data['smtp_username']);

                // Only update password if provided
                if (!empty($data['smtp_password'])) {
                    $this->settingsService->setSmtpPassword($data['smtp_password']);
                }

                $this->settingsService->setSmtpEncryption($data['smtp_encryption']);
                $this->settingsService->setMailFromEmail($data['mail_from_email']);
                $this->settingsService->setMailFromName($data['mail_from_name']);

                // Parse admin emails
                $adminEmails = array_map('trim', explode(',', $data['mail_admin_emails']));
                $adminEmails = array_filter($adminEmails); // Remove empty strings
                $this->settingsService->setMailAdminEmails($adminEmails);

                $this->settingsService->setMailNotificationsEnabled($data['mail_notifications_enabled']);

                $this->addFlash('success', 'Ustawienia email zostały zapisane.');

                return $this->redirectToRoute('admin_settings_email');
            }
        }

        return $this->render('admin/settings/email.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    //Used
    #[Route('/homepage-category', name: 'admin_settings_homepage_category', methods: ['POST'])]
    public function updateHomepageCategory(Request $request): Response
    {
        $categoryId = $request->request->get('category_id');

        $category = null;
        if ($categoryId && $categoryId !== '') {
            $category = $this->categoryRepository->findById((int)$categoryId);
        }

        $this->settingsService->setHomepageCategory($category);

        $this->addFlash('success', 'Ustawienia zostały zapisane.');

        return $this->redirectToRoute('admin_settings');
    }
    //Used
    #[Route('/test-ovh-smtp', name: 'admin_settings_test_ovh_smtp', methods: ['POST'])]
    public function testOvhSmtp(Request $request): Response
    {
        $testResult = $this->ovhSmtpTestService->testOvhConnection(
            $this->settingsService->getSmtpHost() ?? 'ssl0.ovh.net',
            $this->settingsService->getSmtpPort() ?? 465,
            $this->settingsService->getSmtpUsername() ?? '',
            $this->settingsService->getSmtpPassword() ?? '',
            $this->settingsService->getSmtpEncryption() ?? 'ssl'
        );

        if ($testResult['success']) {
            $this->addFlash('success', '[OVH Direct] ' . $testResult['message']);
        } else {
            $errorMessage = '[OVH Direct] ' . $testResult['message'];
            if (isset($testResult['debug'])) {
                $debug = $testResult['debug'];
                $errorMessage .= sprintf(' [Debug: %s]', json_encode($debug));
            }
            $this->addFlash('error', $errorMessage);
        }

        return $this->redirectToRoute('admin_settings_email');
    }

    //Used
    #[Route('/homepage-banner', name: 'admin_settings_homepage_banner', methods: ['POST'])]
    public function updateHomepageBanner(Request $request): Response
    {
        $uploadedFile = $request->files->get('banner_image');

        if ($uploadedFile && $uploadedFile->isValid()) {
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/banners';

            if (!is_dir($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0755, true);
            }

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            try {
                $uploadedFile->move($uploadsDirectory, $newFilename);

                $oldBanner = $this->settingsService->getHomepageBanner();
                if ($oldBanner && file_exists($uploadsDirectory . '/' . $oldBanner)) {
                    unlink($uploadsDirectory . '/' . $oldBanner);
                }

                $this->settingsService->setHomepageBanner($newFilename);
                $this->addFlash('success', 'Banner został wgrany pomyślnie.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas wgrywania banera: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Proszę wybrać prawidłowy plik obrazu.');
        }

        return $this->redirectToRoute('admin_settings');
    }

    #[Route('/gallery', name: 'admin_settings_gallery')]
    public function gallerySettings(): Response
    {
        $galleryImages = $this->galleryService->getAllOrdered();

        return $this->render('admin/settings/gallery.html.twig', [
            'galleryImages' => $galleryImages,
        ]);
    }

    #[Route('/gallery/upload', name: 'admin_settings_gallery_upload', methods: ['POST'])]
    public function uploadGalleryImage(Request $request): Response
    {
        $uploadedFiles = $request->files->get('gallery_images');

        if ($uploadedFiles) {
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }

            try {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile && $uploadedFile->isValid()) {
                        $filename = $this->galleryImageUploadService->uploadImage($uploadedFile);
                        $this->galleryService->addImage($filename);
                    }
                }
                $this->addFlash('success', 'Zdjęcia galerii zostały wgrane pomyślnie.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas wgrywania zdjęć: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Proszę wybrać przynajmniej jedno zdjęcie.');
        }

        return $this->redirectToRoute('admin_settings_gallery');
    }

    #[Route('/gallery/delete/{id}', name: 'admin_settings_gallery_delete', methods: ['POST'])]
    public function deleteGalleryImage(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_gallery_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Błąd autoryzacji.');
            return $this->redirectToRoute('admin_settings_gallery');
        }

        $galleryImage = $this->galleryService->findById($id);
        if ($galleryImage) {
            try {
                $this->galleryImageUploadService->deleteImage($galleryImage->getFilename());
                $this->galleryService->removeImage($id);
                $this->addFlash('success', 'Zdjęcie zostało usunięte.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas usuwania zdjęcia: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_settings_gallery');
    }

    #[Route('/gallery/reorder', name: 'admin_settings_gallery_reorder', methods: ['POST'])]
    public function reorderGalleryImages(Request $request): JsonResponse
    {
        $positions = $request->getPayload()->all();

        try {
            $this->galleryService->reorderImages($positions);
            return new JsonResponse(['success' => true, 'message' => 'Galeria została zmieniona.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
