<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Email;

use App\Application\Email\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private Environment $twig;
    private EmailService $emailService;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        
        $this->emailService = new EmailService(
            $this->mailer,
            $this->twig,
            'from@example.com',
            'Test Shop',
            ['admin1@example.com', 'admin2@example.com']
        );
    }

    public function testSendOrderCreatedCustomerEmail(): void
    {
        $orderNumber = 'ORD-12345';
        $customerEmail = 'customer@example.com';
        $customerName = 'Test Company';
        $orderData = [
            'orderNumber' => $orderNumber,
            'totalAmount' => 199.99,
            'items' => []
        ];

        $expectedHtml = '<html>Order confirmation</html>';

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'email/order_created_customer.html.twig',
                [
                    'customerName' => $customerName,
                    'orderNumber' => $orderNumber,
                    'order' => $orderData
                ]
            )
            ->willReturn($expectedHtml);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($customerEmail, $orderNumber, $expectedHtml) {
                return $email->getTo()[0]->getAddress() === $customerEmail
                    && str_contains($email->getSubject(), $orderNumber)
                    && $email->getHtmlBody() === $expectedHtml
                    && $email->getFrom()[0]->getAddress() === 'from@example.com';
            }));

        $this->emailService->sendOrderCreatedCustomerEmail(
            $customerEmail,
            $customerName,
            $orderNumber,
            $orderData
        );
    }

    public function testSendOrderCreatedAdminEmail(): void
    {
        $orderNumber = 'ORD-12345';
        $orderData = [
            'orderNumber' => $orderNumber,
            'totalAmount' => 199.99,
            'customerEmail' => 'customer@example.com'
        ];

        $expectedHtml = '<html>Admin notification</html>';

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'email/order_created_admin.html.twig',
                [
                    'orderNumber' => $orderNumber,
                    'order' => $orderData
                ]
            )
            ->willReturn($expectedHtml);

        // Should send to both admin emails
        $this->mailer
            ->expects($this->exactly(2))
            ->method('send')
            ->with($this->callback(function (Email $email) use ($orderNumber, $expectedHtml) {
                return in_array($email->getTo()[0]->getAddress(), ['admin1@example.com', 'admin2@example.com'])
                    && str_contains($email->getSubject(), $orderNumber)
                    && $email->getHtmlBody() === $expectedHtml;
            }));

        $this->emailService->sendOrderCreatedAdminEmail(
            $orderNumber,
            $orderData
        );
    }

    public function testSendOrderStatusChangedEmail(): void
    {
        $orderNumber = 'ORD-12345';
        $customerEmail = 'customer@example.com';
        $customerName = 'Test Company';
        $oldStatus = 'Nowe';
        $newStatus = 'W trakcie realizacji';
        $orderData = ['orderNumber' => $orderNumber];

        $expectedHtml = '<html>Status changed</html>';

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'email/order_status_changed.html.twig',
                [
                    'customerName' => $customerName,
                    'orderNumber' => $orderNumber,
                    'oldStatus' => $oldStatus,
                    'newStatus' => $newStatus,
                    'order' => $orderData
                ]
            )
            ->willReturn($expectedHtml);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($customerEmail, $orderNumber) {
                return $email->getTo()[0]->getAddress() === $customerEmail
                    && str_contains($email->getSubject(), $orderNumber);
            }));

        $this->emailService->sendOrderStatusChangedEmail(
            $customerEmail,
            $customerName,
            $orderNumber,
            $oldStatus,
            $newStatus,
            $orderData
        );
    }

    public function testSendOrderShippedEmail(): void
    {
        $orderNumber = 'ORD-12345';
        $customerEmail = 'customer@example.com';
        $customerName = 'Test Company';
        $trackingNumber = 'TRACK123';
        $orderData = ['orderNumber' => $orderNumber];

        $expectedHtml = '<html>Order shipped</html>';

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'email/order_shipped.html.twig',
                [
                    'customerName' => $customerName,
                    'orderNumber' => $orderNumber,
                    'trackingNumber' => $trackingNumber,
                    'order' => $orderData
                ]
            )
            ->willReturn($expectedHtml);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($customerEmail, $orderNumber) {
                return $email->getTo()[0]->getAddress() === $customerEmail
                    && str_contains($email->getSubject(), $orderNumber);
            }));

        $this->emailService->sendOrderShippedEmail(
            $customerEmail,
            $customerName,
            $orderNumber,
            $trackingNumber,
            $orderData
        );
    }
}