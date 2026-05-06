/**
 * offline-db.js — JOTIFY IndexedDB wrapper (dùng idb library)
 * v3: Offline-first architecture
 *   - IDB is the single source of truth for the UI
 *   - Server is a sync target, not primary data source
 *   - syncStatus on every note: 'synced' | 'pending_create' | 'pending_update'
 *   - Never clear() the store — diff-based merge only
 *   - Sync mutex prevents concurrent sync operations
 */
import { openDB } from 'idb';

const DB_NAME    = 'jotify-offline';
const DB_VERSION = 3;

const STORE_NOTES    = 'notes';
const STORE_CREATES  = 'pending_creates';   // notes tạo offline, chờ sync
const STORE_UPDATES  = 'pending_updates';   // notes sửa offline, chờ sync
const STORE_LABELS   = 'labels';            // cache labels của user
const STORE_PREFS    = 'preferences';       // cache preferences
const STORE_PROFILE  = 'profile';           // offline profile cache + pending update

function getDB() {
    return openDB(DB_NAME, DB_VERSION, {
        upgrade(db, oldVersion) {
            // v1 stores
            if (!db.objectStoreNames.contains(STORE_NOTES)) {
                const store = db.createObjectStore(STORE_NOTES, { keyPath: 'id' });
                store.createIndex('is_pinned',  'is_pinned',  { unique: false });
                store.createIndex('updated_at', 'updated_at', { unique: false });
            }
            // v2 stores
            if (oldVersion < 2) {
                if (!db.objectStoreNames.contains(STORE_CREATES)) {
                    db.createObjectStore(STORE_CREATES, { keyPath: 'tempId' });
                }
                if (!db.objectStoreNames.contains(STORE_UPDATES)) {
                    db.createObjectStore(STORE_UPDATES, { keyPath: 'noteId' });
                }
                if (!db.objectStoreNames.contains(STORE_LABELS)) {
                    db.createObjectStore(STORE_LABELS, { keyPath: 'id' });
                }
                if (!db.objectStoreNames.contains(STORE_PREFS)) {
                    db.createObjectStore(STORE_PREFS, { keyPath: 'key' });
                }
            }
            // v3 stores
            if (oldVersion < 3) {
                if (!db.objectStoreNames.contains(STORE_PROFILE)) {
                    // key = 'current' for cached data, 'pending' for queued update
                    db.createObjectStore(STORE_PROFILE, { keyPath: 'key' });
                }
            }

        },
    });
}

// ──────────────────────────────────────────────────────────────────────────────
// NOTES — offline-first read/write with syncStatus tracking
//
// syncStatus values:
//   'synced'         — note exists on server and IDB is up to date
//   'pending_create' — note created offline, not yet on server
//   'pending_update' — note has local edits not yet synced to server
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Merge server notes into IDB — NEVER clears the store.
 * Server data is authoritative for synced notes.
 * Local-only notes (pending_create) are preserved.
 * Notes with pending_update keep their local title/content.
 *
 * @param {Array} serverNotes — notes from the server API
 */
