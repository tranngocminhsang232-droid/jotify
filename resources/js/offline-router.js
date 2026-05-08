/**
 * offline-router.js — JOTIFY Offline-First Client-Side Router (Phase 1)
 * ─────────────────────────────────────────────────────────────────────
 * Explicit route detection (no heuristic shouldIntercept).
 * In-memory notesState for fast rendering without repeated IDB reads.
 * SPA routing for /notes and /notes/:id only.
 *
 * Architecture:
 *   IDB → notesState (once) → render from notesState
 *   Search/filter/sort → operate on notesState (no IDB reads)
 *   Sync → update both IDB + notesState
 */

import {
    getNotesFromIDB, getNoteById, createNoteOfflineFirst,
    updateNoteOfflineFirst, syncAllPending, getPendingSyncCount,
} from './offline-db.js';

// ══════════════════════════════════════════════════════════════
// IN-MEMORY STATE
// ══════════════════════════════════════════════════════════════
window.notesState = [];

let _editorSaveTimer = null;
let _currentNoteId   = null;
let _isEditorActive  = false;
let _stateLoaded     = false;

// ══════════════════════════════════════════════════════════════
// STATE MANAGEMENT
// ══════════════════════════════════════════════════════════════

/** Load notes from IDB into notesState (called once, or after sync) */
export async function loadNotesState() {
    try {
        window.notesState = await getNotesFromIDB();
        _stateLoaded = true;
    } catch (e) {
        console.warn('[Router] loadNotesState failed:', e);
        window.notesState = [];
    }
    return window.notesState;
}

/** Find a note in notesState, fallback to IDB */
async function findNote(id) {
    const strId = String(id);
    let note = window.notesState.find(n => String(n.id) === strId);
    if (note) return note;
    // Fallback: IDB direct lookup
    note = await getNoteById(id);
    return note || null;
}

// ══════════════════════════════════════════════════════════════
// ROUTE DETECTION (EXPLICIT — no heuristic)
// ══════════════════════════════════════════════════════════════

/** Parse note ID from URL. Returns null for /notes list. */
function parseNoteId(path) {
    const m = (path || location.pathname).match(/^\/notes\/([a-z0-9_]+)(\/edit)?$/i);
    return m ? m[1] : null;
}

/** Is this a /notes route? */
function isNotesRoute(path) {
    const p = path || location.pathname;
    return p === '/notes' || p.startsWith('/notes/');
}

// ══════════════════════════════════════════════════════════════
// MAIN ENTRY POINT
// ══════════════════════════════════════════════════════════════

/**
 * Handle the current URL — render the appropriate view.
 * Called on page load when offline + notes route, and on popstate.
 */
export async function handleRoute() {
    const path = location.pathname;
    if (!isNotesRoute(path)) return;

    // Ensure state is loaded
    if (!_stateLoaded) await loadNotesState();

    const noteIdStr = parseNoteId(path);
    if (noteIdStr) {
        // /notes/:id — always render the offline editor
        const noteId = /^\d+$/.test(noteIdStr) ? parseInt(noteIdStr, 10) : noteIdStr;
        await renderDetail(noteId);
    } else {
        // /notes list — on first page load (from SW cache), Blade's
        // loadNotesOfflineFirst() handles rendering. On SPA back-navigation,
        // we render the list ourselves with updated IDB data.
        const hasExistingCards = document.getElementById('notes-container');
        if (!hasExistingCards) {
            await renderList();
        }
    }
}

// ══════════════════════════════════════════════════════════════
// NAVIGATION (no page reload)
// ══════════════════════════════════════════════════════════════

/** Navigate to the offline editor for a note. */
export async function navigateToNote(noteId) {
    const url = '/notes/' + noteId + '/edit';
    history.pushState({ noteId, offlineEditor: true }, '', url);
    if (!_stateLoaded) await loadNotesState();
    await renderDetail(noteId);
}

