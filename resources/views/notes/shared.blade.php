@extends('layouts.app')
@section('title', 'Shared with Me - JOTIFY')

@section('header')
<div class="flex-1 flex items-center gap-3" id="shared-header-toolbar">
    <h1 class="text-lg font-bold flex items-center gap-2 flex-1 min-w-0">
        <span class="material-icons-outlined" style="color:var(--accent-dim);">people</span>
        <span class="hidden sm:inline">Shared with Me</span>
        <span class="sm:hidden">Shared</span>
        @if($sharedNotes->count() > 0)
        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold text-white"
              style="background:var(--accent-dim);">{{ $sharedNotes->count() }}</span>
        @endif
    </h1>

    {{-- View Toggle --}}
    <div class="view-toggle-wrap flex flex-shrink-0" id="shared-view-toggle">
        <div class="view-toggle-pill" id="shared-toggle-pill"></div>
        <button onclick="switchSharedView('grid')" id="shared-btn-grid"
                class="view-toggle-btn" aria-label="Grid view">
            <span class="material-icons-outlined" style="font-size:1.125rem;">grid_view</span>
        </button>
        <button onclick="switchSharedView('list')" id="shared-btn-list"
                class="view-toggle-btn" aria-label="List view">
            <span class="material-icons-outlined" style="font-size:1.125rem;">view_list</span>
        </button>
    </div>
</div>

<style>
.view-toggle-wrap {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--color-hover);
    border: 1px solid var(--color-border);
    border-radius: 0.75rem;
    padding: 3px;
    gap: 2px;
}
.view-toggle-pill {
    position: absolute;
    top: 3px;
    left: 3px;
    width: var(--pill-w, 34px);
    height: var(--pill-h, 34px);
    background: var(--accent-dim, #16a34a);
    border-radius: 0.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    transition: transform 0.26s cubic-bezier(0.34, 1.26, 0.64, 1);
    pointer-events: none;
    z-index: 0;
}
.view-toggle-btn {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 0.5rem;
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--color-muted);
    transition: color 0.22s ease;
}
.view-toggle-btn:not(.active):hover { color: var(--color-body-text); }
.view-toggle-btn.active { color: #ffffff; }
</style>
@endsection

@section('content')

{{-- Password unlock modal — same pattern as delete-modal in index.blade.php --}}
<div id="shared-password-modal"
     class="modal-overlay modal-hidden"
     style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.55);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:9999;">
    <div class="modal-box bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm mx-4 p-6"
         onclick="event.stopPropagation()">
        <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
            <span class="material-icons-outlined text-amber-500">lock</span>
            Password Protected
        </h3>
        <p class="text-muted text-sm mb-4">Enter the password to access this note.</p>
        <form id="shared-unlock-form" onsubmit="submitSharedUnlock(event)">
            <input type="hidden" id="shared-unlock-share-id">
            <div class="mb-4">
                <input type="password" id="shared-unlock-password"
                       class="form-input w-full" placeholder="Note password…"
                       required autocomplete="off">
                <p id="shared-unlock-error" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeSharedPasswordModal()" class="btn-secondary">Cancel</button>
                <button type="submit" id="shared-unlock-btn" class="btn-primary flex items-center gap-1.5">
                    <span class="material-icons-outlined text-sm">lock_open</span>
                    Unlock
                </button>
            </div>
        </form>
    </div>
</div>

