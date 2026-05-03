@extends('layouts.app')
@section('title', 'Reset Password - JOTIFY')

@section('content')
<style>
*, *::before, *::after { box-sizing: border-box; }
body { padding: 0 !important; margin: 0 !important; }
.auth-wrap { min-height: 100vh; display: flex; }

/* ── LEFT BRAND ── */
.auth-brand {
    width: 45%; flex-shrink: 0;
    background:
        radial-gradient(ellipse at 30% 20%, rgba(52,211,153,0.35) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 80%, rgba(5,150,105,0.30) 0%, transparent 55%),
        linear-gradient(145deg, #064e27 0%, #0a7c3e 25%, #16a34a 50%, #22c55e 75%, #34d399 100%);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 3rem 2.5rem; position: relative; overflow: hidden;
}
.auth-brand::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.06) 0%, transparent 50%),
        radial-gradient(circle at 75% 30%, rgba(255,255,255,0.04) 0%, transparent 40%);
    pointer-events: none;
}
.brand-logo-ring {
    width: 88px; height: 88px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.3); overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1.5rem; background: rgba(255,255,255,0.08);
}
.brand-logo-ring img { width: 118px; height: 118px; object-fit: cover; flex-shrink: 0; }
.brand-name    { font-size: 2rem; font-weight: 800; color: #fff; letter-spacing: -0.03em; margin: 0; }
.brand-tagline { color: rgba(255,255,255,0.68); font-size: 0.875rem; margin-top: 0.5rem; text-align: center; max-width: 220px; line-height: 1.5; transition: opacity 0.3s ease; }

/* ── RIGHT FORM ── */
.auth-form-side {
    flex: 1; display: flex; flex-direction: column;
    align-items: stretch; justify-content: center;
    padding: 3rem 4rem;
    background: var(--color-card);
    overflow: hidden;
}

/* ── SLIDING TRACK (giống login/register) ── */
.auth-track-wrap {
    overflow: hidden;
    width: 100%;
    position: relative;
}
.auth-track {
    display: flex;
    width: 200%;
    transition: transform 0.48s cubic-bezier(0.22, 1, 0.36, 1);
    will-change: transform;
}
.auth-track.show-forgot { transform: translateX(0); }
.auth-track.show-otp    { transform: translateX(-50%); }

.auth-panel {
    width: 50%;
    flex-shrink: 0;
    min-width: 0;
    overflow: hidden;
    padding: 0 1rem;
    box-sizing: border-box;
}
.auth-panel > * {
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}
.auth-panel form {
    max-width: 420px;
    width: 100%;
    margin: 0 auto;
}
.auth-panel .panel-heading,
.auth-panel .auth-alert-error,
.auth-panel .auth-alert-success,
.auth-panel .auth-divider,
.auth-panel .auth-footer { max-width: 420px; margin-left: auto; margin-right: auto; }

/* ── Panel content ── */
.panel-heading { margin-bottom: 1.25rem; }
.panel-heading h2 { font-size: 1.375rem; font-weight: 700; color: var(--color-body-text); letter-spacing: -0.02em; margin: 0; }
.panel-heading p  { font-size: 0.78rem; color: var(--color-muted); margin: 0.2rem 0 0; }

/* Fields */
.auth-field { margin-bottom: 0.75rem; }
.auth-label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--color-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.07em; }
.auth-field-wrap { position: relative; }
.auth-field-icon { position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); font-size: 1.05rem; color: var(--color-muted); pointer-events: none; transition: color 0.2s; z-index: 1; }
.auth-field-wrap:focus-within .auth-field-icon { color: var(--accent-dim); }
.auth-input-clean {
    width: 100%; padding: 0.62rem 1rem 0.62rem 2.55rem;
    background: var(--color-body-bg); border: 1.5px solid var(--color-input-border);
    border-radius: 0.75rem; font-size: 0.875rem; color: var(--color-body-text);
    outline: none; font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.auth-input-clean::placeholder { color: var(--color-muted); opacity: 0.5; }
.auth-input-clean:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-subtle); background: var(--color-hover); }

/* OTP input */
.otp-input {
    width: 100%; padding: 0.85rem 1rem;
    background: var(--color-body-bg); border: 1.5px solid var(--color-input-border);
    border-radius: 0.75rem; font-size: 2rem; font-weight: 800; color: var(--accent-dim);
    outline: none; font-family: 'Courier New', monospace;
    letter-spacing: 0.6em; text-align: center;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.otp-input::placeholder { color: var(--color-muted); opacity: 0.3; font-size: 1.5rem; }
.otp-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-subtle); background: var(--color-hover); }