/** Navigate back to the notes list. */
export async function navigateToList() {
    _isEditorActive = false;
    _currentNoteId  = null;
    clearTimeout(_editorSaveTimer);

    if (navigator.onLine) {
        location.href = '/notes';
        return;
    }

    // SPA navigation — instant back with updated content from IDB
    history.pushState({}, '', '/notes');
    // Always reload from IDB so notes are sorted by updated_at
    // (the editor updates IDB but the in-memory array order is stale)
    await loadNotesState();
    await renderList();
}

/** Create a new note offline and open the editor. */
export async function createNoteOffline() {
    const { tempId, note } = await createNoteOfflineFirst({ title: '', content: '' });
    // Add to in-memory state
    window.notesState.unshift(note);
    await navigateToNote(tempId);
}

// ══════════════════════════════════════════════════════════════
// RENDER: NOTES LIST
// ══════════════════════════════════════════════════════════════

export async function renderList() {
    _isEditorActive = false;
    _currentNoteId  = null;
    clearTimeout(_editorSaveTimer);

    const container = getContentContainer();
    if (!container) return;

    // Get pending count for badge
    let pendingCount = 0;
    try { pendingCount = await getPendingSyncCount(); } catch (_) {}

    const notes = window.notesState;
    document.title = 'My Notes - JOTIFY';

    // Restore header to list mode
    restoreHeaderForList();

    // ★ Render the skeleton FIRST so #notes-container exists in the DOM.
    // buildNoteCard() uses getElementById('notes-container') to detect grid vs list mode.
    // If we build cards before the container is in the DOM, it can't find the element
    // and falls back to list-mode cards inside a grid container — breaking layout.
    container.innerHTML = `
    <!-- Offline banner -->
    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;border-radius:0.75rem;margin-bottom:1rem;font-size:0.8rem;font-weight:500;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);color:#f59e0b;">
        <span class="material-icons-outlined" style="font-size:1.1rem;">cloud_off</span>
        <span>You're offline — showing cached notes${pendingCount > 0 ? ` (${pendingCount} change${pendingCount > 1 ? 's' : ''} pending sync)` : ''}</span>
    </div>

    <!-- Search -->
    <div style="position:relative;margin-bottom:1rem;">
        <span class="material-icons-outlined" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:1.1rem;color:var(--color-muted);">search</span>
        <input type="text" id="offline-search-input" placeholder="Search notes..."
               style="width:100%;height:2.5rem;padding-left:2.5rem;padding-right:0.75rem;border-radius:0.75rem;border:1px solid var(--color-border);background:var(--color-hover);color:var(--color-body-text);font-size:0.875rem;outline:none;box-sizing:border-box;"
               autocomplete="off">
    </div>

    <!-- Notes grid -->
    <div id="notes-container" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    </div>`;

    // NOW build cards — #notes-container is in the DOM so buildNoteCard can detect grid mode
    const nc = container.querySelector('#notes-container');
    if (notes.length > 0 && typeof window.buildNoteCard === 'function') {
        nc.innerHTML = notes.map(n => window.buildNoteCard(n)).join('');
    } else if (notes.length > 0) {
        nc.innerHTML = notes.map(n => buildSimpleCard(n)).join('');
    } else {
        nc.innerHTML = `
        <div class="col-span-full" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:5rem 1rem;text-align:center;">
            <div style="width:6rem;height:6rem;border-radius:1.5rem;display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;background:var(--accent-subtle,rgba(34,197,94,0.15));">
                <span class="material-icons-outlined" style="font-size:3rem;color:var(--accent-dim,#16a34a);opacity:0.6;">note_add</span>
            </div>
            <h3 style="font-size:1.125rem;font-weight:600;margin:0 0 0.5rem;">No notes cached</h3>
            <p style="color:var(--color-muted);font-size:0.875rem;margin:0 0 1.5rem;">Visit your notes while online to cache them for offline use.</p>
            <button onclick="window.offlineRouter.createNoteOffline()" class="btn-primary">
                <span class="material-icons-outlined">add</span> Create Note
            </button>
        </div>`;
    }

    // Wire up offline search
    const searchInput = container.querySelector('#offline-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            let filtered = window.notesState;
            if (q) {
                filtered = filtered.filter(n =>
                    (n.title || '').toLowerCase().includes(q) ||
                    (n.content || '').toLowerCase().includes(q)
                );
            }
            const nc = container.querySelector('#notes-container');
            if (nc) {
                if (typeof window.buildNoteCard === 'function') {
                    nc.innerHTML = filtered.length > 0
                        ? filtered.map(n => window.buildNoteCard(n)).join('')
                        : '<div class="col-span-full" style="text-align:center;padding:3rem;color:var(--color-muted);">No matching notes</div>';
                }
            }
        });
    }

    // Wire up note card clicks for offline navigation
    wireOfflineCardClicks(container);
}

