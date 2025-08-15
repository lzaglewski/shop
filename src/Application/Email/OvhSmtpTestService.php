<?php

declare(strict_types=1);

namespace App\Application\Email;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Exception;

class OvhSmtpTestService
{
    public function testOvhConnection(
        string $host,
        int $port,
        string $username,
        string $password,
        ?string $encryption = null
    ): array {
        try {
            // Create SMTP transport directly instead of using DSN
            $transport = new EsmtpTransport($host, $port);
            $transport->setUsername($username);
            $transport->setPassword($password);
            
            // Set encryption
            if ($encryption === 'ssl') {
                $transport->setEncryption('ssl');
            } elseif ($encryption === 'tls') {
                $transport->setEncryption('tls');
            }
            
            // Additional OVH-specific settings
            $transport->setLocalDomain('localhost');
            
            $mailer = new Mailer($transport);
            
            // Test with minimal email
            $email = (new Email())
                ->from($username)
                ->to($username)
                ->subject('OVH SMTP Test')
                ->text('Test connection');
            
            $mailer->send($email);
            
            return [
                'success' => true,
                'message' => 'OVH SMTP test zakończony sukcesem! Email testowy wysłany.'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OVH SMTP test failed: ' . $e->getMessage(),
                'debug' => [
                    'host' => $host,
                    'port' => $port,
                    'username' => $username,
                    'encryption' => $encryption,
                    'transport_class' => get_class($transport ?? null)
                ]
            ];
        }
    }
}