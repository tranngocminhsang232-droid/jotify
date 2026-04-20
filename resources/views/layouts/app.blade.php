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

        /* Viewport wrapper — clip slide ngang */
        #page-viewport {
            overflow: hidden;
            position: relative;
            width: 100%;
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
        body, aside, header { transition: background-color 0.3s ease, border-color 0.3s ease; }
        aside  { background: var(--color-sidebar) !important; border-color: var(--sidebar-border) !important; }
        header { background: var(--color-header)  !important; border-color: var(--header-border)  !important; }

        /* ── CRITICAL BUTTON STYLES (guaranteed even without Vite) ── */
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem; background: #22c55e; color: #fff !important;
            text-decoration: none;
            border-radius: 0.75rem; font-size: 0.875rem; font-weight: 700; border: none;
            cursor: pointer; letter-spacing: 0.01em; user-select: none;
            box-shadow: 0 4px 14px rgba(34,197,94,0.3);
            transition: background 0.22s ease, box-shadow 0.22s ease, transform 0.28s cubic-bezier(0.34,1.56,0.64,1);
        }
        .btn-primary:hover { background: #16a34a; transform: translateY(-2px) scale(1.025); box-shadow: 0 8px 24px rgba(34,197,94,0.4); }
        .btn-primary:active { background: #16a34a; transform: scale(0.96); box-shadow: none; }
        .dark .btn-primary { background: #22c55e; box-shadow: 0 4px 18px rgba(34,197,94,0.4); }
        .dark .btn-primary:hover { background: #4ade80; }

        .btn-secondary {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem; background: transparent; color: #16a34a;
            border-radius: 0.75rem; font-size: 0.875rem; font-weight: 500; border: 1.5px solid rgba(34,197,94,0.3);
            cursor: pointer; user-select: none;
            transition: background 0.22s ease, border-color 0.22s ease, transform 0.28s cubic-bezier(0.34,1.56,0.64,1);
        }
        .btn-secondary:hover { background: rgba(34,197,94,0.1); border-color: #16a34a; transform: translateY(-1.5px); }
        .btn-secondary:active { transform: scale(0.97); }
        .dark .btn-secondary { color: #4ade80; border-color: rgba(34,197,94,0.35); }
        .dark .btn-secondary:hover { color: #4ade80; background: rgba(34,197,94,0.12); }

        .btn-danger {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem; background: #ef4444; color: #fff;
            border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; border: none;
            cursor: pointer; user-select: none;
            transition: background 0.22s ease, box-shadow 0.22s ease, transform 0.28s cubic-bezier(0.34,1.56,0.64,1);
        }
        .btn-danger:hover { background: #dc2626; transform: translateY(-2px) scale(1.025); box-shadow: 0 8px 20px rgba(239,68,68,0.35); }
        .btn-danger:active { transform: scale(0.96); box-shadow: none; }
    </style>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#22c55e">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-body text-body min-h-screen"
      style="background-color:{{ $htmlBg }};color:{{ $htmlColor }};"
      x-data="{
          darkMode: {{ $isDark ? 'true' : 'false' }},
          sidebarOpen: window.innerWidth > 1024,
          notifOpen: false,
          notifications: [],
          unreadCount: 0
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
                            onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#f87171';this.style.borderColor='rgba(239,68,68,0.2)';"
                            onmouseout="this.style.background='';this.style.color='';this.style.borderColor='transparent';">
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
                    {{-- Notifications --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open=!open; if(open){fetch('/notifications').then(r=>r.json()).then(d=>{notifications=d})}" 
                                class="icon-btn relative"
                                id="btn-notifications">
                            <span class="material-icons-outlined">notifications</span>
                            <span x-show="unreadCount > 0" x-text="unreadCount" 
                                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
                                  style="display:none;"></span>
                        </button>
                        <div x-show="open" @click.outside="open=false" 
                             class="absolute right-0 mt-2 w-80 bg-card rounded-xl shadow-2xl border border-border overflow-hidden z-50"
                             style="display:none;">
                            <div class="p-3 border-b border-border font-semibold">Notifications</div>
                            <div class="max-h-64 overflow-y-auto">
                                <template x-for="notif in notifications" :key="notif.id">
                                    <div class="p-3 hover:bg-hover transition-colors border-b border-border/50 cursor-pointer"
                                         :class="{ 'opacity-60': notif.is_read }"
                                         @click="if(!notif.is_read){fetch('/notifications/'+notif.id+'/read',{method:'PUT',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(()=>{notif.is_read=true;unreadCount=Math.max(0,unreadCount-1)})}">
                                        <p class="text-sm" x-text="notif.message"></p>
                                        <p class="text-xs text-muted mt-1" x-text="new Date(notif.created_at).toLocaleDateString()"></p>
                                    </div>
                                </template>
                                <div x-show="notifications.length === 0" class="p-4 text-center text-muted text-sm">
                                    No notifications yet
                                </div>
                            </div>
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

        // AJAX helper
        async function apiCall(url, method = 'GET', data = null) {
            const options = {
                method,
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
            const response = await fetch(url, options);
            const result = await response.json();
            if (!response.ok) throw result;
            return result;
        }

    </script>

    {{-- AJAX NAVIGATION ENGINE: slide ngay khi bấm, không progress bar --}}
    <script data-layout="1">
    (function () {
        // Chống re-init khi AJAX engine bị tái thực thi
        if (window._ajaxNavInit) return;
        window._ajaxNavInit = true;

        let navigating = false;

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

            const currentContent = document.getElementById('page-content');
            const vp = document.getElementById('page-viewport');
            if (!currentContent || !vp) { location.href = href; navigating = false; return; }

            // ① Fetch trước (dùng prefetch cache nếu có)
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
                location.href = href;
                return;
            }

            // Lấy content từ trang mới
            const newInner = doc.querySelector('#page-content') ||
                             doc.querySelector('#app-layout main > .flex-1.p-4') ||
                             doc.querySelector('#app-layout main > div.flex-1');
            if (!newInner) { navigating = false; location.href = href; return; }

            // Cập nhật title + URL
            document.title = doc.title;
            history.pushState({ href }, doc.title, href);

            // Cập nhật active nav links
            doc.querySelectorAll('.nav-link').forEach(nl => {
                const el = document.querySelector(`.nav-link[href="${nl.getAttribute('href')}"]`);
                if (el) el.classList.toggle('active', nl.classList.contains('active'));
            });

            // ★ Cập nhật header bar (tiêu đề trang, search, buttons...)
            // @yield('header') nằm trong: header > div.flex-1.flex.items-center
            const newHeaderSlot = doc.querySelector('#app-layout header > div.flex-1');
            const curHeaderSlot = document.querySelector('#app-layout header > div.flex-1');
            if (newHeaderSlot && curHeaderSlot) {
                // Inject any <style> tags from the new header into <head> (browser ignores them in innerHTML)
                document.getElementById('__ajax-header-style')?.remove();
                const headerStyles = Array.from(newHeaderSlot.querySelectorAll('style'))
                    .map(s => s.textContent).join('\n');
                if (headerStyles.trim()) {
                    const styleTag = document.createElement('style');
                    styleTag.id = '__ajax-header-style';
                    styleTag.textContent = headerStyles;
                    document.head.appendChild(styleTag);
                }
                curHeaderSlot.innerHTML = newHeaderSlot.innerHTML;
                // Re-init Alpine x-data inside the new header content
                if (window.Alpine) {
                    curHeaderSlot.querySelectorAll('[x-data]').forEach(el => {
                        try { Alpine.initTree(el); } catch(e) {}
                    });
                }
            }

            // ③ Tạo new content — đặt sẵn tại translateX(100%), chưa animate
            const nextContent = document.createElement('div');
            nextContent.id = 'page-content';
            nextContent.innerHTML = newInner.innerHTML;
            nextContent.style.cssText = 'position:absolute;top:0;left:0;width:100%;transform:translateX(100%);';
            vp.appendChild(nextContent);

            // ④ BẮT ĐẦU CẢ 2 ANIMATION CÙNG LÚC sau 1 rAF (layout flush)
            requestAnimationFrame(() => {
                nextContent.style.transform = ''; // bỏ inline, animation CSS takes over
                currentContent.classList.add('slide-out-left');
                nextContent.classList.add('slide-in-right');
            });

            // ⑥ Dọn dẹp sau animation (exit:120ms + enter:220ms = 260ms)
            setTimeout(() => {
                currentContent.remove();
                nextContent.style.cssText = '';
                nextContent.classList.remove('slide-in-right');

                // Re-run scripts inside #page-content (inline page scripts)
                nextContent.querySelectorAll('script').forEach(old => {
                    const s = document.createElement('script');
                    Array.from(old.attributes).forEach(a => s.setAttribute(a.name, a.value));
                    s.textContent = old.textContent;
                    document.head.appendChild(s).remove();
                });




                // Re-init Alpine for new x-data elements
                if (window.Alpine) {
                    nextContent.querySelectorAll('[x-data]').forEach(el => {
                        try { Alpine.initTree(el); } catch(e) { /* ignore */ }
                    });
                }

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
            e.preventDefault();
            navigateTo(href);
        }, false);

        // Back/Forward
        window.addEventListener('popstate', e => {
            if (e.state?.href) navigateTo(e.state.href);
            else navigateTo(location.href);
        });

        window.addEventListener('pageshow', e => {
            if (e.persisted) document.body.classList.add('app-ready');
        });

        // Expose globally so inline scripts can use smooth nav
        window.ajaxNav = navigateTo;
    })();
    </script>
    @stack('scripts')

    <script data-layout="1">
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}')
                    .then(reg => console.log('SW registered:', reg.scope))
                    .catch(err => console.log('SW registration failed:', err));
            });
        }
    </script>
</body>
</html>
