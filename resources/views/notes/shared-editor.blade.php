@extends('layouts.app')
@section('title', ($share->note->title ?: 'Shared Note') . ' - JOTIFY')

@section('header')
<div class="flex items-center gap-3 flex-1">
    <a href="/shared" class="p-2 rounded-lg hover:bg-hover transition-colors">
        <span class="material-icons-outlined">arrow_back</span>
    </a>

    @if($share->permission === 'edit')
    {{-- Save status — identical to owner editor --}}
    <span id="shared-save-status" class="text-xs text-muted flex items-center gap-1">
        <span class="material-icons-outlined text-sm text-emerald-500">cloud_done</span>
        Saved
    </span>
    {{-- Live collab indicator --}}
    <span id="collab-indicator" class="hidden inline-flex items-center gap-1 text-xs" style="color:var(--accent-dim);">
        <span class="w-2 h-2 rounded-full animate-pulse" style="background:var(--accent-dim);"></span>
        <span id="collab-indicator-text">Live update</span>
    </span>
    @endif

    {{-- Right: permission badge + owner info --}}
    <div class="ml-auto flex items-center gap-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
            {{ $share->permission === 'edit' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
            {{ $share->permission === 'edit' ? '✏ Can Edit' : '👁 Read Only' }}
        </span>
        <span class="text-xs text-muted hidden sm:inline">from {{ $share->owner->display_name }}</span>
    </div>
</div>
@endsection

@section('content')

@if($share->permission === 'edit')
{{-- ══════════════════════════════════════════════════════
     EDITABLE — mirrors editor.blade.php exactly
══════════════════════════════════════════════════════ --}}
<div class="max-w-4xl mx-auto" x-data="sharedEditorData()" id="shared-editor-root">

    {{-- Title --}}
    <input type="text"
           x-model="title"
           @input.debounce.1000ms="autoSave()"
           id="shared-title"
           class="w-full text-2xl lg:text-3xl font-bold bg-transparent border-none outline-none placeholder:text-muted/50 mb-4"
           placeholder="Note title..."
           value="{{ $share->note->title ?? '' }}"
           autocomplete="off">

    {{-- Content textarea --}}
    <textarea x-model="content"
              @input.debounce.1500ms="autoSave()"
              id="shared-content"
              class="w-full min-h-[400px] bg-transparent border-none outline-none resize-none text-body placeholder:text-muted/50 leading-relaxed"
              placeholder="Start writing..."
              style="font-size: {{ ['small'=>'14px','medium'=>'16px','large'=>'18px','x-large'=>'20px'][$preferences->font_size] }}">{{ $share->note->content ?? '' }}</textarea>

    {{-- Image attachments (identical layout to owner editor) --}}
    <div class="mt-6 border-t border-border pt-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold flex items-center gap-2">
                <span class="material-icons-outlined text-lg">image</span>
                Attachments
            </h4>
            <label class="btn-secondary text-sm cursor-pointer">
                <span class="material-icons-outlined text-lg">add_photo_alternate</span>
                Add Image
                <input type="file" accept="image/*" multiple class="hidden"
                       onchange="uploadSharedImages(this.files)" id="shared-image-upload">
            </label>
        </div>
        <div id="shared-images-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($share->note->images as $image)
            <div class="relative group rounded-xl overflow-hidden bg-hover" id="shared-image-{{ $image->id }}">
                <img src="{{ asset('storage/' . $image->image_path) }}"
                     class="w-full h-32 object-cover"
                     alt="{{ $image->original_name }}">
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button onclick="deleteSharedImage({{ $image->id }})"
                            class="p-2 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors">
                        <span class="material-icons-outlined text-lg">delete</span>
                    </button>
                </div>
                <p class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-2 py-1 truncate">
                    {{ $image->original_name }}
                </p>
            </div>
            @endforeach
        </div>
    </div>

</div>

@else
{{-- ══════════════════════════════════════════════════════
     READ-ONLY
══════════════════════════════════════════════════════ --}}
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl lg:text-3xl font-bold mb-4" id="readonly-title">
        {{ $share->note->title ?: 'Untitled' }}
    </h1>
    <div class="prose prose-sm max-w-none text-body leading-relaxed"
         style="font-size: {{ ['small'=>'14px','medium'=>'16px','large'=>'18px','x-large'=>'20px'][$preferences->font_size] }}"
         id="readonly-content">
        {!! nl2br(e($share->note->content)) !!}
    </div>

    @if($share->note->images->count() > 0)
    <div class="mt-6 border-t border-border pt-6">
        <h4 class="text-sm font-semibold mb-4 flex items-center gap-2">
            <span class="material-icons-outlined text-lg">image</span>
            Attachments
        </h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($share->note->images as $image)
            <div class="rounded-xl overflow-hidden bg-hover">
                <img src="{{ asset('storage/' . $image->image_path) }}"
                     class="w-full h-32 object-cover"
                     alt="{{ $image->original_name }}">
                <p class="text-[10px] text-muted px-2 py-1 truncate">{{ $image->original_name }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

@push('scripts')
<script>
const shareId = {{ $share->id }};

@if($share->permission === 'edit')

// ─── Globals (same pattern as ownerLastTitle / ownerLastContent in editor.blade.php) ──
let sharedLastTitle   = @json($share->note->title ?? '');
let sharedLastContent = @json($share->note->content ?? '');
let sharedIsSaving    = false;

// ─── Alpine component — mirrors noteEditor() in editor.blade.php ──────────────
function sharedEditorData() {
    return {
        // Read initial values from DOM (server-rendered) so content shows immediately
        // after AJAX nav without depending on Alpine json-binding timing
        title:   document.getElementById('shared-title')?.value   || @json($share->note->title ?? ''),
        content: document.getElementById('shared-content')?.value || @json($share->note->content ?? ''),
        saving:  false,
        _ready:  false,   // guard: skip autoSave fired before Alpine binds server data

        init() {
            // Mark ready after one tick so x-model has populated title/content
            this.$nextTick(() => { this._ready = true; });
        },

        async autoSave() {
            // Skip if Alpine hasn't fully bound the server values yet
            if (!this._ready) return;
            // Skip if both are empty but note originally had content (stale init fire)
            if (!this.title && !this.content) return;
            if (this.saving) return;
            this.saving      = true;
            sharedIsSaving   = true;

            // Baseline update so polling won't echo our own save back
            sharedLastTitle   = this.title;
            sharedLastContent = this.content;

            const statusEl = document.getElementById('shared-save-status');
            statusEl.innerHTML =
                '<span class="material-icons-outlined text-sm text-amber-500 animate-spin">sync</span> Saving…';

            try {
                const result = await apiCall(`/shared/${shareId}/auto-save`, 'PUT', {
                    title:   this.title,
                    content: this.content,
                });
                statusEl.innerHTML =
                    '<span class="material-icons-outlined text-sm text-emerald-500">cloud_done</span> Saved ' +
                    result.updated_at;
                // Bust prefetch cache so the Shared-with-Me list shows fresh updated_at
                if (window._ajaxPrefetchCache) {
                    delete window._ajaxPrefetchCache['/shared'];
                    delete window._ajaxPrefetchCache[window.location.origin + '/shared'];
                }
            } catch (err) {
                console.error('Save failed:', err);
                statusEl.innerHTML =
                    '<span class="material-icons-outlined text-sm text-red-500">cloud_off</span> ' +
                    'Save failed — <button onclick="window._sharedComp && window._sharedComp.autoSave()" class="underline">retry</button>';
            } finally {
                this.saving    = false;
                sharedIsSaving = false;
            }
        },
    };
}

// Expose Alpine component after initialisation (for retry button & polling)
document.addEventListener('alpine:initialized', () => {
    const root = document.getElementById('shared-editor-root');
    if (root && window.Alpine) {
        try { window._sharedComp = Alpine.$data(root); } catch(e) {}
    }
});

// ─── Fallback autosave — vanilla JS (works even if Alpine fails to init) ──────
// Mirrors the _doSave pattern in owner editor.blade.php exactly.
(function() {
    var _sharedSaveTimer = null;

    function _sharedDoSave() {
        var titleEl   = document.getElementById('shared-title');
        var contentEl = document.getElementById('shared-content');
        var statusEl  = document.getElementById('shared-save-status');
        if (!titleEl || !contentEl) return;
        // Guard: don't overwrite existing content with empty strings
        if (!titleEl.value && !contentEl.value) return;

        sharedIsSaving = true;
        if (statusEl) statusEl.innerHTML = '<span class="material-icons-outlined text-sm text-amber-500 animate-spin">sync</span> Saving…';

        fetch('/shared/' + shareId + '/auto-save', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ title: titleEl.value, content: contentEl.value })
        })
        .then(r => r.json())
        .then(result => {
            sharedLastTitle   = titleEl.value;
            sharedLastContent = contentEl.value;
            if (statusEl) statusEl.innerHTML = '<span class="material-icons-outlined text-sm text-emerald-500">cloud_done</span> Saved ' + (result.updated_at || '');
            if (window._ajaxPrefetchCache) {
                delete window._ajaxPrefetchCache['/shared'];
                delete window._ajaxPrefetchCache[window.location.origin + '/shared'];
            }
        })
        .catch(() => {
            if (statusEl) statusEl.innerHTML = '<span class="material-icons-outlined text-sm text-red-500">cloud_off</span> Save failed — <button onclick="window._sharedFallbackSave && window._sharedFallbackSave()" class="underline">retry</button>';
        })
        .finally(() => { sharedIsSaving = false; });
    }

    function _sharedScheduleSave(delay) {
        clearTimeout(_sharedSaveTimer);
        _sharedSaveTimer = setTimeout(_sharedDoSave, delay);
    }

    function _attachSharedListeners() {
        var titleEl   = document.getElementById('shared-title');
        var contentEl = document.getElementById('shared-content');
        if (titleEl && !titleEl._sharedSaveBound) {
            titleEl.addEventListener('input', function() { _sharedScheduleSave(1000); });
            titleEl._sharedSaveBound = true;
        }
        if (contentEl && !contentEl._sharedSaveBound) {
            contentEl.addEventListener('input', function() { _sharedScheduleSave(1500); });
            contentEl._sharedSaveBound = true;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _attachSharedListeners);
    } else {
        _attachSharedListeners();
    }

    window._sharedFallbackSave = _sharedDoSave;
})();

