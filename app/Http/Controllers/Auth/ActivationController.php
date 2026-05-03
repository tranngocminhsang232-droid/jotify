<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivationController extends Controller
{
    public function activate($token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect('/login')->withErrors(['activation' => 'Invalid activation link.']);
        }

        $user->update([
            'is_activated' => true,
            'activation_token' => null,
            'email_verified_at' => now(),
        ]);

        return redirect('/notes')->with('success', 'Your account has been activated successfully!');
    }

    public function resend(Request $request)
    {
        $user = auth()->user();

        if ($user->is_activated) {
            return back()->with('info', 'Your account is already activated.');
        }

        $token = \Str::random(64);
        $user->update(['activation_token' => $token]);

        // Resend activation email — handle failure explicitly
        $activationUrl = url('/activate/' . $token);

        try {
            MailService::sendActivationEmail($user, $activationUrl);
            return back()->with('success', 'Activation email has been resent. Please check your inbox.');
        } catch (\RuntimeException $e) {
            Log::error("Resend activation email failed for {$user->email}: " . $e->getMessage());
            return back()->with('error', 'Unable to send activation email. Please try again later or contact support.');
        }
    }
}
