<?php

namespace App\Services;

use App\Core\Config;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = Config::get('mail', [
            'from_email' => 'noreply@swiftcart.com',
            'from_name' => 'SwiftCart'
        ]);
    }

    /**
     * إرسال بريد إلكتروني
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $headers = [
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        ];

        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * إرسال بريد ترحيب
     */
    public function sendWelcomeEmail(array $user): bool
    {
        $subject = 'مرحباً بك في SwiftCart';
        $body = $this->renderTemplate('welcome', ['user' => $user]);
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * إرسال بريد تأكيد الطلب
     */
    public function sendOrderConfirmation(array $order, array $user): bool
    {
        $subject = 'تأكيد الطلب #' . $order['order_number'];
        $body = $this->renderTemplate('order-confirmation', [
            'order' => $order,
            'user' => $user
        ]);
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * إرسال بريد إعادة تعيين كلمة المرور
     */
    public function sendPasswordReset(array $user, string $token): bool
    {
        $subject = 'إعادة تعيين كلمة المرور';
        $resetLink = Config::get('app.url') . '/reset-password?token=' . $token;
        $body = $this->renderTemplate('password-reset', [
            'user' => $user,
            'resetLink' => $resetLink
        ]);
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * عرض قالب البريد
     */
    private function renderTemplate(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        
        $templateFile = __DIR__ . '/../Views/emails/' . $template . '.php';
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            // قالب افتراضي بسيط
            echo "<html><body dir='rtl' style='font-family: Arial, sans-serif;'>";
            echo "<h1>" . ($data['subject'] ?? 'SwiftCart') . "</h1>";
            echo "<pre>" . print_r($data, true) . "</pre>";
            echo "</body></html>";
        }
        
        return ob_get_clean();
    }
}
