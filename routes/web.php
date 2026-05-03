<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\ShareController;

// Redirect root to notes or login
Route::get('/', function () {
    return auth()->check() ? redirect('/notes') : redirect('/login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
    Route::get('/verify-otp', [ForgotPasswordController::class, 'showOtpForm'])->name('password.otp');
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::get('/reset-password-otp', [ForgotPasswordController::class, 'showResetFormAfterOtp'])->name('password.reset.otp');
});

// Activation (accessible always)
Route::get('/activate/{token}', [ActivationController::class, 'activate'])->name('activation.activate');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Resend activation
    Route::post('/activation/resend', [ActivationController::class, 'resend'])->name('activation.resend');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePassword'])->name('profile.password');
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.password.update');

    // Preferences
    Route::get('/preferences', [PreferenceController::class, 'edit'])->name('preferences.edit');
    Route::put('/preferences', [PreferenceController::class, 'update'])->name('preferences.update');
    Route::post('/preferences/view-mode', [PreferenceController::class, 'updateViewMode'])->name('preferences.viewmode');
    Route::post('/preferences/theme', [PreferenceController::class, 'updateTheme'])->name('preferences.theme');

    // Notes
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('/notes', [NoteController::class, 'create'])->name('notes.create');
    Route::get('/notes/{id}/edit', [NoteController::class, 'edit'])->name('notes.edit');
    Route::put('/notes/{id}/auto-save', [NoteController::class, 'autoSave'])->name('notes.autosave');
    Route::get('/notes/{id}/collab-latest', [NoteController::class, 'collabLatest'])->name('notes.collab-latest');
    Route::delete('/notes/{id}', [NoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('/notes/{id}/toggle-pin', [NoteController::class, 'togglePin'])->name('notes.pin');
    Route::post('/notes/{id}/upload-image', [NoteController::class, 'uploadImage'])->name('notes.upload-image');
    Route::delete('/notes/{noteId}/images/{imageId}', [NoteController::class, 'deleteImage'])->name('notes.delete-image');
    Route::put('/notes/{id}/labels', [NoteController::class, 'updateLabels'])->name('notes.labels');

    // Note password protection
    Route::post('/notes/{id}/set-password', [NoteController::class, 'setPassword'])->name('notes.set-password');
    Route::post('/notes/{id}/unlock', [NoteController::class, 'unlockNote'])->name('notes.unlock');
    Route::put('/notes/{id}/change-password', [NoteController::class, 'changeNotePassword'])->name('notes.change-password');
    Route::post('/notes/{id}/remove-password', [NoteController::class, 'removePassword'])->name('notes.remove-password');

    // Labels
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::get('/labels/manage', [LabelController::class, 'manage'])->name('labels.manage');
    Route::post('/labels', [LabelController::class, 'store'])->name('labels.store');
    Route::put('/labels/{id}', [LabelController::class, 'update'])->name('labels.update');
    Route::delete('/labels/{id}', [LabelController::class, 'destroy'])->name('labels.destroy');

    // Sharing
    Route::get('/shared', [ShareController::class, 'sharedWithMe'])->name('shared.index');
    Route::post('/notes/{id}/share', [ShareController::class, 'share'])->name('notes.share');
    Route::get('/notes/{id}/shares', [ShareController::class, 'getShares'])->name('notes.shares');
    Route::put('/shares/{id}/permission', [ShareController::class, 'updatePermission'])->name('shares.permission');
    Route::delete('/shares/{id}', [ShareController::class, 'revoke'])->name('shares.revoke');
    Route::get('/shared/{id}/view', [ShareController::class, 'viewSharedNote'])->name('shared.view');
    Route::post('/shared/{id}/unlock', [ShareController::class, 'unlockSharedNote'])->name('shared.unlock');
    Route::put('/shared/{id}/auto-save', [ShareController::class, 'autoSaveShared'])->name('shared.autosave');
    Route::get('/shared/{id}/latest', [ShareController::class, 'getLatestShared'])->name('shared.latest');
    Route::post('/shared/{id}/upload-image', [ShareController::class, 'uploadSharedImage'])->name('shared.upload-image');
    Route::delete('/shared/{shareId}/images/{imageId}', [ShareController::class, 'deleteSharedImage'])->name('shared.delete-image');

    // Notifications
    Route::get('/notifications', [ShareController::class, 'notifications'])->name('notifications.index');
    Route::put('/notifications/{id}/read', [ShareController::class, 'markNotificationRead'])->name('notifications.read');
    Route::get('/notifications/unread-count', [ShareController::class, 'unreadCount'])->name('notifications.unread');
});
// ─── EMAIL PREVIEW (debug only) ────────────────────────────────────────────
if (config('app.debug')) {
    Route::prefix('email-preview')->group(function () {
        Route::get('/activation', function () {
            $user = new \stdClass();
            $user->display_name = 'Nguyễn Kiệt';
            $user->name = 'kietle';
            return view('emails.activation', [
                'user'          => $user,
                'activationUrl' => url('/activate/sample-token-here'),
            ]);
        });

        Route::get('/password-reset', function () {
            return view('emails.password-reset', [
                'resetUrl' => url('/reset-password/sample-token?email=demo%40jotify.app'),
                'otp'      => '847293',
            ]);
        });

        Route::get('/note-shared', function () {
            $note = new \stdClass();
            $note->title = 'Meeting Notes – Q2 Planning';
            $sharer = new \stdClass();
            $sharer->display_name = 'Nguyễn Kiệt';
            $sharer->email = 'kietle@jotify.app';
            return view('emails.note-shared', [
                'note'       => $note,
                'shareUrl'   => url('/shared'),
                'sharer'     => $sharer,
                'permission' => 'edit',
            ]);
        });
    });

    // ─── MAIL DIAGNOSTIC (debug only) ──────────────────────────────────────
    Route::get('/debug/test-mail', function () {
        $config = [
            'MAIL_MAILER'       => config('mail.default'),
            'MAIL_HOST'         => config('mail.mailers.smtp.host'),
            'MAIL_PORT'         => config('mail.mailers.smtp.port'),
            'MAIL_SCHEME'       => config('mail.mailers.smtp.scheme'),
            'MAIL_USERNAME'     => config('mail.mailers.smtp.username') ? '✅ SET' : '❌ MISSING',
            'MAIL_PASSWORD'     => config('mail.mailers.smtp.password') ? '✅ SET' : '❌ MISSING',
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME'    => config('mail.from.name'),
            'APP_URL'           => config('app.url'),
            'APP_ENV'           => config('app.env'),
        ];

        $result = ['config' => $config, 'test_email' => null];

        // If ?send=YOUR_EMAIL is provided, actually send a test email
        $sendTo = request('send');
        if ($sendTo && filter_var($sendTo, FILTER_VALIDATE_EMAIL)) {
            try {
                $user = new \stdClass();
                $user->email = $sendTo;
                $user->display_name = 'Test User';
                $user->name = 'test';

                \App\Services\MailService::sendActivationEmail(
                    $user,
                    url('/activate/test-token-' . time())
                );

                $result['test_email'] = [
                    'status' => '✅ SUCCESS',
                    'sent_to' => $sendTo,
                    'message' => 'Email sent successfully! Check the inbox.',
                ];
            } catch (\Exception $e) {
                $result['test_email'] = [
                    'status' => '❌ FAILED',
                    'sent_to' => $sendTo,
                    'error' => $e->getMessage(),
                ];
            }
        } else {
            $result['test_email'] = [
                'usage' => 'Add ?send=your@email.com to actually send a test email',
            ];
        }

        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    });
}
