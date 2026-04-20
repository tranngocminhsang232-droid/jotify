@extends('layouts.app')
@section('title', ($note->title ?: 'New Note') . ' - JOTIFY')

@section('header')
<div class="flex items-center gap-3 flex-1">
    <a href="/notes" class="p-2 rounded-lg hover:bg-hover transition-colors header-icon-btn">
        <span class="material-icons-outlined" style="color:var(--color-muted);">arrow_back</span>
    </a>
    <span id="save-status" class="text-xs text-muted flex items-center gap-1">
        <span class="material-icons-outlined text-sm text-emerald-500">cloud_done</span>
        Saved
    </span>
    {{-- Live collaboration indicator (shown only when note is shared with edit) --}}
    @if($note->shares->where('permission','edit')->count() > 0)
    <span id="collab-live" class="hidden ml-2 inline-flex items-center gap-1 text-xs text-blue-500">
        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
        Collaborator editing…
    </span>
    @endif
</div>
<div class="flex items-center gap-2">
    {{-- Pin toggle --}}
    <button onclick="togglePinEditor()" class="p-2 rounded-lg hover:bg-hover transition-colors" title="Pin">
        <span class="material-icons-outlined {{ $note->is_pinned ? 'text-amber-500' : 'text-muted' }}" id="pin-icon">push_pin</span>
    </button>

    {{-- Password protection --}}
    <div class="relative" x-data="{ open: false }">
        <button @click="open=!open" class="p-2 rounded-lg hover:bg-hover transition-colors" title="Password Protection">
            <span class="material-icons-outlined {{ $note->has_password ? 'text-red-500' : 'text-muted' }}">{{ $note->has_password ? 'lock' : 'lock_open' }}</span>
        </button>
        <div x-show="open" @click.outside="open=false" class="absolute right-0 mt-2 w-72 bg-card rounded-xl shadow-2xl border border-border p-4 z-50" style="display:none;">
            @if($note->has_password)
                <p class="text-sm font-medium mb-3">Password Protection is ON</p>
                <button onclick="showChangePasswordForm()" class="btn-secondary w-full text-sm mb-2">Change Password</button>
                <button onclick="showRemovePasswordForm()" class="btn-danger w-full text-sm">Remove Password</button>
            @else
                <p class="text-sm font-medium mb-3">Set Password Protection</p>
                <form id="set-password-form" onsubmit="setNotePassword(event)">
                    <input type="password" id="new-note-pass" class="form-input w-full text-sm mb-2" placeholder="Enter password" required>
                    <input type="password" id="confirm-note-pass" class="form-input w-full text-sm mb-2" placeholder="Confirm password" required>
                    <p id="set-pass-error" class="text-red-500 text-xs mb-2 hidden"></p>
                    <button type="submit" class="btn-primary w-full text-sm">Enable Protection</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Share --}}
    <button onclick="openShareModal()" class="p-2 rounded-lg hover:bg-hover transition-colors" title="Share">
        <span class="material-icons-outlined {{ $note->shares && $note->shares->count() > 0 ? 'text-blue-500' : 'text-muted' }}">share</span>
    </button>

    {{-- More actions --}}
    <div class="relative" x-data="{ open: false }">
        <button @click="open=!open" class="p-2 rounded-lg hover:bg-hover transition-colors header-icon-btn">
            <span class="material-icons-outlined" style="color:var(--color-muted);">more_vert</span>
        </button>
        <div x-show="open" @click.outside="open=false" class="absolute right-0 mt-2 w-48 bg-card rounded-xl shadow-2xl border border-border overflow-hidden z-50" style="display:none;">
            <button onclick="confirmDeleteEditor()" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-500 hover:bg-red-500/10 transition-colors">
                <span class="material-icons-outlined text-lg">delete</span>
                Delete Note
            </button>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto" x-data="noteEditor()">
    {{-- Title --}}
    <input type="text" x-model="title" @input.debounce.1000ms="autoSave()"
           class="w-full font-bold bg-transparent border-none outline-none placeholder:text-muted/50 mb-4"
           style="font-size: {{ ['small'=>'1.25rem','medium'=>'1.5rem','large'=>'1.75rem','x-large'=>'2rem'][$preferences->font_size] }}; line-height:1.3;"
           placeholder="Note title..." id="note-title">

    {{-- Labels --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        @foreach($note->labels as $label)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium text-white note-label" 
              style="background-color: {{ $label->color }}" data-label-id="{{ $label->id }}">
            {{ $label->name }}
            <button onclick="removeLabel({{ $label->id }})" class="hover:opacity-70">&times;</button>
        </span>
        @endforeach
        <div class="relative" x-data="{ open: false, search: '' }">
            <button @click="open=!open" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-hover text-muted hover:text-body border border-dashed border-border transition-colors">
                <span class="material-icons-outlined text-sm">add</span>
                Add Label
            </button>
            <div x-show="open" @click.outside="open=false" class="absolute left-0 mt-2 w-52 bg-card rounded-xl shadow-2xl border border-border overflow-hidden z-50" style="display:none;">
                <div class="p-2">
                    <input type="text" x-model="search" class="w-full text-xs bg-hover rounded-lg px-3 py-2 border border-border" placeholder="Search labels...">
                </div>
                <div class="max-h-40 overflow-y-auto">
                    @foreach($labels as $label)
                    <button onclick="toggleLabel({{ $label->id }})" 
                            class="w-full flex items-center gap-2 px-3 py-2 text-xs hover:bg-hover transition-colors"
                            x-show="!search || '{{ strtolower($label->name) }}'.includes(search.toLowerCase())">
                        <span class="w-3 h-3 rounded-full" style="background-color: {{ $label->color }}"></span>
                        <span>{{ $label->name }}</span>
                        @if($note->labels->contains($label->id))
                        <span class="material-icons-outlined text-sm ml-auto" style="color:var(--accent-dim)">check</span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <textarea x-model="content" @input.debounce.1500ms="autoSave()"
              class="w-full min-h-[400px] bg-transparent border-none outline-none resize-none text-body placeholder:text-muted/50 leading-relaxed"
              placeholder="Start writing..." id="note-content"
              style="font-size: {{ ['small'=>'14px','medium'=>'16px','large'=>'18px','x-large'=>'20px'][$preferences->font_size] }}"></textarea>

    {{-- Image attachments --}}
    <div class="mt-6 border-t border-border pt-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold flex items-center gap-2">
                <span class="material-icons-outlined text-lg" style="color:var(--color-muted);">image</span>
                Attachments
            </h4>
            <label class="btn-secondary text-sm cursor-pointer">
                <span class="material-icons-outlined text-lg">add_photo_alternate</span>
                Add Image
                <input type="file" accept="image/*" multiple class="hidden" onchange="uploadImages(this.files)" id="image-upload">
            </label>
        </div>
        <div id="images-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            @foreach($note->images as $image)
            <div class="relative group rounded-xl overflow-hidden bg-hover" id="image-{{ $image->id }}">
                <img src="{{ asset('storage/' . $image->image_path) }}" class="w-full h-32 object-cover" alt="{{ $image->original_name }}">
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button onclick="deleteImage({{ $image->id }})" class="p-2 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors">
                        <span class="material-icons-outlined text-lg">delete</span>
                    </button>
                </div>
                <p class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-2 py-1 truncate">{{ $image->original_name }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Share modal --}}
<div id="share-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-card rounded-2xl shadow-2xl border border-border w-full max-w-md mx-4" style="overflow:hidden;">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4" style="border-bottom:1px solid var(--color-border);">
            <div class="flex items-center gap-2">
                <div style="width:34px;height:34px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;">
                    <span class="material-icons-outlined" style="font-size:1.1rem;color:#3b82f6;">share</span>
                </div>
                <div>
                    <h3 style="font-size:0.95rem;font-weight:700;margin:0;color:var(--color-body-text);">Share Note</h3>
                    <p style="font-size:0.7rem;color:var(--color-muted);margin:0;">Invite others to view or edit</p>
                </div>
            </div>
            <button onclick="closeShareModal()"
                    style="width:28px;height:28px;border-radius:8px;border:none;background:var(--color-hover);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--color-muted);">
                <span class="material-icons-outlined" style="font-size:1rem;">close</span>
            </button>
        </div>

        {{-- Share Form --}}
        <div class="px-6 py-4" style="border-bottom:1px solid var(--color-border);">
            <form onsubmit="shareNote(event)">
                {{-- Email --}}
                <div style="margin-bottom:0.75rem;">
                    <label style="display:block;font-size:0.7rem;font-weight:700;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.35rem;">
                        Recipient Email
                    </label>
                    <input type="email" id="share-email"
                           class="form-input"
                           style="width:100%;font-size:0.875rem;"
                           placeholder="colleague@example.com" required>
                </div>
                {{-- Permission + Send --}}
                <div style="display:flex;gap:0.625rem;align-items:flex-end;">
                    <div style="flex:1;">
                        <label style="display:block;font-size:0.7rem;font-weight:700;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.35rem;">
                            Permission
                        </label>
                        <select id="share-permission" class="form-input" style="width:100%;font-size:0.875rem;">
                            <option value="read">👁  View only</option>
                            <option value="edit">✏️  Can edit</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary"
                            style="font-size:0.8rem;height:2.375rem;padding:0 1.1rem;display:flex;align-items:center;gap:0.375rem;flex-shrink:0;white-space:nowrap;">
                        <span class="material-icons-outlined" style="font-size:0.95rem;">send</span>
                        Invite
                    </button>
                </div>
                <p id="share-error" class="text-red-500" style="font-size:0.75rem;margin-top:0.5rem;display:none;"></p>
            </form>
        </div>

        {{-- Shared Users List --}}
        <div class="px-6 py-3">
            <p style="font-size:0.7rem;font-weight:700;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.5rem;">
                Shared with
            </p>
            <div id="shares-list" style="max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:0.375rem;">
                {{-- Filled dynamically by loadShares() --}}
            </div>
        </div>

    </div>
</div>

{{-- Change note password modal --}}
<div id="change-pass-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm mx-4 p-6">
        <h3 class="text-lg font-bold mb-4" id="change-pass-title">Change Note Password</h3>
        <form id="change-pass-form" onsubmit="submitPasswordAction(event)">
            <input type="hidden" id="pass-action" value="">
            <div id="current-pass-group" class="mb-3">
                <label class="text-sm font-medium mb-1 block">Current Password</label>
                <input type="password" id="pass-current" class="form-input w-full text-sm" placeholder="Current password">
            </div>
            <div id="new-pass-group" class="mb-3">
                <label class="text-sm font-medium mb-1 block">New Password</label>
                <input type="password" id="pass-new" class="form-input w-full text-sm" placeholder="New password">
            </div>
            <div id="confirm-pass-group" class="mb-3">
                <label class="text-sm font-medium mb-1 block">Confirm New Password</label>
                <input type="password" id="pass-confirm" class="form-input w-full text-sm" placeholder="Confirm password">
            </div>
            <p id="pass-error" class="text-red-500 text-xs mb-2 hidden"></p>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('change-pass-modal').classList.add('hidden')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Confirm</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete confirm modal --}}
