@extends('layouts.app')
@section('title', 'Sign In - JOTIFY')

@section('content')
<style>
*, *::before, *::after { box-sizing: border-box; }
body { padding: 0 !important; margin: 0 !important; }

.auth-wrap { min-height: 100vh; display: flex; }

/* ── LEFT BRAND ── */
.auth-brand {
    width: 45%; flex-shrink: 0;
    background:
        radial-gradient(ellipse at 30% 20%, rgba(52, 211, 153, 0.35) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 80%, rgba(5, 150, 105, 0.30) 0%, transparent 55%),
        linear-gradient(145deg, #064e27 0%, #0a7c3e 25%, #16a34a 50%, #22c55e 75%, #34d399 100%);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 3rem 2.5rem;
    position: relative;
    overflow: hidden;
}
.auth-brand::before {
    content: '';
    position: absolute; inset: 0;
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

/* ── RIGHT FORM ── */
.auth-form-side {
    flex: 1;
    display: flex; flex-direction: column;
    align-items: stretch; justify-content: center;
    padding: 3rem 4rem;
    background: var(--color-card);
    position: relative;
    overflow-x: hidden;
    overflow-y: auto;
}

/* Theme pill */
.theme-pill {
    position: absolute; top: 1.25rem; right: 1.25rem;
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.7rem; border-radius: 99px;
    border: 1px solid var(--color-border); background: var(--color-body-bg);
    color: var(--color-muted); font-size: 0.7rem; font-weight: 700;
    cursor: pointer; text-transform: uppercase; letter-spacing: 0.04em; transition: all 0.2s;
    z-index: 10;
}
.theme-pill:hover { border-color: var(--accent-border); color: var(--accent-dim); background: var(--accent-subtle); }
.theme-pill .material-icons-outlined { font-size: 0.9rem; }

/* ── SLIDING TRACK ──
   Only .auth-track-wrap clips — panels are full-width inside it.
   Panels don't clip themselves (preserves focus rings, outlines).
─────────────────────────────────────────────── */
.auth-track-wrap {
    overflow: hidden;
    width: 100%;
    position: relative; /* clip context for transformed track */
}
.auth-track {
    display: flex;
    width: 200%;
    transition: transform 0.48s cubic-bezier(0.22, 1, 0.36, 1);
    will-change: transform;
}
.auth-track.show-login    { transform: translateX(0); }
.auth-track.show-register { transform: translateX(-50%); }

.auth-panel {
    width: 50%;      /* = 100% of track-wrap */
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
.auth-panel .auth-divider,
.auth-panel .auth-footer,
.auth-panel .auth-alert-error,
.auth-panel .auth-alert-success {
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}

/* ── Panel content ── */
.panel-heading { margin-bottom: 1.25rem; }
.panel-heading h2 { font-size: 1.375rem; font-weight: 700; color: var(--color-body-text); letter-spacing: -0.02em; margin: 0; }
.panel-heading p  { font-size: 0.78rem; color: var(--color-muted); margin: 0.2rem 0 0; }

/* Fields */
.auth-field { margin-bottom: 0.75rem; }
.auth-label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--color-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.07em; }
.auth-field-wrap { position: relative; }
.auth-field-icon { position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%); font-size: 1.05rem; color: var(--color-muted); pointer-events: none; transition: color 0.2s; }
.auth-input-clean {
    width: 100%; padding: 0.62rem 1rem 0.62rem 2.55rem;
    background: var(--color-body-bg); border: 1.5px solid var(--color-input-border);
    border-radius: 0.75rem; font-size: 0.875rem; color: var(--color-body-text);
    outline: none; font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.auth-input-clean::placeholder { color: var(--color-muted); opacity: 0.5; }
.auth-input-clean:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-subtle); background: var(--color-hover); }
.auth-field-wrap:focus-within .auth-field-icon { color: var(--accent-dim); }
.auth-input-clean.has-toggle { padding-right: 2.65rem; }