/** Wire click handlers on note cards to use the offline router */
function wireOfflineCardClicks(root) {
    root.addEventListener('click', (e) => {
        const card = e.target.closest('.note-card-inner');
        if (!card) return;
        // Don't intercept button clicks inside the card (pin, delete)
        if (e.target.closest('button')) return;

        e.preventDefault();
        e.stopPropagation();

        const idMatch = card.id?.match(/^note-card-(.+)$/);
        if (!idMatch) return;
        const noteId = /^\d+$/.test(idMatch[1]) ? parseInt(idMatch[1], 10) : idMatch[1];

        // Password-protected notes: skip offline editor for now
        if (card.dataset.hasPassword === 'true') {
            if (typeof window.requirePassword === 'function') {
                window.requirePassword(noteId, 'edit');
            }
            return;
        }

        navigateToNote(noteId);
    });
}

/** Fallback simple card builder if buildNoteCard not available */
function buildSimpleCard(note) {
    return `
    <div class="note-card-wrapper">
        <div id="note-card-${note.id}" class="note-card-inner note-card-grid"
             data-has-password="${note.has_password ? 'true' : 'false'}"
             style="display:flex;flex-direction:column;padding:1rem;min-height:120px;background:var(--color-card);border-radius:0.875rem;border:1px solid var(--color-border);cursor:pointer;">
            <h3 style="font-weight:700;font-size:0.9rem;margin:0 0 0.375rem;color:var(--color-body-text);">${escapeHTML(note.title) || 'Untitled'}</h3>
            <p style="font-size:0.8rem;color:var(--color-muted);flex:1;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;">${escapeHTML(note.content)}</p>
            <div style="font-size:0.625rem;color:var(--color-muted);opacity:0.7;margin-top:auto;padding-top:0.4rem;border-top:1px solid var(--color-border);">${note.updated_at || ''}</div>
        </div>
    </div>`;
}

// ══════════════════════════════════════════════════════════════
// RENDER: NOTE DETAIL (Simplified Offline Editor)
// ══════════════════════════════════════════════════════════════

async function renderDetail(noteId) {
    _isEditorActive = true;
    _currentNoteId  = noteId;

    const note = await findNote(noteId);
    const container = getContentContainer();
    if (!container) return;

    // ★ Preserve <style> tags from the content area before replacing.
    // The note card CSS is defined in index.blade.php's @section('content').
    // Lifting styles to <head> keeps them alive for SPA back-navigation.
    _preserveContentStyles(container);

    // Update header for editor mode
    updateHeaderForEditor();

    if (!note) {
        container.innerHTML = buildNotFoundHTML(noteId);
        document.title = 'Note Not Found — JOTIFY';
        return;
    }

    container.innerHTML = buildEditorHTML(note);
    document.title = (note.title || 'Untitled') + ' — JOTIFY';

    // Attach auto-save listeners
    attachEditorListeners(noteId);
}

/**
 * Lift <style> tags from a container into <head> so they survive innerHTML replacement.
 * Uses a data attribute to avoid duplicating styles on multiple navigations.
 */