<div id="editor-delete-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm mx-4 p-6">
        <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
            <span class="material-icons-outlined text-red-500">delete</span>
            Delete Note
        </h3>
        <p class="text-muted text-sm mb-6">Are you sure? This action cannot be undone.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="document.getElementById('editor-delete-modal').classList.add('hidden')" class="btn-secondary">Cancel</button>
            <button onclick="deleteNoteEditor()" class="btn-danger">Delete</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const noteId = {{ $note->id }};
let currentLabels = @json($note->labels->pluck('id'));

// ─── Real-time collaboration: poll for changes made by recipients ─────────────
@if($note->shares->where('permission','edit')->count() > 0)
let ownerLastTitle   = @json($note->title ?? '');
let ownerLastContent = @json($note->content ?? '');
let ownerIsPolling   = false;

async function ownerPollCollabChanges() {
    if (ownerIsPolling) return;
    // Don't poll while owner is actively typing (Alpine handles save)
    const noteEl = document.getElementById('note-content');
    if (noteEl && noteEl === document.activeElement) return;
    const titleEl = document.getElementById('note-title');
    if (titleEl && titleEl === document.activeElement) return;

    ownerIsPolling = true;
    try {
        const data = await apiCall(`/notes/${noteId}/collab-latest`);
        if (!data) { ownerIsPolling = false; return; }

        const titleChanged   = data.title   !== ownerLastTitle;
        const contentChanged = data.content !== ownerLastContent;

        if (titleChanged || contentChanged) {
            // Update Alpine model so the UI reflects new values
            const editorEl = document.querySelector('[x-data]');
            if (editorEl && window.Alpine) {
                try {
                    const comp = Alpine.$data(editorEl);
                    if (titleChanged) {
                        comp.title = data.title;
                        ownerLastTitle = data.title;
                    }
                    if (contentChanged) {
                        comp.content = data.content;
                        ownerLastContent = data.content;
                    }
                } catch(err) { /* Alpine not ready */ }
            }
            // Show live indicator
            const liveEl = document.getElementById('collab-live');
            if (liveEl) {
                liveEl.classList.remove('hidden');
                setTimeout(() => liveEl.classList.add('hidden'), 3000);
            }
        }
    } catch(e) { /* silent */ }
    ownerIsPolling = false;
}