/* Actions row */
.auth-actions { display: flex; align-items: center; justify-content: space-between; margin: 0.625rem 0; }
.auth-remember { display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; color: var(--color-muted); cursor: pointer; }
.auth-remember input { accent-color: var(--accent); }
.auth-forgot { font-size: 0.8rem; font-weight: 700; color: var(--accent-dim); text-decoration: none; }
.auth-forgot:hover { opacity: 0.75; }

/* Submit button */
.auth-submit {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    padding: 0.78rem 1.25rem; background: #22c55e; color: #fff;
    border: none; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
    margin-top: 0.25rem;
    box-shadow: 0 4px 14px rgba(34,197,94,0.3);
}
.auth-submit:hover {
    background: #16a34a;
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(34,197,94,0.4);
}
.auth-submit:active { transform: translateY(0); box-shadow: none; }
.auth-submit .material-icons-outlined { font-size: 1.1rem; }

/* Dark mode: nút sáng hơn để nổi bật trên nền tối */
.dark .auth-submit {
    background: #22c55e;
    box-shadow: 0 4px 18px rgba(34,197,94,0.4);
}
.dark .auth-submit:hover {
    background: #4ade80;
    box-shadow: 0 8px 28px rgba(74,222,128,0.45);
}

/* Alerts */
.auth-alert-error {
    border-radius: 0.75rem; padding: 0.65rem 0.875rem;
    background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25);
    margin-bottom: 1rem; font-size: 0.8rem; color: #dc2626;
}
.auth-alert-success {
    border-radius: 0.75rem; padding: 0.65rem 0.875rem;
    background: var(--accent-subtle); border: 1px solid var(--accent-border);
    margin-bottom: 1rem; font-size: 0.8rem; color: var(--accent-dim);
}

/* Email suggestion */
.auth-suggest {
    position: absolute; left: 0; right: 0; margin-top: 0.3rem;
    background: var(--color-card); border: 1.5px solid var(--accent-border);
    border-radius: 0.75rem; overflow: hidden; z-index: 40;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.auth-suggest-btn { width: 100%; display: flex; align-items: center; gap: 0.625rem; padding: 0.65rem 0.875rem; background: none; border: none; cursor: pointer; text-align: left; font-family: inherit; transition: background 0.15s; }
.auth-suggest-btn:hover { background: var(--accent-subtle); }
.auth-suggest-btn .material-icons-outlined { font-size: 1rem; color: var(--accent-dim); flex-shrink: 0; }
.auth-suggest-email { font-size: 0.8125rem; font-weight: 600; color: var(--color-body-text); }
.auth-suggest-hint  { font-size: 0.68rem; color: var(--color-muted); margin-top: 0.05rem; }

/* Divider & footer */
.auth-divider { height: 1px; background: var(--color-border); margin: 1rem 0; }
.auth-footer { text-align: center; font-size: 0.8rem; color: var(--color-muted); }
.auth-footer button { background: none; border: none; padding: 0; font: inherit; font-weight: 700; color: var(--accent-dim); cursor: pointer; }
.auth-footer button:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 680px) {
    .auth-wrap { flex-direction: column; }
    .auth-brand { width: 100%; min-height: 160px; padding: 2rem 1.5rem; flex-direction: row; justify-content: flex-start; gap: 1.25rem; }
    .brand-logo-ring { width: 60px; height: 60px; margin-bottom: 0; }
    .brand-logo-ring img { width: 80px; height: 80px; }
    .brand-name { font-size: 1.5rem; }
    .brand-tagline { display: none; }
    .auth-form-side { padding: 2rem 1.5rem; }
}
</style>

@php
    // Tự động mở register panel nếu có lỗi từ form đăng ký (redirect back)
    $openRegister = $errors->any() && (old('display_name') !== null || old('password_confirmation') !== null);
