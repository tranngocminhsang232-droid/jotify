@extends('layouts.app')
@section('title', 'Forgot Password - JOTIFY')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4"
     style="background: radial-gradient(ellipse at 50% 0%, rgba(0,255,136,0.12) 0%, #050d08 60%), #050d08;">

    <div class="absolute inset-0 pointer-events-none overflow-hidden" style="opacity:0.04;">
        <div style="background-image: linear-gradient(#00ff88 1px, transparent 1px), linear-gradient(90deg, #00ff88 1px, transparent 1px); background-size: 40px 40px; width:100%; height:100%;"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div style="width:90px;height:90px;border-radius:50%;background:#0d1a10;border:2px solid #00ff88;box-shadow:0 0 20px rgba(0,255,136,0.6),0 0 60px rgba(0,255,136,0.25),inset 0 0 20px rgba(0,255,136,0.05);display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <img src="{{ asset('jotify-logo.png') }}" alt="JOTIFY" style="width:70px;height:70px;object-fit:contain;filter:drop-shadow(0 0 8px #00ff88);">
                </div>
            </div>
            <h1 class="text-3xl font-bold" style="color:#00ff88;text-shadow:0 0 20px rgba(0,255,136,0.6);">Reset Password</h1>
            <p style="color:#4a7a62;margin-top:0.5rem;">Enter your email to receive a reset link &amp; OTP</p>
        </div>

        @if($errors->any())
        <div class="mb-4 rounded-xl px-4 py-3" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);">
            @foreach($errors->all() as $err)<p class="text-sm" style="color:#f87171;">{{ $err }}</p>@endforeach
        </div>
        @endif
        @if(session('success'))
        <div class="mb-4 rounded-xl px-4 py-3" style="background:rgba(0,255,136,0.08);border:1px solid rgba(0,255,136,0.3);">
            <p class="text-sm" style="color:#00ff88;">{{ session('success') }}</p>
        </div>
        @endif

        <form action="/forgot-password" method="POST" class="rounded-2xl p-8 space-y-5"
              style="background:rgba(0,255,136,0.03);backdrop-filter:blur(20px);border:1px solid rgba(0,255,136,0.15);box-shadow:0 0 40px rgba(0,255,136,0.06),0 25px 50px rgba(0,0,0,0.5);">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium mb-2" style="color:#a0d4b5;">Email Address</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 material-icons-outlined text-lg" style="color:#2d6b4a;">mail</span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="auth-input pl-10" placeholder="you@example.com">
                </div>
            </div>

            <button type="submit" class="btn-primary w-full">
                <span class="material-icons-outlined text-lg">send</span>
                Send Reset Link
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="{{ route('password.otp') }}" class="text-sm transition-colors"
               style="color:#00cc6a;" onmouseover="this.style.color='#00ff88'" onmouseout="this.style.color='#00cc6a'">
               Have an OTP? Enter it here →
            </a>
        </div>

        <p class="text-center mt-4 text-sm">
            <a href="{{ route('login') }}" class="font-medium transition-colors"
               style="color:#00ff88;" onmouseover="this.style.textShadow='0 0 8px rgba(0,255,136,0.7)'" onmouseout="this.style.textShadow='none'">
               ← Back to Login
            </a>
        </p>
    </div>
</div>
@endsection
