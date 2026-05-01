@extends('layouts.app')
@section('title', 'Reset Password - JOTIFY')

@section('content')
<style>
*, *::before, *::after { box-sizing: border-box; }
body { padding: 0 !important; margin: 0 !important; }
.auth-wrap { min-height: 100vh; display: flex; }
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
.brand-tagline { color: rgba(255,255,255,0.68); font-size: 0.875rem; margin-top: 0.5rem; text-align: center; max-width: 200px; line-height: 1.5; }
.auth-form-side {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 3rem 4rem; background: var(--color-card);
}
.auth-box { width: 100%; max-width: 420px; }
.panel-heading { margin-bottom: 1.5rem; }
.panel-heading h2 { font-size: 1.375rem; font-weight: 700; color: var(--color-body-text); letter-spacing: -0.02em; margin: 0; }
.panel-heading p  { font-size: 0.78rem; color: var(--color-muted); margin: 0.25rem 0 0; }
.auth-field { margin-bottom: 0.875rem; }
.auth-label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--color-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.07em; }
.auth-field-wrap { position: relative; }
.auth-field-icon { position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); font-size: 1.05rem; color: var(--color-muted); pointer-events: none; transition: color 0.2s; z-index: 1; }
.auth-field-wrap:focus-within .auth-field-icon { color: var(--accent-dim); }
.auth-input-clean {
    width: 100%; padding: 0.62rem 2.65rem 0.62rem 2.55rem;
    background: var(--color-body-bg); border: 1.5px solid var(--color-input-border);
    border-radius: 0.75rem; font-size: 0.875rem; color: var(--color-body-text);
    outline: none; font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.auth-input-clean::placeholder { color: var(--color-muted); opacity: 0.5; }
.auth-input-clean:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-subtle); background: var(--color-hover); }
.auth-submit {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    padding: 0.78rem 1.25rem; background: #22c55e; color: #fff;
    border: none; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700;
    cursor: pointer; font-family: inherit; margin-top: 0.25rem;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 4px 14px rgba(34,197,94,0.3);
}
.auth-submit:hover { background: #16a34a; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(34,197,94,0.4); }
.auth-submit:active { transform: translateY(0); box-shadow: none; }
.dark .auth-submit { background: #22c55e; }
.dark .auth-submit:hover { background: #4ade80; }
.auth-alert-error { border-radius: 0.75rem; padding: 0.65rem 0.875rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); margin-bottom: 1rem; font-size: 0.8rem; color: #dc2626; }
.auth-check { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--color-muted); cursor: pointer; margin: 0.5rem 0; }
.auth-check input { accent-color: var(--accent); }
.auth-divider { height: 1px; background: var(--color-border); margin: 1.25rem 0; }
.auth-footer { text-align: center; font-size: 0.8rem; color: var(--color-muted); }
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

<div class="auth-wrap"
     x-data="{ showPassword: false }">
    {{-- LEFT BRAND --}}
    <div class="auth-brand">
        <div class="brand-logo-ring">
            <img src="{{ asset('jotify-logo.png') }}" alt="JOTIFY">
        </div>
        <h1 class="brand-name">JOTIFY</h1>
        <p class="brand-tagline">Create a new password to secure your account.</p>
    </div>

    {{-- RIGHT FORM --}}
    <div class="auth-form-side">
        <div class="auth-box">
            <div class="panel-heading">
                <h2>Set New Password</h2>
                <p>Enter your new password below</p>
            </div>

            @if($errors->any())
            <div class="auth-alert-error">
                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                @if(isset($token) && $token)
                <input type="hidden" name="token" value="{{ $token }}">
                @endif

                <div class="auth-field">
                    <label class="auth-label" for="password">New Password</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon material-icons-outlined">lock</span>
                        <input :type="showPassword ? 'text' : 'password'"
                               id="password" name="password" required
                               class="auth-input-clean" placeholder="••••••••"
                               autocomplete="new-password">
                        <button type="button" @click="showPassword = !showPassword"
                                class="icon-btn !p-1"
                                style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);z-index:1;">
                            <span class="material-icons-outlined" style="font-size:1.05rem;"
                                  x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="password_confirmation">Confirm Password</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon material-icons-outlined">lock_outline</span>
                        <input :type="showPassword ? 'text' : 'password'"
                               id="password_confirmation" name="password_confirmation" required
                               class="auth-input-clean" placeholder="••••••••"
                               autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    <span class="material-icons-outlined" style="font-size:1.1rem;">lock_reset</span>
                    Reset Password
                </button>
            </form>

            <div class="auth-divider"></div>
            <p class="auth-footer">
                <a href="{{ route('login') }}">← Back to Login</a>
            </p>
        </div>
    </div>
</div>
@endsection