function _preserveContentStyles(container) {
    // Also check the parent (styles might be siblings of #page-content)
    const searchRoots = [container];
    if (container.parentElement) searchRoots.push(container.parentElement);

    searchRoots.forEach(root => {
        root.querySelectorAll('style').forEach(style => {
            if (style.dataset.preserved) return; // already handled

            const id = style.id || ('preserved-style-' + Math.random().toString(36).substr(2, 8));
            // Skip if already in <head>
            if (document.head.querySelector(`style[data-preserved-id="${id}"]`)) return;

            const clone = style.cloneNode(true);
            clone.dataset.preservedId = id;
            clone.removeAttribute('id'); // avoid duplicate IDs
            document.head.appendChild(clone);

            style.dataset.preserved = '1'; // mark original
            console.log('[Router] Preserved style to <head>:', id);
        });
    });
}

// ── Header management ────────────────────────────────────────

function updateHeaderForEditor() {
    const header = document.querySelector('header');
    if (!header) return;

    const sidebarToggle = header.querySelector('#btn-sidebar-toggle');
    const children = Array.from(header.children);
    children.forEach((child) => {
        if (child === sidebarToggle) return;
        child.remove();
    });

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="flex items-center gap-3 flex-1">
            <button onclick="window.offlineRouter.navigateToList()"
                    class="p-2 rounded-lg hover:bg-hover transition-colors"
                    title="Back to notes" style="border:none;background:none;cursor:pointer;">
                <span class="material-icons-outlined" style="color:var(--color-muted);">arrow_back</span>
            </button>
            <span id="offline-save-status" class="text-xs flex items-center gap-1" style="color:var(--color-muted);">
                <span class="material-icons-outlined text-sm" style="color:#f59e0b;">cloud_off</span>
                Offline — saved locally
            </span>
        </div>
        <div class="flex items-center gap-1"></div>
    `;
    while (wrapper.firstChild) {
        header.appendChild(wrapper.firstChild);
    }
}

function restoreHeaderForList() {
    const header = document.querySelector('header');
    if (!header) return;

    const sidebarToggle = header.querySelector('#btn-sidebar-toggle');
    const children = Array.from(header.children);
    children.forEach((child) => {
        if (child === sidebarToggle) return;
        child.remove();
    });

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="flex-1 flex items-center gap-2 sm:gap-4">
            <button onclick="window.offlineRouter.createNoteOffline()"
                    class="btn-primary" style="padding:0.5rem 1rem;font-size:0.8rem;gap:0.375rem;">
                <span class="material-icons-outlined" style="font-size:1.1rem;">add</span>
                New Note
            </button>
        </div>
        <div class="flex items-center gap-2"></div>
    `;
    while (wrapper.firstChild) {
        header.appendChild(wrapper.firstChild);
    }
}

// ── Editor HTML ──────────────────────────────────────────────

function buildEditorHTML(note) {
    const isPending = note.syncStatus === 'pending_create' || note.syncStatus === 'pending_update';
    const statusText = isPending ? 'Pending sync' : 'Saved locally';
    const statusColor = isPending ? '#f59e0b' : '#22c55e';

    return `
    <div class="offline-editor" style="padding:1rem 1.25rem;max-width:52rem;margin:0 auto;width:100%;">
        <!-- Offline banner -->
        <div style="display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;
                    border-radius:0.75rem;margin-bottom:1.25rem;font-size:0.8rem;font-weight:500;
                    background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);
                    color:#f59e0b;">
            <span class="material-icons-outlined" style="font-size:1.1rem;">cloud_off</span>
            <span>You're offline — changes are saved locally and will sync when you reconnect</span>
        </div>

        <!-- Save status -->
        <div id="editor-save-indicator" style="display:flex;align-items:center;gap:0.375rem;
                    margin-bottom:1rem;font-size:0.75rem;color:var(--color-muted);">
            <span class="material-icons-outlined" style="font-size:0.9rem;color:${statusColor};">
                ${isPending ? 'pending' : 'check_circle'}
            </span>
            <span id="editor-save-text">${statusText}</span>
        </div>

        <!-- Title -->
        <input type="text" id="note-title"
               value="${escapeAttr(note.title || '')}"
               placeholder="Note title..."
               autocomplete="off"
               style="width:100%;font-size:1.6rem;font-weight:700;border:none;outline:none;
                      background:transparent;color:var(--color-body-text);padding:0 0 0.75rem;
                      border-bottom:1px solid var(--color-border);margin-bottom:1rem;
                      font-family:inherit;letter-spacing:-0.01em;box-sizing:border-box;">

        <!-- Content -->
        <textarea id="note-content"
                  placeholder="Start writing your note..."
                  style="width:100%;min-height:calc(100vh - 320px);border:none;outline:none;
                         background:transparent;color:var(--color-body-text);font-size:0.95rem;
                         line-height:1.75;resize:none;padding:0;font-family:inherit;
                         letter-spacing:0.005em;box-sizing:border-box;">${escapeHTML(note.content || '')}</textarea>
    </div>`;
}