setInterval(ownerPollCollabChanges, 3000);
@endif

function noteEditor() {
    return {
        title: @json($note->title),
        content: @json($note->content ?? ''),
        saving: false,
        async autoSave() {
            this.saving = true;
            // Update the owner's collab baseline (only defined when note has edit-share)
            if (typeof ownerLastTitle   !== 'undefined') ownerLastTitle   = this.title;
            if (typeof ownerLastContent !== 'undefined') ownerLastContent = this.content;
            document.getElementById('save-status').innerHTML = '<span class="material-icons-outlined text-sm text-amber-500 animate-spin">sync</span> Saving...';
            try {
                const result = await apiCall(`/notes/${noteId}/auto-save`, 'PUT', {
                    title: this.title,
                    content: this.content,
                });
                document.getElementById('save-status').innerHTML = '<span class="material-icons-outlined text-sm text-emerald-500">cloud_done</span> Saved ' + result.updated_at;
            } catch(e) {
                document.getElementById('save-status').innerHTML = '<span class="material-icons-outlined text-sm text-red-500">cloud_off</span> Save failed';
            }
            this.saving = false;
        }
    };
}

// Pin toggle in editor
async function togglePinEditor() {
    try {
        const r = await apiCall(`/notes/${noteId}/toggle-pin`, 'POST');
        const icon = document.getElementById('pin-icon');
        icon.classList.toggle('text-amber-500', r.is_pinned);
        icon.classList.toggle('text-muted', !r.is_pinned);
        showToast(r.is_pinned ? 'Note pinned' : 'Note unpinned');
    } catch(e) { showToast('Error', 'error'); }
}

