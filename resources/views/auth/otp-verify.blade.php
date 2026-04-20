@extends('layouts.app')
@section('title', 'Verify OTP - JOTIFY')

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
            <h1 class="text-3xl font-bold" style="color:#00ff88;text-shadow:0 0 20px rgba(0,255,136,0.6);">Enter OTP</h1>
            <p style="color:#4a7a62;margin-top:0.5rem;">Enter the 6-digit code sent to your email</p>
        </div>

        @if($errors->any())
        <div class="mb-4 rounded-xl px-4 py-3" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);">
            @foreach($errors->all() as $err)<p class="text-sm" style="color:#f87171;">{{ $err }}</p>@endforeach
        </div>
        @endif

        <form action="{{ route('password.otp') }}" method="POST" class="rounded-2xl p-8 space-y-5"
              style="background:rgba(0,255,136,0.03);backdrop-filter:blur(20px);border:1px solid rgba(0,255,136,0.15);box-shadow:0 0 40px rgba(0,255,136,0.06),0 25px 50px rgba(0,0,0,0.5);">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium mb-2" style="color:#a0d4b5;">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="auth-input" placeholder="you@example.com">
            </div>
            <div>
                <label for="otp" class="block text-sm font-medium mb-2" style="color:#a0d4b5;">OTP Code</label>
                <input type="text" id="otp" name="otp" required maxlength="6"
                       class="auth-input text-center text-2xl font-mono"
                       style="letter-spacing:0.5em;color:#00ff88;"
                       placeholder="000000">
            </div>
            <button type="submit" class="btn-primary w-full">
                <span class="material-icons-outlined text-lg">verified</span>
                Verify OTP
            </button>
        </form>

        <p class="text-center mt-6 text-sm">
            <a href="{{ route('password.request') }}" class="font-medium transition-colors"
               style="color:#00ff88;" onmouseover="this.style.textShadow='0 0 8px rgba(0,255,136,0.7)'" onmouseout="this.style.textShadow='none'">
               ← Back
            </a>
        </p>
    </div>
</div>
@endsection