function buildNotFoundHTML(noteId) {
    return `
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                min-height:60vh;text-align:center;padding:2rem;">
        <div style="width:5rem;height:5rem;border-radius:1.5rem;display:flex;align-items:center;
                    justify-content:center;margin-bottom:1.5rem;
                    background:rgba(239,68,68,0.1);">
            <span class="material-icons-outlined" style="font-size:2.5rem;color:#ef4444;">search_off</span>
        </div>
        <h2 style="font-size:1.25rem;font-weight:700;margin:0 0 0.5rem;color:var(--color-body-text);">
            Note not found
        </h2>
        <p style="color:var(--color-muted);font-size:0.9rem;margin:0 0 1.5rem;max-width:20rem;">
            This note hasn't been cached locally yet. Visit it while online first.
        </p>
        <button onclick="window.offlineRouter.navigateToList()"
                class="btn-primary" style="gap:0.5rem;">
            <span class="material-icons-outlined" style="font-size:1.1rem;">arrow_back</span>
            Back to Notes
        </button>
    </div>`;
}

// ── Editor listeners ─────────────────────────────────────────

function attachEditorListeners(noteId) {
    const titleEl   = document.getElementById('note-title');
    const contentEl = document.getElementById('note-content');
    if (!titleEl || !contentEl) return;

    function scheduleSave() {
        clearTimeout(_editorSaveTimer);
        updateSaveIndicator('saving');
        _editorSaveTimer = setTimeout(async () => {
            const title   = titleEl.value;
            const content = contentEl.value;

            await updateNoteOfflineFirst(noteId, { title, content });

            // Update notesState in-memory
            const strId = String(noteId);
            const idx = window.notesState.findIndex(n => String(n.id) === strId);
            if (idx !== -1) {
                window.notesState[idx] = { ...window.notesState[idx], title, content, updated_at: 'Just now' };
            }

            updateSaveIndicator('saved');

            // If online, also try to sync to server immediately
            if (navigator.onLine && typeof noteId === 'number') {
                try {
                    const res = await fetch('/notes/' + noteId + '/auto-save', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken || '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ title, content }),
                    });
                    if (res.ok) updateSaveIndicator('synced');
                } catch (_) { /* stay with local save */ }
            }
        }, 800);
    }

    titleEl.addEventListener('input', scheduleSave);
    contentEl.addEventListener('input', scheduleSave);

    // Auto-resize textarea
    function autoResize() {
        contentEl.style.height = 'auto';
        contentEl.style.height = Math.max(contentEl.scrollHeight, 400) + 'px';
    }
    contentEl.addEventListener('input', autoResize);
    setTimeout(autoResize, 50);
}

function updateSaveIndicator(state) {
    const textEl = document.getElementById('editor-save-text');
    const wrapEl = document.getElementById('editor-save-indicator');
    if (!textEl || !wrapEl) return;

    const icon = wrapEl.querySelector('.material-icons-outlined');
    switch (state) {
        case 'saving':
            if (icon) { icon.textContent = 'sync'; icon.style.color = '#f59e0b'; icon.classList.add('animate-spin'); }
            textEl.textContent = 'Saving...';
            break;
        case 'saved':
            if (icon) { icon.textContent = 'check_circle'; icon.style.color = '#22c55e'; icon.classList.remove('animate-spin'); }
            textEl.textContent = 'Saved locally';
            break;
        case 'synced':
            if (icon) { icon.textContent = 'cloud_done'; icon.style.color = '#22c55e'; icon.classList.remove('animate-spin'); }
            textEl.textContent = 'Synced to server';
            break;
    }
}

