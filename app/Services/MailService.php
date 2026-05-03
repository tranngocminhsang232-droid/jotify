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
    // If RESEND_API_KEY is set → use Resend HTTP API (works on Railway/cloud)
    // Otherwise → use PHPMailer SMTP (works on localhost/XAMPP)
    // ──────────────────────────────────────────────────────────────────────

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
        $resendKey = config('services.resend.key');

        if ($resendKey) {
            self::sendViaResend($resendKey, $to, $toName, $subject, $htmlBody, $textBody);
        } else {
            self::sendViaSmtp($to, $toName, $subject, $htmlBody, $textBody);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  RESEND HTTP API TRANSPORT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Send email via Resend REST API (HTTPS — no SMTP ports needed).
     *
     * @throws \RuntimeException on API error
     */
    private static function sendViaResend(
        string $apiKey,
        string $to,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): void {
        $fromAddress = config('mail.from.address', 'onboarding@resend.dev');
        $fromName    = config('mail.from.name', config('app.name', 'JOTIFY'));

        $payload = [
            'from'    => "{$fromName} <{$fromAddress}>",
            'to'      => $toName ? ["{$toName} <{$to}>"] : [$to],
            'subject' => $subject,
            'html'    => $htmlBody,
            'text'    => $textBody,
        ];

        Log::debug('Resend API request', [
            'to'      => $to,
            'from'    => $payload['from'],
            'subject' => $subject,
        ]);

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.resend.com/emails', $payload);

        if ($response->successful()) {
            $id = $response->json('id') ?? 'unknown';
            Log::info("Email sent via Resend to {$to}, id: {$id}");
            return;
        }

        $error = $response->json('message') ?? $response->body();
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
                . "Set these environment variables or use RESEND_API_KEY for HTTP-based sending."
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