export async function mergeServerNotesIntoIDB(serverNotes) {
    try {
        const db = await getDB();
        const tx = db.transaction(STORE_NOTES, 'readwrite');

        const serverIds = new Set(serverNotes.map(n => n.id));
        const existingKeys = await tx.store.getAllKeys();

        // 1. Remove notes deleted on server — but NEVER delete local-only notes
        for (const key of existingKeys) {
            if (!serverIds.has(key)) {
                const existing = await tx.store.get(key);
                // Only delete if it was synced (came from server originally)
                if (existing && existing.syncStatus !== 'pending_create') {
                    await tx.store.delete(key);
                }
            }
        }

        // 2. Upsert server notes — but don't overwrite pending local edits
        for (const note of serverNotes) {
            const existing = await tx.store.get(note.id);

            if (existing && existing.syncStatus === 'pending_update') {
                // Keep local title/content (user edited offline), update metadata
                await tx.store.put({
                    ...existing,
                    note_color:    note.note_color   ?? existing.note_color,
                    is_pinned:     note.is_pinned    ?? existing.is_pinned,
                    has_password:  note.has_password  ?? existing.has_password,
                    note_password: note.note_password ?? existing.note_password,
                    is_shared:     note.is_shared     ?? existing.is_shared,
                    labels:        note.labels        ?? existing.labels,
                    // syncStatus stays 'pending_update'
                });
            } else {
                // Fresh write or update a synced note
                await tx.store.put({
                    id:            note.id,
                    title:         note.title         ?? '',
                    content:       note.content       ?? '',
                    note_color:    note.note_color    ?? '#ffffff',
                    is_pinned:     note.is_pinned     ?? false,
                    has_password:  note.has_password   ?? false,
                    note_password: note.note_password  ?? null,
                    is_shared:     note.is_shared      ?? false,
                    labels:        note.labels         ?? [],
                    updated_at:    note.updated_at     ?? '',
                    created_at_ts: note.created_at_ts  ?? 0,
                    syncStatus:    'synced',
                });
            }
        }

        await tx.done;
    } catch (e) {
        console.warn('[IDB] mergeServerNotesIntoIDB failed:', e);
    }
}

/**
 * Backward-compatible wrapper — callers that used saveNotesToIDB
 * now get merge behavior instead of destructive clear.
 */
export async function saveNotesToIDB(notes) {
    return mergeServerNotesIntoIDB(notes);
}

export async function getNotesFromIDB() {
    try {
        const db    = await getDB();
        const notes = await db.getAll(STORE_NOTES);
        notes.sort((a, b) => {
            if (b.is_pinned !== a.is_pinned) return b.is_pinned ? 1 : -1;
            return b.created_at_ts - a.created_at_ts;
        });
        return notes;
    } catch (e) {
        console.warn('[IDB] getNotesFromIDB failed:', e);
        return [];
    }
}

export async function deleteNoteFromIDB(id) {
    try {
        const db = await getDB();
        await db.delete(STORE_NOTES, id);
    } catch (e) {
        console.warn('[IDB] deleteNoteFromIDB failed:', e);
    }
}

export async function updateNoteInIDB(note) {
    try {
        const db = await getDB();
        const existing = await db.get(STORE_NOTES, note.id);
        // Upsert: merge with existing or create with defaults
        // Preserve syncStatus unless explicitly provided
        await db.put(STORE_NOTES, {
            id:           note.id,
            title:        '',
            content:      '',
            note_color:   'none',
            is_pinned:    false,
            has_password: false,
            note_password:null,
            is_shared:    false,
            labels:       [],
            updated_at:   '',
            created_at_ts:0,
            syncStatus:   'synced',
            ...existing,   // override defaults with existing data
            ...note,       // override with new data
        });
    } catch (e) {
        console.warn('[IDB] updateNoteInIDB failed:', e);
    }
}

export async function hasOfflineNotes() {
    try {
        const db    = await getDB();
        const count = await db.count(STORE_NOTES);
        return count > 0;
    } catch (e) {
        return false;
    }
}

