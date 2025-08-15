<?php

declare(strict_types=1);

namespace App\Application\Admin;

use App\Application\Common\SettingsService;
use App\Application\Email\SmtpTestService;
use App\Application\Email\OvhSmtpTestService;
use App\Application\Form\SmtpSettingsType;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        private readonly OvhSmtpTestService $ovhSmtpTestService
    ) {
    }

    #[Route('', name: 'admin_settings')]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAll();
        $currentCategory = $this->settingsService->getHomepageCategory();

        return $this->render('admin/settings/index.html.twig', [
            'categories' => $categories,
            'currentCategory' => $currentCategory,
        ]);
    }

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
}