/* Submit */
.auth-submit {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    padding: 0.78rem 1.25rem; background: #22c55e; color: #fff;
    border: none; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    margin-top: 0.25rem; box-shadow: 0 4px 14px rgba(34,197,94,0.3);
}
.auth-submit:hover { background: #16a34a; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(34,197,94,0.4); }
.auth-submit:active { transform: translateY(0); box-shadow: none; }
.dark .auth-submit { background: #22c55e; }
.dark .auth-submit:hover { background: #4ade80; }

/* Alerts */
.auth-alert-error   { border-radius: 0.75rem; padding: 0.65rem 0.875rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); margin-bottom: 1rem; font-size: 0.8rem; color: #dc2626; }
.auth-alert-success { border-radius: 0.75rem; padding: 0.65rem 0.875rem; background: var(--accent-subtle); border: 1px solid var(--accent-border); margin-bottom: 1rem; font-size: 0.8rem; color: var(--accent-dim); }

.auth-divider { height: 1px; background: var(--color-border); margin: 1rem 0; }
.auth-footer { text-align: center; font-size: 0.8rem; color: var(--color-muted); }
.auth-footer button { background: none; border: none; padding: 0; font: inherit; font-weight: 700; color: var(--accent-dim); cursor: pointer; }
.auth-footer button:hover { text-decoration: underline; }
.auth-footer a { font-weight: 700; color: var(--accent-dim); text-decoration: none; }
.auth-footer a:hover { text-decoration: underline; }

@media (max-width: 680px) {
    .auth-wrap { flex-direction: column; }
    .auth-brand { width: 100%; min-height: 140px; padding: 1.5rem; flex-direction: row; justify-content: flex-start; gap: 1.25rem; }
    .brand-logo-ring { width: 56px; height: 56px; margin-bottom: 0; }
    .brand-logo-ring img { width: 76px; height: 76px; }
    .brand-name { font-size: 1.4rem; }
    .brand-tagline { display: none; }
    .auth-form-side { padding: 2rem 1.5rem; }
}
</style>

@php
    $initPanel = request('panel') === 'otp' ? 'otp' : 'forgot';
    $initEmail = request('email', '');
@endphp

<div class="auth-wrap"
     x-data="{
         mode: '{{ $initPanel }}',
         setMode(m) { this.mode = m; }
     }">

    {{-- LEFT BRAND --}}
    <div class="auth-brand">
        <div class="brand-logo-ring">
            <img src="{{ asset('jotify-logo.png') }}" alt="JOTIFY">
        </div>
        <h1 class="brand-name">JOTIFY</h1>
        <p class="brand-tagline"
           x-text="mode === 'forgot'
               ? 'Enter your email to receive a reset link & OTP code.'
               : 'Enter the 6-digit OTP sent to your email.'">
        </p>
    </div>

    {{-- RIGHT FORM --}}
    <div class="auth-form-side">
        <div class="auth-track-wrap">
            <div class="auth-track" :class="mode === 'forgot' ? 'show-forgot' : 'show-otp'">

                {{-- ═══ PANEL 1 — FORGOT PASSWORD ═══ --}}
                <div class="auth-panel">
                    <div class="panel-heading">
                        <h2>Forgot Password?</h2>
                        <p>We'll send you a reset link & OTP code</p>
                    </div>

                    @if(session('error'))
                    <div class="auth-alert-error">{{ session('error') }}</div>
                    @endif
                    @if($errors->any() && !request('panel'))
                    <div class="auth-alert-error">
                        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                    </div>
                    @endif

                    <form action="/forgot-password" method="POST">
                        @csrf
                        <div class="auth-field">
                            <label class="auth-label" for="fp_email">Email Address</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">mail</span>
                                <input type="email" id="fp_email" name="email"
                                       value="{{ old('email', $initEmail) }}" required autofocus
                                       class="auth-input-clean" placeholder="you@example.com">
                            </div>
                        </div>

                        <button type="submit" class="auth-submit">
                            <span class="material-icons-outlined" style="font-size:1.1rem;">send</span>
                            Send Reset Link
                        </button>
                    </form>

                    <div class="auth-divider"></div>
                    <p class="auth-footer">
                        Have an OTP code? <button type="button" @click="setMode('otp')">Enter it here →</button>
                    </p>
                    <p class="auth-footer" style="margin-top:0.5rem;">
                        <a href="{{ route('login') }}">Back to Login</a>
                    </p>
                </div>

                {{-- ═══ PANEL 2 — OTP VERIFY ═══ --}}
                <div class="auth-panel">
                    <div class="panel-heading">
                        <h2>Enter OTP Code</h2>
                        <p>Enter the 6-digit code sent to your email</p>
                    </div>

                    @if(session('success'))
                    <div class="auth-alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any() && request('panel') === 'otp')
                    <div class="auth-alert-error">
                        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                    </div>
                    @endif

                    <form action="{{ route('password.otp') }}" method="POST">
                        @csrf
                        <div class="auth-field">
                            <label class="auth-label" for="otp_email">Email Address</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">mail</span>
                                <input type="email" id="otp_email" name="email"
                                       value="{{ old('email', $initEmail) }}"
                                       required class="auth-input-clean" placeholder="you@example.com">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="otp">OTP Code</label>
                            <input type="text" id="otp" name="otp" required maxlength="6"
                                   class="otp-input" placeholder="000000"
                                   inputmode="numeric" autocomplete="one-time-code">
                        </div>

                        <button type="submit" class="auth-submit">
                            <span class="material-icons-outlined" style="font-size:1.1rem;">verified</span>
                            Verify OTP
                        </button>
                    </form>

                    <div class="auth-divider"></div>
                    <p class="auth-footer">
                        Didn't get a code? <button type="button" @click="setMode('forgot')">← Go back</button>
                    </p>
                </div>

            </div>{{-- end .auth-track --}}
        </div>{{-- end .auth-track-wrap --}}
    </div>
</div>
@endsection
