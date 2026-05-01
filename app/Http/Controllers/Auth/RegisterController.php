<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function showForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'display_name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $activationToken = Str::random(64);

        $user = User::create([
            'name' => $request->display_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'display_name' => $request->display_name,
            'activation_token' => $activationToken,
            'is_activated' => false,
        ]);

        // Create default preferences
        UserPreference::create([
            'user_id' => $user->id,
        ]);

        // Send activation email via PHPMailer
        MailService::sendActivationEmail($user, url('/activate/' . $activationToken));

        // Auto-login after registration
        Auth::login($user);

        return redirect('/notes')->with('success', 'Registration successful! Please check your email to activate your account.');
    }
}
