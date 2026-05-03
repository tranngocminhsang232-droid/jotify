<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $token = Str::random(64);
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'otp' => $otp, 'created_at' => now()]
        );

        // Gửi email reset password — handle failure explicitly
        try {
            MailService::sendPasswordResetEmail(
                $request->email,
                url('/reset-password/' . $token . '?email=' . urlencode($request->email)),
                $otp
            );
        } catch (\RuntimeException $e) {
            \Log::error("Password reset email failed for {$request->email}: " . $e->getMessage());
            return back()->with('error', 'Unable to send reset email. Please try again later.');
        }

        // Redirect về cùng trang, mở panel OTP
        return redirect('/forgot-password?panel=otp&email=' . urlencode($request->email))
            ->with('success', 'Reset link & OTP have been sent! Enter the 6-digit code below.');
    }

    public function showResetForm($token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function showOtpForm()
    {
        return view('auth.otp-verify');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || $record->otp !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }

        // Check if OTP expired (60 minutes) — use Carbon parse to be safe
        if (\Carbon\Carbon::parse($record->created_at)->diffInMinutes(now()) > 60) {
            return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.']);
        }

        return redirect('/reset-password-otp?email=' . urlencode($request->email));
    }

    public function showResetFormAfterOtp(Request $request)
    {
        return view('auth.reset-password', [
            'token' => null,
            'email' => $request->email,
            'otp_verified' => true,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Invalid reset request.']);
        }

        // If token-based reset, verify token
        if ($request->token) {
            if (!Hash::check($request->token, $record->token)) {
                return back()->withErrors(['email' => 'Invalid reset token.']);
            }
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No user found with this email address.']);
        }
        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect('/login')->with('success', 'Password has been reset successfully. Please log in.');
    }
}
