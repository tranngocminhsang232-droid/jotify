<?php

namespace App\Jobs;

use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Gửi email thông báo chia sẻ note.
 * Không implement ShouldQueue — được gọi qua register_shutdown_function
 * để không cần queue worker và không làm chậm API response.
 */
class SendNoteSharedEmail
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $noteTitle,
        public readonly string $shareUrl,
        public readonly string $sharerName,
        public readonly string $sharerEmail,
        public readonly string $permission,
    ) {}

    public function handle(): void
    {
        // Build minimal objects matching what the Blade view expects
        $note   = (object) ['title' => $this->noteTitle];
        $sharer = (object) [
            'display_name' => $this->sharerName,
            'email'        => $this->sharerEmail,
        ];

        try {
            MailService::sendNoteSharedEmail(
                $this->toEmail,
                $this->toName,
                $note,
                $this->shareUrl,
                $sharer,
                $this->permission,
            );
        } catch (\RuntimeException $e) {
            \Log::error("SendNoteSharedEmail job failed for {$this->toEmail}: " . $e->getMessage());
        }
    }
}