// ─── Image upload/delete (same as owner's uploadImages / deleteImage) ─────────
async function uploadSharedImages(files) {
    for (const file of files) {
        const fd = new FormData();
        fd.append('image', file);
        try {
            const r = await apiCall(`/shared/${shareId}/upload-image`, 'POST', fd);
            const grid = document.getElementById('shared-images-grid');
            grid.insertAdjacentHTML('beforeend', `
                <div class="relative group rounded-xl overflow-hidden bg-hover" id="shared-image-${r.image.id}">
                    <img src="${r.image.url}" class="w-full h-32 object-cover" alt="${r.image.original_name}">
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <button onclick="deleteSharedImage(${r.image.id})"
                                class="p-2 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors">
                            <span class="material-icons-outlined text-lg">delete</span>
                        </button>
                    </div>
                    <p class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-2 py-1 truncate">
                        ${r.image.original_name}
                    </p>
                </div>
            `);
            showToast('Image uploaded');
        } catch(e) {
            showToast('Upload failed', 'error');
        }
    }
    document.getElementById('shared-image-upload').value = '';
}

async function deleteSharedImage(imageId) {
    try {
        await apiCall(`/shared/${shareId}/images/${imageId}`, 'DELETE');
        document.getElementById(`shared-image-${imageId}`)?.remove();
        showToast('Image removed');
    } catch(e) {
        showToast('Error removing image', 'error');
    }
}