// Images
async function uploadImages(files) {
    for (const file of files) {
        const fd = new FormData();
        fd.append('image', file);
        try {
            const r = await apiCall(`/notes/${noteId}/upload-image`, 'POST', fd);
            const grid = document.getElementById('images-grid');
            grid.insertAdjacentHTML('beforeend', `
                <div class="relative group rounded-xl overflow-hidden bg-hover" id="image-${r.image.id}">
                    <img src="${r.image.url}" class="w-full h-32 object-cover" alt="${r.image.original_name}">
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <button onclick="deleteImage(${r.image.id})" class="p-2 rounded-full bg-red-500 text-white hover:bg-red-600">
                            <span class="material-icons-outlined text-lg">delete</span>
                        </button>
                    </div>
                    <p class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-2 py-1 truncate">${r.image.original_name}</p>
                </div>
            `);
            showToast('Image uploaded');
        } catch(e) { showToast('Upload failed', 'error'); }
    }
    document.getElementById('image-upload').value = '';
}

async function deleteImage(imageId) {
    try {
        await apiCall(`/notes/${noteId}/images/${imageId}`, 'DELETE');
        document.getElementById(`image-${imageId}`)?.remove();
        showToast('Image removed');
    } catch(e) { showToast('Error', 'error'); }
}