/** Get count of notes with pending sync status */
export async function getPendingSyncCount() {
    try {
        const db    = await getDB();
        const notes = await db.getAll(STORE_NOTES);
        return notes.filter(n =>
            n.syncStatus === 'pending_create' || n.syncStatus === 'pending_update'
        ).length;
    } catch (e) {
        return 0;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// SYNC ENGINE — mutex-protected sync of pending changes to server
// ──────────────────────────────────────────────────────────────────────────────

let _syncLock = false;

/**
 * Sync all pending changes (creates + updates) to the server.
 * Protected by a mutex to prevent concurrent sync operations.
 * @param {string} csrfToken — CSRF token for server requests
 * @returns {{ created: number, updated: number, failed: number }}
 */
export async function syncAllPending(csrfToken) {
    if (_syncLock) return { created: 0, updated: 0, failed: 0 };
    _syncLock = true;
    const result = { created: 0, updated: 0, failed: 0 };

    try {
        // ── Sync pending creates ──────────────────────────────────────────
        const creates = await getPendingCreates();
        for (const item of creates) {
            try {
                const res = await fetch('/notes', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) { result.failed++; continue; }

                const data = await res.json().catch(() => ({}));
                const newId = data.id;
                if (newId) {
                    // Push the offline content to the new server note
                    await fetch(`/notes/${newId}/auto-save`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            title:   item.title   || '',
                            content: item.content || '',
                        }),
                    });

                    // Remove from pending_creates queue
                    await removePendingCreate(item.tempId);
                    // Remove the temp note from IDB notes store
                    await deleteNoteFromIDB(item.tempId);
                    result.created++;
                }
            } catch (e) {
                result.failed++;
            }
        }

        // ── Sync pending updates ──────────────────────────────────────────
        const updates = await getPendingUpdates();
        for (const item of updates) {
            try {
                const res = await fetch(`/notes/${item.noteId}/auto-save`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        title:   item.title   || '',
                        content: item.content || '',
                    }),
                });
                if (res.ok) {
                    await removePendingUpdate(item.noteId);
                    // Mark note as synced in IDB
                    await updateNoteInIDB({
                        id:         item.noteId,
                        syncStatus: 'synced',
                    });
                    result.updated++;
                } else {
                    result.failed++;
                }
            } catch (e) {
                result.failed++;
            }
        }
    } finally {
        _syncLock = false;
    }

    return result;
}

// ──────────────────────────────────────────────────────────────────────────────
// OFFLINE QUEUE — pending_creates & pending_updates
// (kept for backward compat; syncAllPending reads from these stores)
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Queue a new note to be created when back online.
 * @param {string} tempId  — client-generated temp ID (e.g. "temp_1714000000000")
 * @param {object} data    — { title, content, note_color, labels, created_at_ts }
 */
export async function queueCreate(tempId, data) {
    try {
        const db = await getDB();
        await db.put(STORE_CREATES, { tempId, ...data, queued_at: Date.now() });
        // Also add to notes cache so UI shows it immediately
        await db.put(STORE_NOTES, {
            id:            tempId,
            title:         data.title        ?? '',
            content:       data.content      ?? '',
            note_color:    data.note_color   ?? '#ffffff',
            is_pinned:     false,
            has_password:  false,
            is_shared:     false,
            labels:        data.labels       ?? [],
            updated_at:    'Just now',
            created_at_ts: data.created_at_ts ?? Math.floor(Date.now() / 1000),
            syncStatus:    'pending_create',
            _pending:      true,
        });
    } catch (e) {
        console.warn('[IDB] queueCreate failed:', e);
    }
}

/**
 * Queue a note update to sync when back online.
 * @param {number|string} noteId
 * @param {object} data  — { title, content }
 */
export async function queueUpdate(noteId, data) {
    try {
        const db = await getDB();
        // Overwrite any existing queued update for this note (latest wins)
        await db.put(STORE_UPDATES, { noteId, ...data, queued_at: Date.now() });
        // Keep local cache fresh + mark as pending
        const existing = await db.get(STORE_NOTES, noteId);
        if (existing) {
            await db.put(STORE_NOTES, {
                ...existing,
                title:      data.title   ?? existing.title,
                content:    data.content ?? existing.content,
                updated_at: 'Pending sync…',
                syncStatus: 'pending_update',
                _pending:   true,
            });
        }
    } catch (e) {
        console.warn('[IDB] queueUpdate failed:', e);
    }
}

/** Get all pending creates */
export async function getPendingCreates() {
    try {
        const db = await getDB();
        return await db.getAll(STORE_CREATES);
    } catch (e) {
        console.warn('[IDB] getPendingCreates failed:', e);
        return [];
    }
}