// ─── Real-time collaboration — Laravel Echo WebSocket ────────────────────────
const noteIdForEcho = {{ $share->note->id }};

function applyRemoteUpdate(data) {
    if (sharedIsSaving) return;

    const titleEl   = document.getElementById('shared-title');
    const contentEl = document.getElementById('shared-content');

    // Don't overwrite while the user is actively typing
    if (titleEl   && titleEl   === document.activeElement) return;
    if (contentEl && contentEl === document.activeElement) return;

    const root = document.getElementById('shared-editor-root');
    const comp = (root && window.Alpine)
        ? (() => { try { return Alpine.$data(root); } catch(e) { return null; } })()
        : null;

    if (comp) {
        if (data.title   !== undefined && data.title   !== (comp.title   ?? '')) { comp.title   = data.title;   sharedLastTitle   = data.title; }
        if (data.content !== undefined && data.content !== (comp.content ?? '')) { comp.content = data.content; sharedLastContent = data.content; }
    }

    const indicator = document.getElementById('collab-indicator');
    const indicatorText = document.getElementById('collab-indicator-text');
    if (indicator) {
        if (indicatorText && data.updated_by) indicatorText.textContent = data.updated_by + ' is editing…';
        indicator.classList.remove('hidden');
        setTimeout(() => indicator.classList.add('hidden'), 3000);
    }
}