@endphp
<div class="auth-wrap"
     x-data="{
        mode: '{{ $openRegister ? 'register' : 'login' }}',
        showPassLogin: false,
        showPassReg: false,
        darkMode: localStorage.getItem('theme') === 'dark' || document.documentElement.classList.contains('dark'),
        setMode(m) { this.mode = m; },
        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
     }">

    {{-- LEFT BRAND --}}
    <div class="auth-brand">
        <div class="brand-logo-ring">
            <img src="{{ asset('jotify-logo.png') }}" alt="JOTIFY">
        </div>
        <h1 class="brand-name">JOTIFY</h1>
        <p class="brand-tagline">Your smart note-taking workspace.</p>
    </div>

    {{-- RIGHT FORM --}}
    <div class="auth-form-side">

        <button class="theme-pill" @click="toggleTheme()">
            <span class="material-icons-outlined" x-text="darkMode ? 'light_mode' : 'dark_mode'"></span>
            <span x-text="darkMode ? 'Light' : 'Dark'"></span>
        </button>

        {{-- SLIDING TRACK --}}
        <div class="auth-track-wrap">
            <div class="auth-track" :class="mode === 'login' ? 'show-login' : 'show-register'">

                {{-- ═══ PANEL 1 — LOGIN ═══ --}}
                <div class="auth-panel">
                    <div class="panel-heading">
                        <h2>Welcome back</h2>
                        <p>Sign in to your JOTIFY account</p>
                    </div>

                    @if($errors->any())
                    <div class="auth-alert-error">
                        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                    </div>
                    @endif
                    @if(session('success'))
                    <div class="auth-alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="/login" method="POST" id="login-form">
                        @csrf
                        <input type="hidden" name="client_theme" id="client_theme" value="light">

                        <div class="auth-field" x-data="emailSuggest()">
                            <label class="auth-label" for="jf_email">Email</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">mail</span>
                                <input type="text" id="jf_email" name="jf_email"
                                       value="{{ old('email') }}"
                                       inputmode="email" required readonly autocomplete="off"
                                       x-ref="emailInput"
                                       @focus="$el.removeAttribute('readonly'); $el.type='email'; onFocus()"
                                       @blur="onBlur()" @input="onInput($event.target.value)"
                                       class="auth-input-clean" placeholder="you@example.com">
                                <div class="auth-suggest"
                                     x-show="showSuggestion && suggestion"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100">
                                    <button type="button" class="auth-suggest-btn" @click="applySuggestion()">
                                        <span class="material-icons-outlined">manage_accounts</span>
                                        <div>
                                            <div class="auth-suggest-email" x-text="suggestion"></div>
                                            <div class="auth-suggest-hint">Remembered — tap to fill</div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="pw_login">Password</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">lock</span>
                                <input :type="showPassLogin ? 'text' : 'password'"
                                       id="pw_login" name="password" required
                                       class="auth-input-clean has-toggle" placeholder="••••••••">
                                <button type="button" @click="showPassLogin = !showPassLogin"
                                        class="icon-btn !p-1"
                                        style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);">
                                    <span class="material-icons-outlined" style="font-size:1.05rem;"
                                          x-text="showPassLogin ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                        </div>

                        <div class="auth-actions">
                            <label class="auth-remember">
                                <input type="checkbox" name="remember" id="remember"> Remember me
                            </label>
                            <a href="{{ route('password.request') }}" class="auth-forgot">Forgot password?</a>
                        </div>

                        <button type="submit" class="auth-submit" id="login-submit">
                            <span class="material-icons-outlined">login</span> Sign In
                        </button>
                    </form>

                    <div class="auth-divider"></div>
                    <p class="auth-footer">
                        Don't have an account?
                        <button type="button" @click="setMode('register')">Create one →</button>
                    </p>
                </div>

                {{-- ═══ PANEL 2 — REGISTER ═══ --}}
                <div class="auth-panel">
                    <div class="panel-heading">
                        <h2>Create account</h2>
                        <p>Start organizing your notes today</p>
                    </div>

                    @if($openRegister && $errors->any())
                    <div class="auth-alert-error">
                        @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                    </div>
                    @endif

                    <form action="/register" method="POST">
                        @csrf

                        <div class="auth-field">
                            <label class="auth-label" for="reg_email">Email</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">mail</span>
                                <input type="email" id="reg_email" name="email"
                                       value="{{ old('email') }}" required
                                       class="auth-input-clean" placeholder="you@example.com">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="display_name">Display Name</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">person</span>
                                <input type="text" id="display_name" name="display_name"
                                       value="{{ old('display_name') }}" required
                                       class="auth-input-clean" placeholder="John Doe">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="pw_reg">Password</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">lock</span>
                                <input :type="showPassReg ? 'text' : 'password'"
                                       id="pw_reg" name="password" required
                                       class="auth-input-clean has-toggle" placeholder="Min. 6 characters">
                                <button type="button" @click="showPassReg = !showPassReg"
                                        class="icon-btn !p-1"
                                        style="position:absolute;right:0.7rem;top:50%;transform:translateY(-50%);">
                                    <span class="material-icons-outlined" style="font-size:1.05rem;"
                                          x-text="showPassReg ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="pw_confirm">Confirm Password</label>
                            <div class="auth-field-wrap">
                                <span class="auth-field-icon material-icons-outlined">lock_outline</span>
                                <input :type="showPassReg ? 'text' : 'password'"
                                       id="pw_confirm" name="password_confirmation" required
                                       class="auth-input-clean has-toggle" placeholder="Repeat password">
                            </div>
                        </div>

                        <button type="submit" class="auth-submit">
                            <span class="material-icons-outlined">person_add</span> Create Account
                        </button>
                    </form>

                    <div class="auth-divider"></div>
                    <p class="auth-footer">
                        Already have an account?
                        <button type="button" @click="setMode('login')">Sign in →</button>
                    </p>
                </div>

            </div>{{-- end .auth-track --}}
        </div>{{-- end .auth-track-wrap --}}

    </div>{{-- end .auth-form-side --}}