// Labels
async function toggleLabel(labelId) {
    const idx = currentLabels.indexOf(labelId);
    if (idx > -1) currentLabels.splice(idx, 1);
    else currentLabels.push(labelId);

    try {
        await apiCall(`/notes/${noteId}/labels`, 'PUT', { labels: currentLabels });
        window.location.reload();
    } catch(e) { showToast('Error updating labels', 'error'); }
}

async function removeLabel(labelId) {
    currentLabels = currentLabels.filter(id => id !== labelId);
    try {
        await apiCall(`/notes/${noteId}/labels`, 'PUT', { labels: currentLabels });
        document.querySelector(`.note-label[data-label-id="${labelId}"]`)?.remove();
        showToast('Label removed');
    } catch(e) { showToast('Error', 'error'); }
}

// Password
async function setNotePassword(e) {
    e.preventDefault();
    const pass = document.getElementById('new-note-pass').value;
    const confirm = document.getElementById('confirm-note-pass').value;
    if (pass !== confirm) {
        document.getElementById('set-pass-error').textContent = 'Passwords do not match';
        document.getElementById('set-pass-error').classList.remove('hidden');
        return;
    }
    try {
        await apiCall(`/notes/${noteId}/set-password`, 'POST', { password: pass, password_confirmation: confirm });
        showToast('Password protection enabled');
        window.location.reload();
    } catch(e) {
        document.getElementById('set-pass-error').textContent = e.error || 'Error';
        document.getElementById('set-pass-error').classList.remove('hidden');
    }
}

function showChangePasswordForm() {
    document.getElementById('pass-action').value = 'change';
    document.getElementById('change-pass-title').textContent = 'Change Note Password';
    document.getElementById('current-pass-group').style.display = '';
    document.getElementById('new-pass-group').style.display = '';
    document.getElementById('confirm-pass-group').style.display = '';
    document.getElementById('change-pass-modal').classList.remove('hidden');
}

function showRemovePasswordForm() {
    document.getElementById('pass-action').value = 'remove';
    document.getElementById('change-pass-title').textContent = 'Remove Password Protection';
    document.getElementById('current-pass-group').style.display = '';
    document.getElementById('new-pass-group').style.display = 'none';
    document.getElementById('confirm-pass-group').style.display = 'none';
    document.getElementById('change-pass-modal').classList.remove('hidden');
}

async function submitPasswordAction(e) {
    e.preventDefault();
    const action = document.getElementById('pass-action').value;
    const current = document.getElementById('pass-current').value;
    const errEl = document.getElementById('pass-error');

    try {
        if (action === 'change') {
            const pw = document.getElementById('pass-new').value;
            const confirm = document.getElementById('pass-confirm').value;
            if (pw !== confirm) {
                errEl.textContent = 'Passwords do not match';
                errEl.classList.remove('hidden');
                return;
            }
            await apiCall(`/notes/${noteId}/change-password`, 'PUT', { current_password: current, password: pw, password_confirmation: confirm });
            showToast('Note password changed');
        } else {
            await apiCall(`/notes/${noteId}/remove-password`, 'POST', { password: current });
            showToast('Password protection removed');
        }
        document.getElementById('change-pass-modal').classList.add('hidden');
        window.location.reload();
    } catch(err) {
        errEl.textContent = err.error || 'Error';
        errEl.classList.remove('hidden');
    }
}

