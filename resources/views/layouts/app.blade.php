@php
    /* ─── Xác định theme ngay từ PHP để render HTML với màu nền đúng ──────────
       Đây là cách DUY NHẤT ngăn khoảng trắng 100%: set style trực tiếp trên
       <html> và <body> bằng server-side PHP — browser áp dụng ngay khi parse,
       trước bất kỳ CSS/JS/font nào được tải.
    ─────────────────────────────────────────────────────────────────────────── */
    $isDark    = auth()->check() && (auth()->user()->preferences->theme ?? 'light') === 'dark';
    $htmlBg    = $isDark ? '#071910' : '#f0fdf4';
    $htmlColor = $isDark ? '#d1fae5' : '#14532d';
@endphp
<!DOCTYPE html>
<html lang="en" style="background-color:{{ $htmlBg }};color:{{ $htmlColor }};"
      class="{{ $isDark ? 'dark' : '' }}">
<head>
    {{-- Sync localStorage với theme từ server (để toggle button hoạt động đúng) --}}
    <script>
        (function(){
            @auth
            var t = '{{ auth()->check() ? (auth()->user()->preferences->theme ?? 'light') : 'light' }}';
            localStorage.setItem('theme', t);
            if (t === 'dark') document.documentElement.classList.add('dark');
            else              document.documentElement.classList.remove('dark');
            @else
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#071910';
                document.documentElement.style.color = '#d1fae5';
            }
            @endauth
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="JOTIFY - A modern note management application">
    <title>@yield('title', 'JOTIFY')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════════════
           CSS DESIGN TOKENS — defined inline so they are always
           available even before app.css (Vite) finishes loading.
           app.css re-declares the same values (no conflict).
           ══════════════════════════════════════════════════════ */
        :root {
            --accent:           #22c55e;
            --accent-dim:       #16a34a;
            --accent-subtle:    rgba(34, 197, 94, 0.15);
            --accent-border:    rgba(34, 197, 94, 0.3);
            --bg-custom: none;
            /* Light theme */
            --color-body-bg:    #f0fdf4;
            --color-body-text:  #052e16;
            --color-muted:      #166534;
            --color-card:       #ffffff;
            --color-sidebar:    #f7fef9;
            --color-header:     rgba(255,255,255,0.97);
            --color-border:     #bbf7d0;
            --color-hover:      #dcfce7;
            --color-input-bg:   #f0fdf4;
            --color-input-border: #86efac;
            --sidebar-border:   rgba(34,197,94,0.2);
            --header-border:    rgba(34,197,94,0.15);
        }
        .dark {
            --color-body-bg:    #071910;
            --color-body-text:  #f0fdf4;
            --color-muted:      #a3e6bb;
            --color-card:       #0d2318;
            --color-sidebar:    #081d12;
            --color-header:     rgba(7,25,16,0.97);
            --color-border:     #1a3d28;
            --color-hover:      #102a1c;
            --color-input-bg:   #0a1e13;
            --color-input-border: #2d5a3d;
            --sidebar-border:   rgba(34,197,94,0.18);
            --header-border:    rgba(34,197,94,0.14);
        }

        /* Body fade-in on first load */
        body { opacity: 1; transition: opacity 0.12s ease; }
        body.app-ready { opacity: 1; }

        /* Viewport wrapper — mặc định visible để không clip card hover shadows.
           AJAX engine tự set overflow:hidden CHỈ trong khi slide animation. */
        #page-viewport {
            overflow: visible;
            position: relative;
            width: 100%;
        }

        /* App layout — clip overflow ngang để tránh giật khi slide */
        #app-layout {
            overflow-x: hidden;
        }

        /* Main content area: position:relative để absolute overlay (delete modal)
           chỉ che đúng vùng note display, không lan sang sidebar */
        #app-layout main {
            position: relative;
        }

        /* Content wrapper */
        #page-content { will-change: opacity, transform; }

        /* SLIDE — cả 2 trang, cùng curve, cùng 350ms */
        #page-content.slide-out-left {
            animation: page-exit 0.38s cubic-bezier(0.32, 0.72, 0, 1) forwards;
        }
        #page-content.slide-in-right {
            animation: page-enter 0.38s cubic-bezier(0.32, 0.72, 0, 1) forwards;
        }
        @keyframes page-exit {
            from { transform: translateX(0); }
            to   { transform: translateX(-100%); }
        }
        @keyframes page-enter {
            from { transform: translateX(100%); }
            to   { transform: translateX(0); }
        }

        /* Stable bg */
        html      { background-color: #f0fdf4; color: #14532d; overflow-x: hidden; }
        html.dark { background-color: #071910; color: #d1fae5; }
        body      { } /* overflow-x KHÔNG set ở đây — sẽ clip card hover box-shadow */
        body, aside, header { transition: background-color 0.3s ease, border-color 0.3s ease; }
        aside  { background: var(--color-sidebar) !important; border-color: var(--sidebar-border) !important; }
        header { background: var(--color-header)  !important; border-color: var(--header-border)  !important; }

        /* ── FREEZE sidebar/overlay trong khi navigate — ghi đè Alpine hoàn toàn ── */
        body.navigating #sidebar         { transition: none !important; }
        body.navigating #sidebar-overlay { display: none !important; opacity: 0 !important; pointer-events: none !important; }

        /* ── CRITICAL BUTTON STYLES (guaranteed even without Vite) ── */

        /* ── btn-primary: shimmer sweep + spring lift ── */
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem; background: #22c55e; color: #fff !important;
            text-decoration: none; position: relative; overflow: hidden;
            border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700; border: none;
            cursor: pointer; letter-spacing: 0.01em; user-select: none;
            box-shadow: 0 4px 14px rgba(34,197,94,0.3);
            transition: background 0.22s ease, box-shadow 0.25s ease, transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .btn-primary::after {
            content: '';
            position: absolute; top: -50%; left: -80%; width: 55%; height: 200%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.28), transparent);
            transform: skewX(-18deg);
            transition: left 0.55s cubic-bezier(0.4,0,0.2,1);
            pointer-events: none;
        }
        @media (hover: hover) {
            .btn-primary:hover { background: #16a34a; transform: translateY(-2px) scale(1.03); box-shadow: 0 10px 28px rgba(34,197,94,0.45); }
            .btn-primary:hover::after { left: 130%; }
        }
        .btn-primary:active { transform: scale(0.95) translateY(0); box-shadow: 0 2px 8px rgba(34,197,94,0.2); transition-duration: 0.1s; }
        .dark .btn-primary { background: #22c55e; box-shadow: 0 4px 18px rgba(34,197,94,0.4); }
        .dark .btn-primary:hover { background: #4ade80; }

        /* ── btn-secondary: border glow + lift ── */
        .btn-secondary {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem; background: transparent; color: #16a34a;
            border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; border: 1.5px solid rgba(34,197,94,0.3);
            cursor: pointer; user-select: none;
            transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        @media (hover: hover) {
            .btn-secondary:hover { background: rgba(34,197,94,0.08); border-color: #16a34a; transform: translateY(-1.5px); box-shadow: 0 0 0 3px rgba(34,197,94,0.12); }
        }
        .btn-secondary:active { transform: scale(0.97); box-shadow: none; transition-duration: 0.1s; }
        .dark .btn-secondary { color: #4ade80; border-color: rgba(34,197,94,0.35); }
        .dark .btn-secondary:hover { color: #4ade80; background: rgba(34,197,94,0.12); }

        /* ── btn-danger: shake + red glow ── */
        @keyframes btn-danger-shake {
            0%,100% { transform: translateX(0) translateY(-2px) scale(1.02); }
            20%     { transform: translateX(-3px) translateY(-2px) scale(1.02); }
            40%     { transform: translateX(3px) translateY(-2px) scale(1.02); }
            60%     { transform: translateX(-2px) translateY(-2px) scale(1.02); }
            80%     { transform: translateX(2px) translateY(-2px) scale(1.02); }
        }
        .btn-danger {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem; background: #ef4444; color: #fff;
            border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; border: none;
            cursor: pointer; user-select: none;
            transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        @media (hover: hover) {
            .btn-danger:hover { background: #dc2626; animation: btn-danger-shake 0.38s ease forwards; box-shadow: 0 8px 24px rgba(239,68,68,0.45); }
        }
        .btn-danger:active { transform: scale(0.95); box-shadow: none; transition-duration: 0.1s; }

        /* Respect reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .btn-primary, .btn-primary::after, .btn-secondary, .btn-danger { transition: none; animation: none; }
        }
    </style>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#22c55e">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-body text-body min-h-screen"
      style="background-color:{{ $htmlBg }};color:{{ $htmlColor }};"
      x-data="{
          darkMode: {{ $isDark ? 'true' : 'false' }},
          sidebarOpen: (window._sidebarOpen !== undefined) ? window._sidebarOpen : (window.innerWidth > 1024),
          notifOpen: false,
          notifications: [],
          unreadCount: 0,
          loadNotifications() {
              fetch('/notifications', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                  .then(r => r.json())
                  .then(d => { if (Array.isArray(d)) { this.notifications = d; } })
                  .catch(() => {});
          },
          markRead(notif) {
              if (notif.is_read) return;
              fetch('/notifications/' + notif.id + '/read', {
                  method: 'PUT',
                  headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
              }).then(() => { notif.is_read = true; this.unreadCount = Math.max(0, this.unreadCount - 1); }).catch(() => {});
          }
      }"
      :class="{ 'dark': darkMode }"
      x-init="
          $watch('darkMode', val => {
              const html = document.documentElement;
              const body = document.body;
              // Transition mượt trước khi đổi class
              html.style.transition = 'background-color 0.3s ease, color 0.3s ease';
              body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
              if (val) {
                  html.classList.add('dark');
                  html.style.backgroundColor = '#071910';
                  html.style.color = '#f0fdf4';
                  body.style.backgroundColor = '#071910';
                  body.style.color = '#f0fdf4';
              } else {
                  html.classList.remove('dark');
                  html.style.backgroundColor = '#f0fdf4';
                  html.style.color = '#052e16';
                  body.style.backgroundColor = '#f0fdf4';
                  body.style.color = '#052e16';
              }
              localStorage.setItem('theme', val ? 'dark' : 'light');
          });
          // Persist sidebarOpen vào window để Alpine re-init không reset
          $watch('sidebarOpen', val => { window._sidebarOpen = val; });
          window._sidebarOpen = sidebarOpen;
          $nextTick(() => document.body.classList.add('app-ready'));
          @auth
          fetch('/notifications/unread-count').then(r=>r.json()).then(d=>{unreadCount=d.count});
          @endauth
      ">

    @auth
    <div class="flex min-h-screen" id="app-layout">
        {{-- Sidebar Overlay — chỉ hiện trên mobile khi sidebar mở --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden"
             id="sidebar-overlay"
             style="display:none;"></div>

        {{--
            Desktop sidebar wrapper: animate width 288px → 0 khi collapse
            → main content tự động mở rộng nhờ flex-1 trên <main>
            Mobile: wrapper không có chiều rộng, aside dùng fixed overlay
        --}}
        <div class="hidden lg:block flex-shrink-0 transition-all duration-300 ease-in-out overflow-hidden"
             :style="sidebarOpen ? 'width:288px' : 'width:0px'">
        </div>

        {{-- Sidebar — fixed trên mobile, fixed+left:0 trên desktop nhưng được kéo vào/ra bằng translateX --}}
        <aside class="fixed top-0 left-0 z-50 w-72 h-screen bg-sidebar border-r border-border flex flex-col shadow-xl
                      transition-transform duration-300 ease-in-out"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               id="sidebar">
            
            {{-- Logo --}}
            <div class="p-5" style="border-bottom:1px solid var(--sidebar-border);">
                <a href="{{ route('notes.index') }}" class="flex items-center gap-3 no-underline">
                    <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                        <img src="{{ asset('jotify-logo.png') }}" alt="Logo" style="width:66px;height:66px;flex-shrink:0;object-fit:cover;">
                    </div>
                    <span class="text-xl font-bold" style="color:var(--accent-dim);letter-spacing:0.04em;">JOTIFY</span>
                </a>
            </div>

            {{-- User info --}}
            <div class="p-4" style="border-bottom:1px solid var(--sidebar-border);">
                <div class="flex items-center gap-3">
                    <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-10 h-10 rounded-full object-cover" style="border:2px solid var(--accent-border);">
                    <div class="flex-1 min-w-0">
                        <p class="sidebar-username truncate">{{ auth()->user()->display_name }}</p>
                        <p class="sidebar-email truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto p-3 space-y-1">
                <a href="{{ route('notes.index') }}" class="nav-link {{ request()->is('notes') && !request()->is('notes/*/edit') ? 'active' : '' }}">
                    <span class="material-icons-outlined text-lg">sticky_note_2</span>
                    <span>My Notes</span>
                </a>
                <a href="{{ route('shared.index') }}" class="nav-link {{ request()->is('shared*') ? 'active' : '' }}">
                    <span class="material-icons-outlined text-lg">people</span>
                    <span>Shared with Me</span>
                </a>

                <div class="pt-4 pb-2">
                    <div class="flex items-center justify-between px-3">
                        <span class="sidebar-section-label">Labels</span>
                        <button onclick="openLabelManager()" class="icon-btn" title="Manage Labels">
                            <span class="material-icons-outlined text-base">settings</span>
                        </button>
                    </div>
                </div>

                @if(isset($labels))
                @foreach($labels as $label)
                <a href="/notes?labels={{ $label->id }}" class="nav-link {{ request('labels') == $label->id ? 'active' : '' }}">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $label->color }}"></span>
                    <span class="truncate">{{ $label->name }}</span>
                </a>
                @endforeach
                @endif

                <div class="pt-6 pb-2">
                    <span class="sidebar-section-label px-3">Settings</span>
                </div>
                <a href="{{ route('profile.show') }}" class="nav-link {{ request()->is('profile*') ? 'active' : '' }}">
                    <span class="material-icons-outlined text-lg">person</span>
                    <span>Profile</span>
                </a>
                <a href="{{ route('preferences.edit') }}" class="nav-link {{ request()->is('preferences*') ? 'active' : '' }}">
                    <span class="material-icons-outlined text-lg">tune</span>
                    <span>Preferences</span>
                </a>
            </nav>

            {{-- Logout --}}
            <div class="p-3" style="border-top:1px solid var(--sidebar-border);">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link w-full text-left"
                            onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#f87171';this.style.borderColor='rgba(239,68,68,0.2)';this.querySelector('.material-icons-outlined').style.color='#f87171';"
                            onmouseout="this.style.background='';this.style.color='';this.style.borderColor='transparent';this.querySelector('.material-icons-outlined').style.color='';">
                        <span class="material-icons-outlined text-lg">logout</span>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 flex flex-col min-w-0">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 bg-header backdrop-blur-xl border-b border-border px-4 lg:px-6 h-16 flex items-center gap-4">
                {{-- Hamburger/Toggle — luôn hiển thị để người dùng có thể mở sidebar trên mọi màn hình --}}
                <button @click="sidebarOpen = !sidebarOpen"
                        class="p-2 rounded-lg hover:bg-hover transition-colors flex-shrink-0 header-icon-btn"
                        :title="sidebarOpen ? 'Close menu' : 'Open menu'"
                        id="btn-sidebar-toggle">
                    <span class="material-icons-outlined transition-transform duration-200"
                          :style="sidebarOpen && window.innerWidth >= 1024 ? 'transform:rotate(180deg)' : ''"
                    >menu</span>
                </button>

                <div class="flex-1 flex items-center">
                    @yield('header')
                </div>

                <div class="flex items-center gap-2">
                    {{-- Notifications: Pure Vanilla JS — không dùng Alpine để tránh scope + click.outside race condition --}}
                    <div class="relative" id="notif-wrapper">
                        <button class="icon-btn relative" id="btn-notifications" type="button">
                            <span class="material-icons-outlined">notifications</span>
                            <span id="notif-badge"
                                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 items-center justify-center font-bold"
                                  style="display:none;"></span>
                        </button>

                        {{-- Desktop dropdown: dùng position:fixed để tránh bị clip bởi parent overflow --}}
                        <div id="notif-panel-desktop"
                             class="w-80 bg-card rounded-xl shadow-2xl border border-border overflow-hidden z-50"
                             style="display:none;position:fixed;top:64px;right:1rem;z-index:9990;">
                            <div class="p-3 border-b border-border font-semibold text-sm">Notifications</div>
                            <div class="max-h-72 overflow-y-auto" id="notif-list-desktop"></div>
                        </div>

                        {{-- Mobile panel --}}
                        <div id="notif-panel-mobile"
                             style="display:none;position:fixed;top:64px;left:0.75rem;right:0.75rem;z-index:9990;background:var(--color-card);border-radius:1rem;box-shadow:0 20px 60px rgba(0,0,0,0.3);border:1px solid var(--color-border);overflow:hidden;">
                            <div style="padding:0.875rem 1rem;border-bottom:1px solid var(--color-border);font-weight:600;font-size:0.9rem;">Notifications</div>
                            <div style="max-height:65vh;overflow-y:auto;" id="notif-list-mobile"></div>
                        </div>
                    </div>

                    {{-- Dark mode toggle --}}
                    <button id="dark-mode-toggle"
                            @click="darkMode=!darkMode; localStorage.setItem('theme', darkMode?'dark':'light'); @auth fetch('/preferences/theme',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({theme:darkMode?'dark':'light'})}) @endauth" 
                            class="icon-btn header-icon-btn"
                            title="Toggle dark/light mode">
                        <span x-show="!darkMode" class="material-icons-outlined" style="color:var(--color-muted);">dark_mode</span>
                        <span x-show="darkMode" class="material-icons-outlined" style="color:var(--color-muted);display:none;">light_mode</span>
                    </button>


                </div>
            </header>

            {{-- Activation Banner --}}
            @if(!auth()->user()->is_activated)
            <div class="bg-amber-500/10 border-b border-amber-500/30 px-6 py-3 flex items-center justify-between" id="activation-banner">
                <div class="flex items-center gap-2">
                    <span class="material-icons-outlined text-amber-500">warning</span>
                    <span class="text-sm text-amber-600 dark:text-amber-400">Your account is not verified. Please check your email to complete activation.</span>
                </div>
                <form action="/activation/resend" method="POST" class="inline">
                    @csrf
                    <button type="submit" style="background:none;border:none;cursor:pointer;color:#fbbf24;font-size:0.875rem;font-weight:500;text-decoration:underline;padding:0;">Resend Email</button>
                </form>
            </div>
            @endif

            {{-- Flash messages --}}
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition class="mx-4 lg:mx-6 mt-4">
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl px-4 py-3 flex items-center gap-2">
                    <span class="material-icons-outlined text-emerald-500 text-lg">check_circle</span>
                    <span class="text-sm text-emerald-600 dark:text-emerald-400">{{ session('success') }}</span>
                </div>
            </div>
            @endif

            @if(session('warning'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 x-transition class="mx-4 lg:mx-6 mt-4">
                <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-3 flex items-center gap-2">
                    <span class="material-icons-outlined text-amber-500 text-lg">warning</span>
                    <span class="text-sm text-amber-600 dark:text-amber-400">{{ session('warning') }}</span>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                 x-transition class="mx-4 lg:mx-6 mt-4">
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 flex items-center gap-2">
                    <span class="material-icons-outlined text-red-500 text-lg">error</span>
                    <span class="text-sm text-red-600 dark:text-red-400">{{ session('error') }}</span>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="mx-4 lg:mx-6 mt-4">
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3">
                    @foreach($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <span class="material-icons-outlined text-red-500 text-lg">error</span>
                        <span class="text-sm text-red-600 dark:text-red-400">{{ $error }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Page Content --}}
            <div class="flex-1 p-4 lg:p-6">
                @yield('content')
            </div>
        </main>
    </div>
    @else
        @yield('content')
    @endauth

    {{-- Toast container --}}
    <div id="toast-container" class="fixed bottom-6 right-6 z-[100] space-y-2"></div>

    {{-- Label Manager Modal --}}
    @auth
    @include('labels.modal')

    {{-- Password unlock modal — lives in layout so AJAX nav never duplicates it --}}
    <div id="password-modal"
         class="modal-overlay modal-hidden"
         style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:9999;">
        <div class="modal-box bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <span class="material-icons-outlined text-amber-500">lock</span>
                Note is Password Protected
            </h3>
            <form id="unlock-form" onsubmit="unlockNote(event)" data-no-transition>
                <input type="hidden" id="unlock-note-id">
                <input type="hidden" id="unlock-action">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Enter Password</label>
                    <input type="password" id="unlock-password" class="form-input w-full" placeholder="Note password" required>
                    <p id="unlock-error" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closePasswordModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Unlock</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete confirm modal — lives in layout so AJAX nav never duplicates it --}}
    <div id="delete-modal"
         class="modal-overlay modal-hidden"
         style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:9999;">
        <div class="modal-box bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm mx-4 p-6">
            <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                <span class="material-icons-outlined text-red-500">delete</span>
                Delete Note
            </h3>
            <p class="text-muted text-sm mb-6">Are you sure you want to delete this note? This action cannot be undone.</p>
            <div class="flex gap-3 justify-end">
                <button onclick="closeDeleteModal()" class="btn-secondary">Cancel</button>
                <button id="confirm-delete-btn" class="btn-danger">Delete</button>
            </div>
        </div>
    </div>
    @endauth

    <script data-layout="1">
        // CSRF token for AJAX
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Toast notification helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const icons = { success: 'check_circle', error: 'error', info: 'info' };
            const colors = { success: 'emerald', error: 'red', info: 'blue' };
            const color = colors[type] || 'blue';
            
            toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl border animate-slide-up bg-card border-border`;
            toast.innerHTML = `
                <span class="material-icons-outlined text-${color}-500">${icons[type] || 'info'}</span>
                <span class="text-sm font-medium">${message}</span>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        window.showToast = showToast;

        // Đọc flash message từ server và hiện toast
        @if(session('toast_error'))
        setTimeout(() => showToast(@json(session('toast_error')), 'error'), 200);
        @endif
        @if(session('toast_success'))
        setTimeout(() => showToast(@json(session('toast_success')), 'success'), 200);
        @endif
        @if(session('toast_info'))
        setTimeout(() => showToast(@json(session('toast_info')), 'info'), 200);
        @endif

        // AJAX helper — with 12-second timeout so the UI never hangs forever
        async function apiCall(url, method = 'GET', data = null) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 12000);
            const options = {
                method,
                signal: controller.signal,
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };
            if (data) {
                if (data instanceof FormData) {
                    options.body = data;
                } else {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(data);
                }
            }
            try {
                const response = await fetch(url, options);
                clearTimeout(timer);
                let result;
                try {
                    result = await response.json();
                } catch (_) {
                    const raw = await response.text().catch(() => '');
                    console.error('[apiCall] Non-JSON response:', response.status, raw.slice(0, 500));
                    throw { error: `HTTP ${response.status} — server returned non-JSON.`, status: response.status };
                }
                if (!response.ok) throw result;
                return result;
            } catch (e) {
                clearTimeout(timer);
                if (e.name === 'AbortError') throw { error: 'Request timed out. Please try again.' };
                throw e;
            }
        }

    </script>

    {{-- AJAX NAVIGATION ENGINE: slide ngay khi bấm, không progress bar --}}
    <script data-layout="1">
    (function () {
        // Chống re-init khi AJAX engine bị tái thực thi
        if (window._ajaxNavInit) return;
        window._ajaxNavInit = true;

        let navigating = false;

        // ── Tắt transition sidebar để tránh chớp khi Alpine.initTree reset state ──
        function _freezeSidebar() {
            const s = document.getElementById('sidebar');
            const o = document.querySelector('#app-layout > div:first-child'); // overlay
            if (s) s.style.transition = 'none';
            if (o) o.style.transition = 'none';
        }

        // ── Khôi phục sidebar state + bật lại transition ──────────────────────
        function _restoreSidebar(wasOpen) {
            requestAnimationFrame(() => {
                // Set state trước (không có transition → instant, không chớp)
                let restored = false;
                try {
                    if (window.Alpine && Alpine.$data) {
                        const d = Alpine.$data(document.body);
                        if (d && 'sidebarOpen' in d) { d.sidebarOpen = wasOpen; restored = true; }
                    }
                } catch(_) {}
                if (!restored) {
                    try {
                        const bd = document.body._x_dataStack?.[0];
                        if (bd) { bd.sidebarOpen = wasOpen; restored = true; }
                    } catch(_) {}
                }
                if (!restored) {
                    // Fallback: set class trực tiếp
                    const s = document.getElementById('sidebar');
                    if (s) {
                        s.classList.toggle('translate-x-0', wasOpen);
                        s.classList.toggle('-translate-x-full', !wasOpen);
                    }
                }
                // Bật lại transition sau 1 frame (để không thấy snap)
                requestAnimationFrame(() => {
                    const s = document.getElementById('sidebar');
                    const o = document.querySelector('#app-layout > div:first-child');
                    if (s) s.style.transition = '';
                    if (o) o.style.transition = '';
                });
            });
        }

        // ── PREFETCH CACHE — tải trước khi hover ─────────────────────────────
        const _prefetch = {};
        function prefetchHref(href) {
            if (_prefetch[href] || !href || href.startsWith('#')) return;
            _prefetch[href] = fetch(href, { headers: { 'X-Ajax-Nav': '1' } })
                .then(r => r.ok ? r.text() : null)
                .then(html => html ? new DOMParser().parseFromString(html, 'text/html') : null)
                .catch(() => null);
        }
        document.addEventListener('mouseover', e => {
            const link = e.target.closest('a[href]:not([target]):not([download])');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!skipAjax(href, { ctrlKey:false, metaKey:false, shiftKey:false, altKey:false }))
                prefetchHref(href);
        }, { passive: true });

        // Điều kiện bỏ qua AJAX
        function skipAjax(href, e) {
            if (!href || href.startsWith('#') || href.startsWith('javascript')
                || href.startsWith('mailto') || href.startsWith('tel')
                || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return true;
            try { return new URL(href, location.origin).origin !== location.origin; }
            catch(_) { return true; }
        }

        // Đảm bảo có #page-viewport / #page-content
        function ensureViewport() {
            let main = document.querySelector('#app-layout main > .flex-1.p-4') ||
                       document.querySelector('#app-layout main > div.flex-1');
            if (!main || document.getElementById('page-viewport')) return;
            // KHÔNG set overflow-x:hidden trên main — sẽ clip box-shadow của note cards khi hover
            const vp = document.createElement('div');
            vp.id = 'page-viewport';
            const inner = document.createElement('div');
            inner.id = 'page-content';
            while (main.firstChild) inner.appendChild(main.firstChild);
            vp.appendChild(inner);
            main.appendChild(vp);
        }

        // ── NAVIGATE ──────────────────────────────────────────────────────────
        async function navigateTo(href) {
            if (navigating) return;
            navigating = true;
            document.body.classList.add('navigating');

            const currentContent = document.getElementById('page-content');
            const vp = document.getElementById('page-viewport');
            if (!currentContent || !vp) { location.href = href; navigating = false; return; }

            // ① Fetch
            let doc;
            try {
                const cached = _prefetch[href];
                delete _prefetch[href];
                doc = await (cached || fetch(href, { headers: { 'X-Ajax-Nav': '1' } })
                    .then(r => { if (!r.ok) throw r; return r.text(); })
                    .then(html => new DOMParser().parseFromString(html, 'text/html')));
                if (!doc) throw new Error('null doc');
            } catch(_) {
                navigating = false;
                document.body.classList.remove('navigating');
                location.href = href;
                return;
            }

            // Lấy content từ trang mới
            const newInner = doc.querySelector('#page-content') ||
                             doc.querySelector('#app-layout main > .flex-1.p-4') ||
                             doc.querySelector('#app-layout main > div.flex-1');
            if (!newInner) { navigating = false; document.body.classList.remove('navigating'); location.href = href; return; }

            // Cập nhật title + URL
            document.title = doc.title;
            history.pushState({ href }, doc.title, href);

            // Cập nhật active nav links
            doc.querySelectorAll('.nav-link').forEach(nl => {
                const el = document.querySelector(`.nav-link[href="${nl.getAttribute('href')}"]`);
                if (el) el.classList.toggle('active', nl.classList.contains('active'));
            });

            // ★ Cập nhật header bar — MutationObserver tự init elements mới
            const newHeaderSlot = doc.querySelector('#app-layout header > div.flex-1');
            const curHeaderSlot = document.querySelector('#app-layout header > div.flex-1');
            if (newHeaderSlot && curHeaderSlot) {
                document.getElementById('__ajax-header-style')?.remove();
                const headerStyles = Array.from(newHeaderSlot.querySelectorAll('style'))
                    .map(s => s.textContent).join('\n');
                if (headerStyles.trim()) {
                    const styleTag = document.createElement('style');
                    styleTag.id = '__ajax-header-style';
                    styleTag.textContent = headerStyles;
                    document.head.appendChild(styleTag);
                }
                // Thay header — Alpine MutationObserver tự init [x-data] mới (không gọi initTree)
                curHeaderSlot.innerHTML = newHeaderSlot.innerHTML;
            }

            // ③ Tạo new content
            const nextContent = document.createElement('div');
            nextContent.id = 'page-content';
            nextContent.innerHTML = newInner.innerHTML;
            nextContent.style.cssText = 'position:absolute;top:0;left:0;width:100%;transform:translateX(100%);';
            // Dùng x-ignore để ngăn Alpine MutationObserver tự init TRƯỚC khi scripts chạy
            // (nếu Alpine init noteEditor() trước function được define → content rỗng)
            nextContent.setAttribute('x-ignore', '');
            // Khoá overflow CHỈ trong lúc slide animation để chứa nextContent
            vp.style.overflow = 'hidden';
            vp.appendChild(nextContent);

            // ④ Animation
            requestAnimationFrame(() => {
                nextContent.style.transform = '';
                currentContent.classList.add('slide-out-left');
                nextContent.classList.add('slide-in-right');
            });

            // ⑥ Dọn dẹp sau animation
            setTimeout(() => {
                currentContent.remove();
                nextContent.style.cssText = '';
                nextContent.classList.remove('slide-in-right');

                // Re-run inline scripts TRƯỚC để define các functions (vd: noteEditor())
                // 1) Scripts bên trong #page-content
                nextContent.querySelectorAll('script').forEach(old => {
                    const s = document.createElement('script');
                    Array.from(old.attributes).forEach(a => s.setAttribute(a.name, a.value));
                    s.textContent = old.textContent;
                    document.head.appendChild(s).remove();
                });
                // 2) Scripts từ stack('scripts') trong doc mới — nằm ngoài #page-content
                //    push('scripts') render ở body level, cần lấy riêng
                doc.querySelectorAll('body > script:not([data-layout])').forEach(old => {
                    const s = document.createElement('script');
                    Array.from(old.attributes).forEach(a => s.setAttribute(a.name, a.value));
                    s.textContent = old.textContent;
                    document.head.appendChild(s).remove();
                });

                // SAU KHI scripts chạy xong (noteEditor đã được define):
                // Xóa x-ignore và init Alpine — chỉ trên content, KHÔNG trên header/body
                nextContent.removeAttribute('x-ignore');
                if (window.Alpine) {
                    nextContent.querySelectorAll('[x-data]').forEach(el => {
                        try { Alpine.initTree(el); } catch(e) {}
                    });
                }

                // Bỏ overflow:hidden của viewport — cho card hover shadows thoải mái
                vp.style.overflow = '';
                document.body.classList.remove('navigating');
                navigating = false;
            }, 400);
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => setTimeout(ensureViewport, 0));

        // Intercept clicks — chỉ bắt <a href> GET, không bắt forms/buttons
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link || e.defaultPrevented) return;
            const href = link.getAttribute('href');
            if (link.target === '_blank' || link.hasAttribute('download')
                || ('noTransition' in link.dataset) || skipAjax(href, e)) return;
            // When offline + navigating to note editor → use offline router
            // (renders from IDB inside the current app shell, no page reload)
            if (!navigator.onLine && /^\/notes\/([a-z0-9_]+)(\/edit)?$/i.test(href)) {
                e.preventDefault();
                const noteId = href.match(/\/notes\/([a-z0-9_]+)/i)[1];
                const id = /^\d+$/.test(noteId) ? parseInt(noteId, 10) : noteId;
                if (window.offlineRouter) window.offlineRouter.navigateToNote(id);
                return;
            }
            e.preventDefault();
            navigateTo(href);
        }, false);

        // Back/Forward
        window.addEventListener('popstate', e => {
            // When offline, delegate to the offline router for /notes routes
            if (!navigator.onLine && window.offlineRouter) {
                const path = location.pathname;
                if (/^\/notes(\/.*)?$/.test(path)) {
                    window.offlineRouter.handleRoute();
                    return;
                }
            }
            if (e.state?.href) navigateTo(e.state.href);
            else navigateTo(location.href);
        });

        window.addEventListener('pageshow', e => {
            if (e.persisted) document.body.classList.add('app-ready');
        });

        // Expose globally so inline scripts can use smooth nav
        window.ajaxNav = navigateTo;
        // Expose prefetch cache so pages can invalidate stale prefetched content
        window._ajaxPrefetchCache = _prefetch;

        // Expose CSRF token globally for the offline sync engine
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    })();
    </script>
    @stack('scripts')

    <script data-layout="1">
    // ── Service Worker: register + force update + pre-warm pages into cache ──
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            // Use relative path '/sw.js' — NOT asset('sw.js') which can generate
            // HTTP URLs behind Railway's HTTPS reverse proxy → scheme mismatch
            navigator.serviceWorker.register('/sw.js')
                .then(reg => {
                    console.log('[SW-Reg] Registered, scope:', reg.scope);
                    reg.addEventListener('updatefound', () => {
                        const newWorker = reg.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[SW-Reg] New SW installed, sending SKIP_WAITING');
                                newWorker.postMessage({ type: 'SKIP_WAITING' });
                            }
                        });
                    });
                    reg.update(); // check for new version on each load

                    // Pre-warm auth pages + offline shells into SW cache.
                    // This runs AFTER login (inside @@auth block), so the cookies are
                    // valid and the SW will get 200 responses (not 302->login).
                    @auth
                    const warmPages = ['/notes', '/profile', '/profile/edit', '/preferences'];
                    function sendCacheMsg() {
                        if (navigator.serviceWorker.controller) {
                            console.log('[SW-Reg] Sending CACHE_PAGES:', warmPages);
                            navigator.serviceWorker.controller.postMessage({
                                type: 'CACHE_PAGES',
                                pages: warmPages
                            });
                        }
                    }
                    sendCacheMsg();
                    // Also send on controller change (first activation)
                    navigator.serviceWorker.addEventListener('controllerchange', () => {
                        sendCacheMsg();
                    });
                    @endauth
                })
                .catch(err => console.warn('[SW-Reg] Registration failed:', err));
        });
        // Reload when new SW takes control (optional)
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (!window._swReloaded) { window._swReloaded = true; }
        });
    }
    </script>

    @auth
    <script data-layout="1">
    // ═══════════════════════════════════════════════════════
    //  NOTIFICATION SYSTEM — 100% Vanilla JS (no Alpine)
    //  Fixes: scope isolation + @click.outside race condition
    // ═══════════════════════════════════════════════════════
    (function () {
        let _open       = false;
        let _loaded     = false;
        let _unread     = 0;

        const isMobile  = () => window.innerWidth < 640;
        const btn       = () => document.getElementById('btn-notifications');
        const badge     = () => document.getElementById('notif-badge');
        const panelD    = () => document.getElementById('notif-panel-desktop');
        const panelM    = () => document.getElementById('notif-panel-mobile');
        const listD     = () => document.getElementById('notif-list-desktop');
        const listM     = () => document.getElementById('notif-list-mobile');

        // ── Render item HTML ──────────────────────────────
        function itemHtml(n) {
            const date = new Date(n.created_at).toLocaleDateString();
            const opacity = n.is_read ? 'opacity:0.55;' : '';
            // Đọc share_id từ field data — xử lý cả 3 trường hợp:
            // 1. Object (data mới, cast đúng)
            // 2. JSON string đơn (single-encoded)
            // 3. JSON string kép (double-encoded — data cũ bị lỗi lưu)
            let shareId = '';
            try {
                let d = n.data;
                if (typeof d === 'string') d = JSON.parse(d);       // decode lần 1
                if (typeof d === 'string') d = JSON.parse(d);       // decode lần 2 (nếu double-encoded)
                shareId = (d && d.share_id) ? String(d.share_id) : '';
            } catch(e) {}
            const hasLink = !!shareId;
            const dot = !n.is_read
                ? `<span style="width:7px;height:7px;border-radius:50%;background:var(--accent-dim,#16a34a);flex-shrink:0;margin-top:4px;display:inline-block;"></span>`
                : `<span style="width:7px;flex-shrink:0;display:inline-block;"></span>`;
            return `<div data-notif-id="${n.id}" data-read="${n.is_read ? '1':'0'}" data-share-id="${shareId}"
                        style="display:flex;align-items:flex-start;gap:0.5rem;padding:0.75rem 1rem;border-bottom:1px solid var(--color-border);cursor:${hasLink?'pointer':'default'};transition:background 0.12s;${opacity}"
                        onmouseenter="this.style.background='var(--color-hover)'"
                        onmouseleave="this.style.background=''">
                    ${dot}
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:0.875rem;margin:0;font-weight:${n.is_read?'400':'600'}">${escHtml(n.message)}</p>
                        <p style="font-size:0.7rem;color:var(--color-muted);margin:0.25rem 0 0;">${date}${hasLink ? ' &middot; <span style="color:var(--accent-dim,#16a34a);">Open note &rarr;</span>' : ''}</p>
                    </div>
                    </div>`;
        }
        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // ── Render list into both panels ──────────────────
        function renderList(items) {
            const empty = '<div style="padding:1.25rem;text-align:center;font-size:0.875rem;color:var(--color-muted);">No notifications yet</div>';
            const html  = items.length ? items.map(itemHtml).join('') : empty;
            const ld = listD(), lm = listM();
            if (ld) ld.innerHTML = html;
            if (lm) lm.innerHTML = html;
            // Bind click to each item
            [ld, lm].forEach(container => {
                if (!container) return;
                container.querySelectorAll('[data-notif-id]').forEach(el => {
                    el.addEventListener('click', () => markRead(el, items));
                });
            });
        }

        // ── Mark notification as read + navigate ──────────────
        function markRead(el, items) {
            const id      = el.dataset.notifId;
            const read    = el.dataset.read === '1';
            const shareId = el.dataset.shareId || '';
            const navUrl  = shareId ? '/shared/' + shareId + '/view' : '';

            // Đóng panel ngay lập tức
            _open = false;
            const pd = panelD(), pm = panelM();
            if (pd) pd.style.display = 'none';
            if (pm) pm.style.display = 'none';

            // Mark read nếu chưa đọc (fire-and-forget)
            if (!read) {
                fetch('/notifications/' + id + '/read', {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(r => r.json()).then(() => {
                    document.querySelectorAll('[data-notif-id="' + id + '"]').forEach(e => {
                        e.dataset.read = '1';
                        e.style.opacity = '0.55';
                    });
                    _unread = Math.max(0, _unread - 1);
                    updateBadge();
                }).catch(() => {});
            }

            // Navigate đến shared note (kể cả khi đã đọc)
            if (navUrl) {
                if (window.ajaxNav) window.ajaxNav(navUrl);
                else window.location.href = navUrl;
            }
        }

        // ── Fetch notifications ───────────────────────────
        function loadNotifications() {
            if (_loaded) return;
            _loaded = true;
            const ld = listD(), lm = listM();
            const loading = '<div style="padding:1rem;text-align:center;font-size:0.875rem;color:var(--color-muted);">Loading...</div>';
            if (ld) ld.innerHTML = loading;
            if (lm) lm.innerHTML = loading;

            fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (Array.isArray(data)) renderList(data);
                else throw new Error('Invalid response');
            })
            .catch(() => {
                const err = '<div style="padding:1rem;text-align:center;font-size:0.875rem;color:var(--color-muted);">Failed to load notifications</div>';
                if (ld) ld.innerHTML = err;
                if (lm) lm.innerHTML = err;
                _loaded = false; // allow retry
            });
        }

        // ── Toggle panel ─────────────────────────────────
        function togglePanel(e) {
            e.stopPropagation();
            _open = !_open;
            const pd = panelD(), pm = panelM();
            if (_open) {
                if (isMobile()) {
                    if (pm) pm.style.display = 'block';
                    if (pd) pd.style.display = 'none';
                } else {
                    if (pd) pd.style.display = 'block';
                    if (pm) pm.style.display = 'none';
                }
                loadNotifications();
            } else {
                if (pd) pd.style.display = 'none';
                if (pm) pm.style.display = 'none';
            }
        }

        // ── Close on outside click ────────────────────────
        document.addEventListener('click', function(e) {
            if (!_open) return;
            const wrapper = document.getElementById('notif-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                _open = false;
                const pd = panelD(), pm = panelM();
                if (pd) pd.style.display = 'none';
                if (pm) pm.style.display = 'none';
            }
        });

        // ── Badge update ──────────────────────────────────
        function updateBadge() {
            const b = badge();
            if (!b) return;
            if (_unread > 0) {
                b.textContent = _unread > 99 ? '99+' : _unread;
                b.style.display = 'flex';
            } else {
                b.style.display = 'none';
            }
        }

        // ── Fetch unread count on load ────────────────────
        function fetchUnreadCount() {
            fetch('/notifications/unread-count', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(d => { _unread = d.count || 0; updateBadge(); })
            .catch(() => {});
        }

        // ── Init: bind button + fetch count ──────────────
        function initNotifications() {
            const b = btn();
            if (!b || b._notifBound) return;
            b._notifBound = true;
            b.addEventListener('click', togglePanel);
            fetchUnreadCount();
        }

        // Init immediately + re-init after AJAX navigation
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initNotifications);
        } else {
            initNotifications();
        }
        // Re-init sau mỗi lần AJAX nav thay header
        const _origPushState = history.pushState.bind(history);
        history.pushState = function(...args) {
            _origPushState(...args);
            setTimeout(initNotifications, 500);
        };
    })();
    </script>
    @endauth
</body>
</html>