// Primary: WebSocket via Pusher
let _echoChannelShared = null;
if (window.Echo) {
    try {
        _echoChannelShared = window.Echo.private('note.' + noteIdForEcho)
            .listen('.NoteContentUpdated', (e) => {
                applyRemoteUpdate(e);
            });
    } catch(err) {
        console.warn('Echo subscription failed, falling back to polling:', err);
    }
}

// Fallback: polling (used only when Echo is not connected)
let _sharedPolling = false;
async function pollSharedChanges() {
    if (_echoChannelShared) return; // Echo is active, skip polling
    if (_sharedPolling) return;
    if (sharedIsSaving)  return;
    _sharedPolling = true;
    try {
        const data = await apiCall(`/shared/${shareId}/latest`);
        applyRemoteUpdate(data);
    } catch (e) { /* silent */ }
    finally { _sharedPolling = false; }
}
setInterval(pollSharedChanges, 3000);

// Warn on unsaved changes — identical to owner editor
window.addEventListener('beforeunload', (e) => {
    const root = document.getElementById('shared-editor-root');
    if (!root || !window.Alpine) return;
    try {
        const comp  = Alpine.$data(root);
        const dirty = comp.title !== sharedLastTitle || comp.content !== sharedLastContent;
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    } catch(err) { /* silent */ }
});

@else
// ─── Read-only: Echo WebSocket listener ─────────────────────────────────────
const noteIdReadonly = {{ $share->note->id }};
let lastReadTitle   = @json($share->note->title ?? '');
let lastReadContent = @json($share->note->content ?? '');

function applyReadonlyUpdate(data) {
    const titleEl   = document.getElementById('readonly-title');
    const contentEl = document.getElementById('readonly-content');
    if (titleEl && data.title !== lastReadTitle) {
        titleEl.textContent = data.title || 'Untitled';
        lastReadTitle = data.title;
    }
    if (contentEl && data.content !== lastReadContent) {
        contentEl.innerHTML = data.content
            ? data.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')
            : '';
        lastReadContent = data.content;
    }
}

// Primary: Echo WebSocket
let _echoReadonly = null;
if (window.Echo) {
    try {
        _echoReadonly = window.Echo.private('note.' + noteIdReadonly)
            .listen('.NoteContentUpdated', (e) => { applyReadonlyUpdate(e); });
    } catch(err) { console.warn('Echo readonly failed, fallback to polling'); }
}

// Fallback: polling
async function pollReadOnly() {
    if (_echoReadonly) return;
    try {
        const data = await apiCall(`/shared/${shareId}/latest`);
        applyReadonlyUpdate(data);
    } catch (e) { /* silent */ }
}
setInterval(pollReadOnly, 5000);
@endif
</script>
@endpush
@endsection