@if($sharedNotes->count() > 0)
{{-- Notes container — class managed by JS --}}
<div id="shared-notes-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($sharedNotes as $share)

    {{-- Check if password-protected — show lock prompt instead of direct link --}}
    @if($share->note->has_password)
    <div class="shared-card-wrapper cursor-pointer"
         onclick="openSharedPasswordModal({{ $share->id }})">
    @else
    <a href="/shared/{{ $share->id }}/view" class="shared-card-wrapper no-underline block">
    @endif

        {{-- Grid card --}}
        <div class="shared-card-grid bg-card rounded-xl border border-border p-4 transition-all h-full
                    hover:border-[var(--accent-border)] hover:shadow-lg hover:-translate-y-1">

            {{-- Top row: icons + permission badge --}}
            <div class="flex items-center gap-1.5 mb-2">
                <span class="material-icons-outlined text-base" style="color:var(--accent-dim);">share</span>
                @if($share->note->has_password)
                <span class="material-icons-outlined text-amber-500" style="font-size:14px;" title="Password protected">lock</span>
                @endif
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                    {{ $share->permission === 'edit' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
                    {{ $share->permission === 'edit' ? '✏ Can Edit' : 'Read Only' }}
                </span>
            </div>

            {{-- Title — shown even when locked --}}
            <h3 class="font-semibold text-sm mb-1 line-clamp-2 leading-snug">
                {{ $share->note->title ?: 'Untitled' }}
            </h3>

            {{-- Preview (hidden when locked) --}}
            @if(!$share->note->has_password)
            <p class="text-xs text-muted line-clamp-3 mb-3 flex-1">
                {{ \Str::limit(strip_tags($share->note->content), 120) }}
            </p>
            @else
            <p class="text-xs text-muted mb-3 italic">🔒 Content is password protected</p>
            @endif

            {{-- Image thumbnail (hidden when locked) --}}
            @if(!$share->note->has_password && $share->note->images->count() > 0)
            <div class="note-thumb-wrap" style="margin-bottom:0.5rem;">
                <img src="{{ asset('storage/' . $share->note->images->first()->image_path) }}"
                     alt="Attachment"
                     class="w-full rounded-lg object-cover" style="max-height:120px;"
                     loading="lazy">
                @if($share->note->images->count() > 1)
                <span class="text-[10px] text-muted">+{{ $share->note->images->count() - 1 }} more</span>
                @endif
            </div>
            @endif

            {{-- Footer: owner info + last-modified time --}}
            <div class="flex items-center gap-2 pt-2 border-t border-border/50 mt-auto">
                <img src="{{ $share->owner->avatar_url }}" class="w-5 h-5 rounded-full flex-shrink-0" alt="">
                <span class="text-[11px] text-muted truncate flex-1">{{ $share->owner->display_name }}</span>
                {{-- Show note's updated_at, not share's shared_at --}}
                <span class="text-[10px] text-muted flex-shrink-0">{{ ($share->note->updated_at ?? $share->shared_at)->diffForHumans() }}</span>
            </div>
        </div>

        {{-- List card (hidden by default, toggled via JS) --}}
        <div class="shared-card-list hidden bg-card rounded-xl border border-border px-4 py-3 transition-all
                    hover:border-[var(--accent-border)] hover:shadow-md items-center gap-3">
            <div class="flex-shrink-0 flex items-center gap-1">
                <span class="material-icons-outlined text-base" style="color:var(--accent-dim);font-size:18px;">share</span>
                @if($share->note->has_password)
                <span class="material-icons-outlined text-amber-500" style="font-size:14px;">lock</span>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 min-w-0">
                    <h3 class="font-semibold text-sm truncate flex-1 min-w-0">
                        {{ $share->note->title ?: 'Untitled' }}
                    </h3>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-medium flex-shrink-0
                        {{ $share->permission === 'edit' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
                        {{ $share->permission === 'edit' ? 'Edit' : 'View' }}
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                    <img src="{{ $share->owner->avatar_url }}" class="w-4 h-4 rounded-full flex-shrink-0" alt="">
                    <span class="text-[11px] text-muted truncate">{{ $share->owner->display_name }}</span>
                    {{-- Show note's updated_at, not share's shared_at --}}
                    <span class="text-[10px] text-muted ml-auto flex-shrink-0">{{ ($share->note->updated_at ?? $share->shared_at)->diffForHumans() }}</span>
                </div>
            </div>
        </div>

    @if($share->note->has_password)
    </div>
    @else
    </a>
    @endif

    @endforeach
</div>
@else
<div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="w-24 h-24 rounded-3xl flex items-center justify-center mb-6" style="background:var(--accent-subtle);">
        <span class="material-icons-outlined text-5xl" style="color:var(--accent-dim);opacity:0.6;">folder_shared</span>
    </div>
    <h3 class="text-lg font-semibold mb-2">No shared notes</h3>
    <p class="text-muted text-sm">Notes shared with you will appear here</p>
</div>
@endif

<style>
/* Modal transitions */
.modal-overlay { transition: opacity 0.2s cubic-bezier(0.4,0,0.2,1); }
.modal-overlay.modal-hidden { opacity:0; pointer-events:none; }
.modal-box { transition: opacity 0.22s cubic-bezier(0.4,0,0.2,1), transform 0.28s cubic-bezier(0.34,1.3,0.64,1); }
.modal-overlay.modal-hidden .modal-box { opacity:0; transform:scale(0.92) translateY(12px); }
.modal-overlay:not(.modal-hidden) .modal-box { opacity:1; transform:scale(1) translateY(0); }

/* Grid card flex column */
.shared-card-grid { display:flex; flex-direction:column; }

/* List card active */
#shared-notes-container.list-mode .shared-card-grid { display:none; }
#shared-notes-container.list-mode .shared-card-list { display:flex; }
#shared-notes-container:not(.list-mode) .shared-card-list { display:none; }

/* List mode container */
#shared-notes-container.list-mode { display:flex; flex-direction:column; gap:0.5rem; }
</style>

@push('scripts')
<script>
(function() {
    // ─── View Toggle ────────────────────────────────────────────────────────
    const STORAGE_KEY = 'sharedViewMode';

    function switchSharedView(mode) {
        const container  = document.getElementById('shared-notes-container');
        const btnGrid    = document.getElementById('shared-btn-grid');
        const btnList    = document.getElementById('shared-btn-list');
        const pill       = document.getElementById('shared-toggle-pill');
        if (!container || !btnGrid || !btnList) return;

        if (mode === 'grid') {
            container.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4';
            container.classList.remove('list-mode');
            pill.style.transform = 'translateX(0px)';
            btnGrid.classList.add('active'); btnList.classList.remove('active');
        } else {
            container.classList.add('list-mode');
            pill.style.transform = 'translateX(calc(100% + 2px))';
            btnList.classList.add('active'); btnGrid.classList.remove('active');
        }
        try { localStorage.setItem(STORAGE_KEY, mode); } catch(e) {}
    }
    window.switchSharedView = switchSharedView;

    // Restore saved view preference
    (function restoreView() {
        let saved = 'grid';
        try { saved = localStorage.getItem(STORAGE_KEY) || 'grid'; } catch(e) {}
        switchSharedView(saved);

        // Sync pill size after layout settles
        requestAnimationFrame(() => {
            const btn  = document.getElementById('shared-btn-grid');
            const pill = document.getElementById('shared-toggle-pill');
            if (!btn || !pill) return;
            const sz = btn.getBoundingClientRect();
            pill.style.setProperty('--pill-w', sz.width + 'px');
            pill.style.setProperty('--pill-h', sz.height + 'px');
            pill.style.width  = sz.width  + 'px';
            pill.style.height = sz.height + 'px';
        });
    })();

    // ─── Password Modal for locked shared notes ──────────────────────────────
    function openSharedPasswordModal(shareId) {
        const modal = document.getElementById('shared-password-modal');
        if (!modal) return;
        document.getElementById('shared-unlock-share-id').value = shareId;
        document.getElementById('shared-unlock-password').value = '';
        const err = document.getElementById('shared-unlock-error');
        if (err) { err.textContent = ''; err.classList.add('hidden'); }

        // Teleport modal to <body> so position:fixed is relative to viewport,
        // not #page-content which has will-change:transform (creates new containing block)
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        modal.style.display = 'flex';
        modal.getBoundingClientRect();  // force reflow
        modal.classList.remove('modal-hidden');
        setTimeout(() => document.getElementById('shared-unlock-password')?.focus(), 200);
    }
    window.openSharedPasswordModal = openSharedPasswordModal;

    function closeSharedPasswordModal() {
        const modal = document.getElementById('shared-password-modal');
        if (!modal) return;
        modal.classList.add('modal-hidden');
        setTimeout(() => { modal.style.display = 'none'; }, 220);
    }
    window.closeSharedPasswordModal = closeSharedPasswordModal;

    // Close on backdrop click
    document.getElementById('shared-password-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeSharedPasswordModal();
    });

    async function submitSharedUnlock(e) {
        e.preventDefault();
        const shareId  = document.getElementById('shared-unlock-share-id').value;
        const password = document.getElementById('shared-unlock-password').value;
        const errEl    = document.getElementById('shared-unlock-error');
        const btn      = document.getElementById('shared-unlock-btn');

        errEl.classList.add('hidden');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-icons-outlined animate-spin text-sm">sync</span> Checking…';

        try {
            const res = await fetch(`/shared/${shareId}/unlock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                },
                body: JSON.stringify({ password }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                // Correct password — navigate to note
                closeSharedPasswordModal();
                window.location.href = `/shared/${shareId}/view`;
            } else {
                errEl.textContent = data.error || 'Incorrect password.';
                errEl.classList.remove('hidden');
                document.getElementById('shared-unlock-password').value = '';
                document.getElementById('shared-unlock-password').focus();
            }
        } catch(err) {
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-icons-outlined text-sm">lock_open</span> Unlock';
        }
    }
    window.submitSharedUnlock = submitSharedUnlock;
    // ─── Auto-open password modal if redirected from a locked shared note ───
    @if(session('shared_password_required'))
        openSharedPasswordModal({{ session('shared_password_required') }});
    @endif
})();
</script>
@endpush
@endsection
