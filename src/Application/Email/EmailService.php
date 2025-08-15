<?php

declare(strict_types=1);

namespace App\Application\Email;

use App\Application\Common\SettingsService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly SettingsService $settingsService,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly array $adminEmails
    ) {
    }

    private function getFromEmail(): string
    {
        return $this->settingsService->getMailFromEmail() ?? $this->fromEmail;
    }

    private function getFromName(): string
    {
        return $this->settingsService->getMailFromName() ?? $this->fromName;
    }

    private function getAdminEmails(): array
    {
        $dbEmails = $this->settingsService->getMailAdminEmails();
        return !empty($dbEmails) ? $dbEmails : $this->adminEmails;
    }

    private function isNotificationsEnabled(): bool
    {
        return $this->settingsService->isMailNotificationsEnabled();
    }

    private function getMailer(): MailerInterface
    {
        $smtpDsn = $this->settingsService->getSmtpDsn();
        
        if ($smtpDsn) {
            $transport = Transport::fromDsn($smtpDsn);
            return new Mailer($transport);
        }

        return $this->mailer;
    }

    public function sendOrderCreatedCustomerEmail(
        string $customerEmail,
        string $customerName,
        string $orderNumber,
        array $orderData
    ): void {
        if (!$this->isNotificationsEnabled()) {
            return;
        }

        $subject = 'Potwierdzenie zamówienia - ' . $orderNumber;
        
        $html = $this->twig->render('email/order_created_customer.html.twig', [
            'customerName' => $customerName,
            'orderNumber' => $orderNumber,
            'order' => $orderData
        ]);

        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($customerEmail)
            ->subject($subject)
            ->html($html);

        $this->getMailer()->send($email);
    }

    public function sendOrderCreatedAdminEmail(
        string $orderNumber,
        array $orderData
    ): void {
        if (!$this->isNotificationsEnabled()) {
            return;
        }

        $subject = 'Nowe zamówienie - ' . $orderNumber;
        
        $html = $this->twig->render('email/order_created_admin.html.twig', [
            'orderNumber' => $orderNumber,
            'order' => $orderData
        ]);

        foreach ($this->getAdminEmails() as $adminEmail) {
            $email = (new Email())
                ->from($this->getFromEmail())
                ->to($adminEmail)
                ->subject($subject)
                ->html($html);

            $this->getMailer()->send($email);
        }
    }

    public function sendOrderStatusChangedEmail(
        string $customerEmail,
        string $customerName,
        string $orderNumber,
        string $oldStatus,
        string $newStatus,
        array $orderData
    ): void {
        if (!$this->isNotificationsEnabled()) {
            return;
        }

        $subject = 'Zmiana statusu zamówienia - ' . $orderNumber;
        
        $html = $this->twig->render('email/order_status_changed.html.twig', [
            'customerName' => $customerName,
            'orderNumber' => $orderNumber,
            'oldStatus' => $oldStatus,
            'newStatus' => $newStatus,
            'order' => $orderData
        ]);

        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($customerEmail)
            ->subject($subject)
            ->html($html);

        $this->getMailer()->send($email);
    }

    public function sendOrderShippedEmail(
        string $customerEmail,
        string $customerName,
        string $orderNumber,
        ?string $trackingNumber,
        array $orderData
    ): void {
        if (!$this->isNotificationsEnabled()) {
            return;
        }

        $subject = 'Zamówienie zostało wysłane - ' . $orderNumber;
        
        $html = $this->twig->render('email/order_shipped.html.twig', [
            'customerName' => $customerName,
            'orderNumber' => $orderNumber,
            'trackingNumber' => $trackingNumber,
            'order' => $orderData
        ]);

        $email = (new Email())
            ->from($this->getFromEmail())
            ->to($customerEmail)
            ->subject($subject)
            ->html($html);

        $this->getMailer()->send($email);
    }
}