</div>

<script>
function emailSuggest() {
    return {
        suggestion: '', showSuggestion: false, _blurTimer: null,
        init() {
            const saved = localStorage.getItem('rememberedEmail') || '';
            if (saved) this.suggestion = saved;
            setTimeout(() => {
                const el = this.$refs.emailInput;
                if (el && !el.matches(':focus')) el.value = '';
            }, 80);
        },
        onFocus() {
            clearTimeout(this._blurTimer);
            const cur = this.$refs.emailInput.value.trim();
            if (this.suggestion && (cur === '' || this.suggestion.toLowerCase().startsWith(cur.toLowerCase())))
                this.showSuggestion = true;
        },
        onBlur()   { this._blurTimer = setTimeout(() => { this.showSuggestion = false; }, 200); },
        onInput(v) { this.showSuggestion = !!(this.suggestion && v && this.suggestion.toLowerCase().startsWith(v.toLowerCase())); },
        applySuggestion() {
            this.$refs.emailInput.value = this.suggestion;
            this.showSuggestion = false;
            this.$refs.emailInput.focus();
        }
    };
}


document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    if (!form) return;
    const clientThemeInput = document.getElementById('client_theme');
    if (clientThemeInput) clientThemeInput.value = localStorage.getItem('theme') || 'light';
    const remember = document.getElementById('remember');
    const emailEl  = document.getElementById('jf_email');
    form.addEventListener('submit', function() {
        const email = emailEl?.value?.trim();
        if (emailEl) emailEl.name = 'email';
        if (clientThemeInput) clientThemeInput.value = localStorage.getItem('theme') || 'light';
        if (remember?.checked && email) localStorage.setItem('rememberedEmail', email);
        else if (remember && !remember.checked) localStorage.removeItem('rememberedEmail');
    });
});
</script>
@endsection
