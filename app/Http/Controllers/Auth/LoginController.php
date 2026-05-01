<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Sync theme: nếu user chọn light/dark trên trang sign in → cập nhật DB
            $clientTheme = in_array($request->input('client_theme'), ['light', 'dark'])
                ? $request->input('client_theme')
                : null;
            if ($clientTheme) {
                $prefs = Auth::user()->preferences;
                if ($prefs) {
                    $prefs->update(['theme' => $clientTheme]);
                }
            }

            return redirect()->intended('/notes')->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'You have been logged out.');
    }
}