// ══════════════════════════════════════════════════════════════
// ONLINE / OFFLINE EVENT HANDLING
// ══════════════════════════════════════════════════════════════

export function initOfflineEvents() {
    window.addEventListener('online', async () => {
        console.log('[Router] Back online — syncing pending changes...');
        hideOfflineBanner();
        showSyncBanner();
        try {
            const result = await syncAllPending(window.csrfToken || '');
            console.log('[Router] Sync result:', result);
            if (result.created > 0 || result.updated > 0) {
                showToast(`Synced: ${result.created} created, ${result.updated} updated`);
                // Refresh notesState after sync
                await loadNotesState();
            }
        } catch (e) {
            console.warn('[Router] Sync failed:', e);
        }
        hideSyncBanner();

        if (_isEditorActive) {
            updateSaveIndicator('synced');
        }
    });

    window.addEventListener('offline', () => {
        console.log('[Router] Gone offline');
        showOfflineBanner();
    });

    if (!navigator.onLine) {
        showOfflineBanner();
    }
}

// ── Banners ──────────────────────────────────────────────────

function showOfflineBanner() {
    if (document.getElementById('global-offline-banner')) return;
    const banner = document.createElement('div');
    banner.id = 'global-offline-banner';
    banner.style.cssText = `
        position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;
        display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;
        border-radius:0.75rem;font-size:0.8rem;font-weight:600;
        background:rgba(245,158,11,0.95);color:#fff;
        box-shadow:0 4px 20px rgba(0,0,0,0.2);backdrop-filter:blur(8px);
        animation:slideUp 0.3s ease;
    `;
    banner.innerHTML = `
        <span class="material-icons-outlined" style="font-size:1.1rem;">cloud_off</span>
        You're offline — changes saved locally
    `;
    document.body.appendChild(banner);

    if (!document.getElementById('offline-banner-style')) {
        const style = document.createElement('style');
        style.id = 'offline-banner-style';
        style.textContent = `
            @keyframes slideUp { from { transform: translateX(-50%) translateY(100%); opacity:0; }
                                 to   { transform: translateX(-50%) translateY(0);    opacity:1; } }
        `;
        document.head.appendChild(style);
    }
}

function hideOfflineBanner() {
    document.getElementById('global-offline-banner')?.remove();
}

function showSyncBanner() {
    if (document.getElementById('global-sync-banner')) return;
    const banner = document.createElement('div');
    banner.id = 'global-sync-banner';
    banner.style.cssText = `
        position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;
        display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;
        border-radius:0.75rem;font-size:0.8rem;font-weight:600;
        background:rgba(34,197,94,0.95);color:#fff;
        box-shadow:0 4px 20px rgba(0,0,0,0.2);
    `;
    banner.innerHTML = `
        <span class="material-icons-outlined animate-spin" style="font-size:1.1rem;">sync</span>
        Syncing changes...
    `;
    document.body.appendChild(banner);
}

function hideSyncBanner() {
    document.getElementById('global-sync-banner')?.remove();
}

function showToast(msg) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;
        padding:0.625rem 1.25rem;border-radius:0.75rem;font-size:0.8rem;font-weight:600;
        background:rgba(34,197,94,0.95);color:#fff;
        box-shadow:0 4px 20px rgba(0,0,0,0.2);
        animation:slideUp 0.3s ease;
    `;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ── Popstate (back/forward) ──────────────────────────────────

window.addEventListener('popstate', () => {
    const onNotesRoute = isNotesRoute();
    if (!navigator.onLine && onNotesRoute) {
        handleRoute();
    }
});

// ── Content container helper ─────────────────────────────────

function getContentContainer() {
    return document.getElementById('page-content')
        || document.querySelector('#app-layout main > div.flex-1')
        || document.querySelector('#app-layout main')
        || document.querySelector('main');
}

// ── Utilities ────────────────────────────────────────────────

function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function escapeAttr(str) {
    return (str || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;')
                      .replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
