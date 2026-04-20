@extends('layouts.app')
@section('title', 'Reset Password - JOTIFY')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-body"
     x-data="{ showPassword: false }">

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div style="width:90px;height:90px;border-radius:50%;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;">
                    <img src="{{ asset('jotify-logo.png') }}" alt="JOTIFY" style="width:122px;height:122px;flex-shrink:0;object-fit:cover;">
                </div>
            </div>
            <h1 class="text-3xl font-bold" style="color:var(--accent-dim);">Set New Password</h1>
            <p class="text-muted mt-2 text-sm">Enter your new password below</p>
        </div>

        @if($errors->any())
        <div class="mb-4 rounded-xl px-4 py-3 bg-red-500/10 border border-red-300">
            @foreach($errors->all() as $err)<p class="text-sm text-red-600">{{ $err }}</p>@endforeach
        </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="bg-card rounded-2xl p-8 space-y-5 border border-border shadow-lg">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            @if(isset($token) && $token)
            <input type="hidden" name="token" value="{{ $token }}">
            @endif

            <div>
                <label for="password" class="block text-sm font-medium mb-2 text-body">New Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 material-icons-outlined text-lg text-muted">lock</span>
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                           class="auth-input pl-10" placeholder="••••••••">
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium mb-2 text-body">Confirm Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 material-icons-outlined text-lg text-muted">lock</span>
                    <input :type="showPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                           class="auth-input pl-10" placeholder="••••••••">
                </div>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" @change="showPassword = !showPassword" style="accent-color:var(--accent);">
                <span class="text-sm text-muted">Show password</span>
            </label>

            <button type="submit" class="btn-primary w-full">
                <span class="material-icons-outlined text-lg">lock_reset</span>
                Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
