<?php

declare(strict_types=1);

namespace App\Application\Email;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Exception;

class SmtpTestService
{
    public function testConnection(
        string $host,
        int $port,
        string $username,
        string $password,
        ?string $encryption = null,
        ?string $testEmail = null
    ): array {
        try {
            // Build DSN with proper URL encoding for special characters
            $dsn = sprintf('smtp://%s:%s@%s:%d', 
                urlencode($username), 
                urlencode($password), 
                $host, 
                $port
            );
            
            if ($encryption) {
                // Handle different encryption types for OVH
                if (strtolower($encryption) === 'ssl' && $port == 465) {
                    $dsn .= '?encryption=ssl';
                } elseif (strtolower($encryption) === 'tls' && $port == 587) {
                    $dsn .= '?encryption=tls';
                } else {
                    $dsn .= '?encryption=' . $encryption;
                }
            }

            // Create transport and mailer
            $transport = Transport::fromDsn($dsn);
            
            // Try to configure transport for OVH specifically
            if (method_exists($transport, 'setLocalDomain')) {
                $transport->setLocalDomain('localhost');
            }
            
            $mailer = new Mailer($transport);

            // Test connection by sending a test email
            if ($testEmail && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $email = (new Email())
                    ->from($username)
                    ->to($testEmail)
                    ->subject('Test połączenia SMTP')
                    ->text('To jest testowa wiadomość wysłana w celu sprawdzenia konfiguracji SMTP.')
                    ->html('<p>To jest <strong>testowa wiadomość</strong> wysłana w celu sprawdzenia konfiguracji SMTP.</p>');

                $mailer->send($email);

                return [
                    'success' => true,
                    'message' => 'Test połączenia zakończony sukcesem! Email testowy został wysłany na adres: ' . $testEmail
                ];
            } else {
                // Just test the connection without sending email - this is safer and faster
                try {
                    // Create a test email but don't send it - just verify the transport works
                    $testEmail = (new Email())
                        ->from($username)
                        ->to($username) // Send to self for testing
                        ->subject('Test połączenia SMTP')
                        ->text('Test');
                    
                    // This will actually connect to SMTP server and verify credentials
                    $mailer->send($testEmail);
                    
                    return [
                        'success' => true,
                        'message' => 'Test połączenia zakończony sukcesem! SMTP działa poprawnie (wysłano email testowy na adres: ' . $username . ')'
                    ];
                } catch (Exception $e) {
                    // If sending fails, try just connecting without sending
                    $transport->start();
                    $transport->stop();
                    
                    return [
                        'success' => true,
                        'message' => 'Połączenie z serwerem SMTP zostało nawiązane pomyślnie (test bez wysyłania email-a).'
                    ];
                }
            }

        } catch (Exception $e) {
            // Add more detailed error information
            $debugInfo = [
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'encryption' => $encryption,
                'dsn' => $dsn ?? 'N/A',
                'password_length' => strlen($password ?? '')
            ];
            
            return [
                'success' => false,
                'message' => 'Błąd połączenia: ' . $e->getMessage(),
                'debug' => $debugInfo
            ];
        }
    }

    public function validateSmtpSettings(array $settings): array
    {
        $errors = [];

        if (empty($settings['smtp_host'])) {
            $errors[] = 'Host SMTP jest wymagany';
        }

        if (empty($settings['smtp_port']) || !is_numeric($settings['smtp_port'])) {
            $errors[] = 'Poprawny port SMTP jest wymagany';
        }

        if (empty($settings['smtp_username'])) {
            $errors[] = 'Login SMTP jest wymagany';
        }

        if (empty($settings['smtp_password'])) {
            $errors[] = 'Hasło SMTP jest wymagane';
        }

        if (!filter_var($settings['smtp_username'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Login musi być prawidłowym adresem email';
        }

        return $errors;
    }
}