// Share
function openShareModal() {
    document.getElementById('share-modal').classList.remove('hidden');
    loadShares();
}
function closeShareModal() {
    document.getElementById('share-modal').classList.add('hidden');
}

async function loadShares() {
    try {
        const shares = await apiCall(`/notes/${noteId}/shares`);
        const list = document.getElementById('shares-list');
        if (!shares.length) {
            list.innerHTML = `<p style="font-size:0.8rem;color:var(--color-muted);text-align:center;padding:0.75rem 0;">No one has access yet</p>`;
            return;
        }
        list.innerHTML = shares.map(s => {
            const initials = (s.recipient.display_name || s.recipient.email).slice(0,2).toUpperCase();
            const colors = ['#3b82f6','#8b5cf6','#f59e0b','#10b981','#ef4444','#ec4899'];
            const color  = colors[s.id % colors.length];
            return `
            <div style="display:flex;align-items:center;gap:0.625rem;padding:0.5rem 0.625rem;border-radius:10px;background:var(--color-hover);" id="share-${s.id}">
                <div style="width:32px;height:32px;border-radius:50%;background:${color};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.7rem;font-weight:700;color:#fff;">${initials}</div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:0.8rem;font-weight:600;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--color-body-text);">${s.recipient.display_name}</p>
                    <p style="font-size:0.7rem;color:var(--color-muted);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${s.recipient.email}</p>
                </div>
                <select onchange="updateSharePermission(${s.id}, this.value)"
                        style="font-size:0.72rem;padding:0.2rem 0.4rem;border-radius:6px;border:1px solid var(--color-border);background:var(--color-card);color:var(--color-body-text);cursor:pointer;">
                    <option value="read" ${s.permission==='read'?'selected':''}>👁 View</option>
                    <option value="edit" ${s.permission==='edit'?'selected':''}>✏️ Edit</option>
                </select>
                <button onclick="revokeShare(${s.id})"
                        style="width:26px;height:26px;border-radius:7px;border:none;background:rgba(239,68,68,0.1);color:#f87171;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;"
                        title="Remove access">
                    <span class="material-icons-outlined" style="font-size:0.9rem;">person_remove</span>
                </button>
            </div>`;
        }).join('');
    } catch(e) {}
}

async function shareNote(e) {
    e.preventDefault();
    const email = document.getElementById('share-email').value;
    const permission = document.getElementById('share-permission').value;
    const errEl = document.getElementById('share-error');
    errEl.classList.add('hidden');

    try {
        await apiCall(`/notes/${noteId}/share`, 'POST', { email, permission });
        document.getElementById('share-email').value = '';
        showToast('Note shared successfully');
        loadShares();
    } catch(err) {
        errEl.textContent = err.error || 'Error sharing note';
        errEl.classList.remove('hidden');
    }
}

async function updateSharePermission(shareId, permission) {
    try {
        await apiCall(`/shares/${shareId}/permission`, 'PUT', { permission });
        showToast('Permission updated');
    } catch(e) { showToast('Error', 'error'); }
}

async function revokeShare(shareId) {
    try {
        await apiCall(`/shares/${shareId}`, 'DELETE');
        document.getElementById(`share-${shareId}`)?.remove();
        showToast('Share revoked');
    } catch(e) { showToast('Error', 'error'); }
}

// Delete
function confirmDeleteEditor() {
    document.getElementById('editor-delete-modal').classList.remove('hidden');
}
async function deleteNoteEditor() {
    try {
        await apiCall(`/notes/${noteId}`, 'DELETE');
        if (window.ajaxNav) window.ajaxNav('/notes');
        else window.location.href = '/notes';
    } catch(e) { showToast('Error deleting', 'error'); }
}
</script>
@endpush
@endsection
