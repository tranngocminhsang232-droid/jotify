<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Tạo và cấu hình PHPMailer instance từ .env
     */
    private static function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

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

        // Charset và encoding
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        // Địa chỉ gửi
        $mail->setFrom(
            config('mail.from.address'),
            config('mail.from.name', config('app.name'))
        );

        return $mail;
    }

    /**
     * Gửi email kích hoạt tài khoản
     *
     * @param  \App\Models\User  $user
     * @param  string  $activationUrl
     * @return bool
     */
    public static function sendActivationEmail($user, string $activationUrl): bool
    {
        try {
            $mail = self::createMailer();
            $mail->addAddress($user->email, $user->display_name ?? $user->name);
            $mail->Subject = 'Activate Your ' . config('app.name') . ' Account';
            $mail->isHTML(true);

            // Render Blade template sang HTML
            $mail->Body    = view('emails.activation', [
                'user'          => $user,
                'activationUrl' => $activationUrl,
            ])->render();

            $mail->AltBody = "Hi {$user->display_name},\n\nPlease activate your account by visiting:\n{$activationUrl}";

            $mail->send();

            Log::info("Activation email sent to {$user->email}");
            return true;
        } catch (PHPMailerException $e) {
            Log::error("PHPMailer failed to send activation email to {$user->email}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to send activation email to {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gửi email đặt lại mật khẩu
     *
     * @param  string  $toEmail
     * @param  string  $resetUrl
     * @param  string  $otp
     * @return bool
     */
    public static function sendPasswordResetEmail(string $toEmail, string $resetUrl, string $otp): bool
    {
        try {
            $mail = self::createMailer();
            $mail->addAddress($toEmail);
            $mail->Subject = 'Reset Your ' . config('app.name') . ' Password';
            $mail->isHTML(true);

            // Render Blade template sang HTML
            $mail->Body    = view('emails.password-reset', [
                'resetUrl' => $resetUrl,
                'otp'      => $otp,
            ])->render();

            $mail->AltBody = "Reset your password by visiting:\n{$resetUrl}\n\nOr use OTP: {$otp}\n\nThis code expires in 60 minutes.";

            $mail->send();

            Log::info("Password reset email sent to {$toEmail}");
            return true;
        } catch (PHPMailerException $e) {
            Log::error("PHPMailer failed to send password reset email to {$toEmail}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email to {$toEmail}: " . $e->getMessage());
            return false;
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
     * @return bool
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
            $mail = self::createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = config('app.name') . ': ' . ($sharer ? $sharer->display_name : 'Someone') . ' shared a note with you';
            $mail->isHTML(true);

            $mail->Body = view('emails.note-shared', [
                'note'       => $note,
                'shareUrl'   => $shareUrl,
                'sharer'     => $sharer,
                'permission' => $permission,
            ])->render();

            $mail->AltBody = ($sharer ? $sharer->display_name : 'Someone')
                . " shared the note \"{$note->title}\" with you.\nView it at: {$shareUrl}";

            $mail->send();

            Log::info("Note shared email sent to {$toEmail}");
            return true;
        } catch (PHPMailerException $e) {
            Log::error("PHPMailer failed to send note-shared email to {$toEmail}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to send note-shared email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