/** Get all pending updates */
export async function getPendingUpdates() {
    try {
        const db = await getDB();
        return await db.getAll(STORE_UPDATES);
    } catch (e) {
        console.warn('[IDB] getPendingUpdates failed:', e);
        return [];
    }
}

/** Remove a pending create after successful sync */
export async function removePendingCreate(tempId) {
    try {
        const db = await getDB();
        await db.delete(STORE_CREATES, tempId);
    } catch (e) {
        console.warn('[IDB] removePendingCreate failed:', e);
    }
}

/** Remove a pending update after successful sync */
export async function removePendingUpdate(noteId) {
    try {
        const db = await getDB();
        await db.delete(STORE_UPDATES, noteId);
    } catch (e) {
        console.warn('[IDB] removePendingUpdate failed:', e);
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// LABELS — cache labels for offline filtering
// ──────────────────────────────────────────────────────────────────────────────

export async function saveLabelsToIDB(labels) {
    try {
        const db = await getDB();
        const tx = db.transaction(STORE_LABELS, 'readwrite');
        await tx.store.clear();
        for (const label of labels) {
            await tx.store.put({ id: label.id, name: label.name, color: label.color });
        }
        await tx.done;
    } catch (e) {
        console.warn('[IDB] saveLabelsToIDB failed:', e);
    }
}

export async function getLabelsFromIDB() {
    try {
        const db = await getDB();
        return await db.getAll(STORE_LABELS);
    } catch (e) {
        console.warn('[IDB] getLabelsFromIDB failed:', e);
        return [];
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// PREFERENCES — cache user preferences
// ──────────────────────────────────────────────────────────────────────────────

export async function savePreferencesToIDB(prefs) {
    try {
        const db = await getDB();
        await db.put(STORE_PREFS, { key: 'user_prefs', ...prefs });
    } catch (e) {
        console.warn('[IDB] savePreferencesToIDB failed:', e);
    }
}

export async function getPreferencesFromIDB() {
    try {
        const db = await getDB();
        return await db.get(STORE_PREFS, 'user_prefs') ?? null;
    } catch (e) {
        console.warn('[IDB] getPreferencesFromIDB failed:', e);
        return null;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// PROFILE — offline cache + pending update queue
// ──────────────────────────────────────────────────────────────────────────────

/** Save current profile data locally (called when online, key='current') */
export async function saveProfileToIDB(profile) {
    try {
        const db = await getDB();
        await db.put(STORE_PROFILE, { key: 'current', ...profile, savedAt: Date.now() });
    } catch (e) {
        console.warn('[IDB] saveProfileToIDB failed:', e);
    }
}

/** Get cached profile data */
export async function getProfileFromIDB() {
    try {
        const db = await getDB();
        return await db.get(STORE_PROFILE, 'current') ?? null;
    } catch (e) {
        console.warn('[IDB] getProfileFromIDB failed:', e);
        return null;
    }
}

/**
 * Queue a profile update to sync when back online.
 * @param {{ display_name: string, avatarDataUrl: string|null }} data
 */
export async function queueProfileUpdate(data) {
    try {
        const db = await getDB();
        await db.put(STORE_PROFILE, { key: 'pending', ...data, queuedAt: Date.now() });
    } catch (e) {
        console.warn('[IDB] queueProfileUpdate failed:', e);
    }
}

/** Get pending profile update (null if none) */
export async function getProfileQueue() {
    try {
        const db = await getDB();
        return await db.get(STORE_PROFILE, 'pending') ?? null;
    } catch (e) {
        console.warn('[IDB] getProfileQueue failed:', e);
        return null;
    }
}

/** Remove pending profile update after successful sync */
export async function clearProfileQueue() {
    try {
        const db = await getDB();
        await db.delete(STORE_PROFILE, 'pending');
    } catch (e) {
        console.warn('[IDB] clearProfileQueue failed:', e);
    }
}
