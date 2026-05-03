<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailService
{
    // ─── TRANSPORT SELECTION ───────────────────────────────────────────────
    // Priority: GMAIL_WEBHOOK → BREVO → MAILJET → RESEND → PHPMailer SMTP
    //
    // Gmail Webhook = Google Apps Script, uses YOUR Gmail, HTTPS, no signup
    // Brevo   = free 300 emails/day, needs account activation
    // Mailjet = free 200 emails/day, needs business verification
    // Resend  = free 100 emails/day, needs domain verification
    // SMTP    = works on localhost/XAMPP, blocked on Railway/cloud
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detect which transport is active.
     */
    public static function activeTransport(): string
    {
        if (config('services.gmail_webhook.url'))  return 'gmail_webhook';
        if (config('services.brevo.key'))           return 'brevo';
        if (config('services.mailjet.key'))         return 'mailjet';
        if (config('services.resend.key'))          return 'resend';
        return 'smtp';
    }

    /**
     * Send an email using the best available transport.
     *
     * @throws \RuntimeException on any failure
     */
    private static function sendEmail(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $transport = self::activeTransport();

        match ($transport) {
            'gmail_webhook' => self::sendViaGmailWebhook($to, $toName, $subject, $htmlBody, $textBody),
            'brevo'         => self::sendViaBrevo($to, $toName, $subject, $htmlBody, $textBody),
            'mailjet'       => self::sendViaMailjet($to, $toName, $subject, $htmlBody, $textBody),
            'resend'        => self::sendViaResend($to, $toName, $subject, $htmlBody, $textBody),
            default         => self::sendViaSmtp($to, $toName, $subject, $htmlBody, $textBody),
        };
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  GMAIL APPS SCRIPT WEBHOOK TRANSPORT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Send email via Google Apps Script webhook (HTTPS).
     * Uses the user's own Gmail account — no third-party service needed.
     * Free quota: ~100 emails/day on consumer Gmail.
     *
     * @throws \RuntimeException on API error
     */
    private static function sendViaGmailWebhook(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $webhookUrl = config('services.gmail_webhook.url');
        $secret     = config('services.gmail_webhook.secret', '');
        $fromName   = config('mail.from.name', config('app.name', 'JOTIFY'));

        $payload = [
            'secret'   => $secret,
            'to'       => $to,
            'toName'   => $toName,
            'subject'  => $subject,
            'html'     => $htmlBody,
            'text'     => $textBody,
            'fromName' => $fromName,
        ];

        Log::debug('Gmail Webhook request', [
            'to'      => $to,
            'subject' => $subject,
            'url'     => substr($webhookUrl, 0, 50) . '...',
        ]);

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($webhookUrl, $payload);

        if ($response->successful()) {
            $body = $response->json() ?? [];
            if (isset($body['error'])) {
                throw new \RuntimeException("Gmail Webhook error: " . $body['error']);
            }
            Log::info("Email sent via Gmail Webhook to {$to}");
            return;
        }

        $error  = $response->body();
        $status = $response->status();
        Log::error("Gmail Webhook error [{$status}] for {$to}: {$error}");
        throw new \RuntimeException("Gmail Webhook error ({$status}): {$error}");
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  BREVO (Sendinblue) HTTP API TRANSPORT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Send email via Brevo REST API (HTTPS — no SMTP ports needed).
     * Only requires a verified SENDER EMAIL (not a domain).
     *
     * @throws \RuntimeException on API error
     */
    private static function sendViaBrevo(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $apiKey      = config('services.brevo.key');
        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name', config('app.name', 'JOTIFY'));

        $payload = [
            'sender'      => ['name' => $fromName, 'email' => $fromAddress],
            'to'          => [['email' => $to, 'name' => $toName ?: $to]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
            'textContent' => $textBody,
        ];

        Log::debug('Brevo API request', [
            'to'      => $to,
            'from'    => $fromAddress,
            'subject' => $subject,
        ]);

        $response = Http::withHeaders(['api-key' => $apiKey])
            ->timeout(15)
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->successful()) {
            $messageId = $response->json('messageId') ?? 'unknown';
            Log::info("Email sent via Brevo to {$to}, messageId: {$messageId}");
            return;
        }

        $error  = $response->json('message') ?? $response->body();
        $status = $response->status();
        Log::error("Brevo API error [{$status}] for {$to}: {$error}");
        throw new \RuntimeException("Brevo API error ({$status}): {$error}");
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  MAILJET HTTP API TRANSPORT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Send email via Mailjet REST API (HTTPS — no SMTP ports needed).
     * Only requires a verified SENDER EMAIL (no domain needed).
     * Works immediately after signup — no account activation delay.
     *
     * @throws \RuntimeException on API error
     */
    private static function sendViaMailjet(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $apiKey      = config('services.mailjet.key');
        $apiSecret   = config('services.mailjet.secret');
        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name', config('app.name', 'JOTIFY'));

        $payload = [
            'Messages' => [[
                'From'     => ['Email' => $fromAddress, 'Name' => $fromName],
                'To'       => [['Email' => $to, 'Name' => $toName ?: $to]],
                'Subject'  => $subject,
                'HTMLPart' => $htmlBody,
                'TextPart' => $textBody,
            ]],
        ];

        Log::debug('Mailjet API request', [
            'to'      => $to,
            'from'    => $fromAddress,
            'subject' => $subject,
        ]);

        $response = Http::withBasicAuth($apiKey, $apiSecret)
            ->timeout(15)
            ->post('https://api.mailjet.com/v3.1/send', $payload);

        if ($response->successful()) {
            $messages = $response->json('Messages') ?? [];
            $status   = $messages[0]['Status'] ?? 'unknown';
            $msgId    = $messages[0]['To'][0]['MessageID'] ?? 'unknown';
            Log::info("Email sent via Mailjet to {$to}, status: {$status}, id: {$msgId}");

            if ($status === 'error') {
                $errMsg = $messages[0]['Errors'][0]['ErrorMessage'] ?? 'Unknown Mailjet error';
                throw new \RuntimeException("Mailjet delivery error: {$errMsg}");
            }
            return;
        }

        $error  = $response->json('ErrorMessage') ?? $response->body();
        $status = $response->status();
        Log::error("Mailjet API error [{$status}] for {$to}: {$error}");
        throw new \RuntimeException("Mailjet API error ({$status}): {$error}");
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  RESEND HTTP API TRANSPORT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Send email via Resend REST API (HTTPS — no SMTP ports needed).
     * Requires domain verification for sending to non-account emails.
     *
     * @throws \RuntimeException on API error
     */
    private static function sendViaResend(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $apiKey      = config('services.resend.key');
        $fromAddress = config('mail.from.address', 'onboarding@resend.dev');
        $fromName    = config('mail.from.name', config('app.name', 'JOTIFY'));

        $payload = [
            'from'    => "{$fromName} <{$fromAddress}>",
            'to'      => $toName ? ["{$toName} <{$to}>"] : [$to],
            'subject' => $subject,
            'html'    => $htmlBody,
            'text'    => $textBody,
        ];

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.resend.com/emails', $payload);

        if ($response->successful()) {
            $id = $response->json('id') ?? 'unknown';
            Log::info("Email sent via Resend to {$to}, id: {$id}");
            return;
        }

        $error  = $response->json('message') ?? $response->body();
        $status = $response->status();
        Log::error("Resend API error [{$status}] for {$to}: {$error}");
        throw new \RuntimeException("Resend API error ({$status}): {$error}");
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PHPMAILER SMTP TRANSPORT (local development)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Validate that all required SMTP configuration values are present.
     */
    private static function validateSmtpConfig(): void
    {
        $required = [
            'MAIL_HOST'         => config('mail.mailers.smtp.host'),
            'MAIL_USERNAME'     => config('mail.mailers.smtp.username'),
            'MAIL_PASSWORD'     => config('mail.mailers.smtp.password'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
        ];

        $missing = [];
        foreach ($required as $key => $value) {
            if (empty($value) || ($value === '127.0.0.1' && $key === 'MAIL_HOST')) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $list = implode(', ', $missing);
            throw new \RuntimeException(
                "Mail configuration incomplete — missing or default values for: {$list}. "
                . "Set BREVO_API_KEY or RESEND_API_KEY for HTTP-based sending on cloud platforms."
            );
        }
    }

    /**
     * Send email via PHPMailer SMTP.
     *
     * @throws \RuntimeException on connection/send failure
     */
    private static function sendViaSmtp(
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        self::validateSmtpConfig();

        $mail = new PHPMailer(true);

        // Enable SMTP debug output to Laravel log when in debug mode
        if (config('app.debug')) {
            $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) {
                Log::debug("PHPMailer SMTP [{$level}]: " . trim($str));
            };
        }

        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = config('mail.mailers.smtp.host', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = config('mail.mailers.smtp.username');
        $mail->Password   = config('mail.mailers.smtp.password');
        $mail->Port       = (int) config('mail.mailers.smtp.port', 465);

        // Xử lý encryption (smtps = SSL, starttls = TLS)
        $scheme = config('mail.mailers.smtp.scheme', 'smtps');
        if ($scheme === 'smtps') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // Timeout
        $mail->Timeout       = 15;
        $mail->SMTPKeepAlive = false;

        // Charset và encoding
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        // Địa chỉ gửi
        $mail->setFrom(
            config('mail.from.address'),
            config('mail.from.name', config('app.name'))
        );

        // Recipient
        $mail->addAddress($to, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        try {
            $mail->send();
            Log::info("Email sent via SMTP to {$to}");
        } catch (PHPMailerException $e) {
            throw new \RuntimeException("SMTP Error: " . $e->getMessage(), 0, $e);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PUBLIC API — unchanged interface for all callers
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Gửi email kích hoạt tài khoản
     *
     * @param  \App\Models\User  $user
     * @param  string  $activationUrl
     * @return bool  true on success
     * @throws \RuntimeException on failure
     */
    public static function sendActivationEmail($user, string $activationUrl): bool
    {
        try {
            $subject = 'Activate Your ' . config('app.name') . ' Account';

            $html = view('emails.activation', [
                'user'          => $user,
                'activationUrl' => $activationUrl,
            ])->render();

            $text = "Hi {$user->display_name},\n\nPlease activate your account by visiting:\n{$activationUrl}";

            self::sendEmail(
                $user->email,
                $user->display_name ?? $user->name,
                $subject,
                $html,
                $text
            );

            Log::info("Activation email sent successfully to {$user->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send activation email to {$user->email}: " . $e->getMessage());
            throw new \RuntimeException("Failed to send activation email: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Gửi email đặt lại mật khẩu
     *
     * @param  string  $toEmail
     * @param  string  $resetUrl
     * @param  string  $otp
     * @return bool  true on success
     * @throws \RuntimeException on failure
     */
    public static function sendPasswordResetEmail(string $toEmail, string $resetUrl, string $otp): bool
    {
        try {
            $subject = 'Reset Your ' . config('app.name') . ' Password';

            $html = view('emails.password-reset', [
                'resetUrl' => $resetUrl,
                'otp'      => $otp,
            ])->render();

            $text = "Reset your password by visiting:\n{$resetUrl}\n\nOr use OTP: {$otp}\n\nThis code expires in 60 minutes.";

            self::sendEmail($toEmail, '', $subject, $html, $text);

            Log::info("Password reset email sent successfully to {$toEmail}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email to {$toEmail}: " . $e->getMessage());
            throw new \RuntimeException("Failed to send password reset email: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Gửi email chia sẻ ghi chú
     *
     * @param  string  $toEmail
     * @param  string  $toName
     * @param  mixed   $note
     * @param  string  $shareUrl
     * @param  mixed   $sharer      User object của người chia sẻ
     * @param  string  $permission  'read' hoặc 'edit'
     * @return bool  true on success
     * @throws \RuntimeException on failure
     */
    public static function sendNoteSharedEmail(
        string $toEmail,
        string $toName,
        $note,
        string $shareUrl,
        $sharer = null,
        string $permission = 'read'
    ): bool {
        try {
            $subject = config('app.name') . ': '
                . ($sharer ? $sharer->display_name : 'Someone')
                . ' shared a note with you';

            $html = view('emails.note-shared', [
                'note'       => $note,
                'shareUrl'   => $shareUrl,
                'sharer'     => $sharer,
                'permission' => $permission,
            ])->render();

            $text = ($sharer ? $sharer->display_name : 'Someone')
                . " shared the note \"{$note->title}\" with you.\nView it at: {$shareUrl}";

            self::sendEmail($toEmail, $toName, $subject, $html, $text);

            Log::info("Note shared email sent successfully to {$toEmail}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send note-shared email to {$toEmail}: " . $e->getMessage());
            throw new \RuntimeException("Failed to send note-shared email: " . $e->getMessage(), 0, $e);
        }
    }
}
