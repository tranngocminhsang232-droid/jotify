/**
 * offline-db.js — JOTIFY IndexedDB wrapper (dùng idb library)
 * v2: Thêm offline queue, labels cache, preferences cache
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
// NOTES — read/write cached notes
// ──────────────────────────────────────────────────────────────────────────────

export async function saveNotesToIDB(notes) {
    try {
        const db = await getDB();
        const tx = db.transaction(STORE_NOTES, 'readwrite');
        await tx.store.clear();
        for (const note of notes) {
            await tx.store.put({
                id:           note.id,
                title:        note.title        ?? '',
                content:      note.content      ?? '',
                note_color:   note.note_color   ?? '#ffffff',
                is_pinned:    note.is_pinned    ?? false,
                has_password: note.has_password ?? false,
                is_shared:    note.is_shared    ?? false,
                labels:       note.labels       ?? [],
                updated_at:   note.updated_at   ?? '',
                created_at_ts:note.created_at_ts ?? 0,
            });
        }
        await tx.done;
    } catch (e) {
        console.warn('[IDB] saveNotesToIDB failed:', e);
    }
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
        if (existing) {
            await db.put(STORE_NOTES, { ...existing, ...note });
        }
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

// ──────────────────────────────────────────────────────────────────────────────
// OFFLINE QUEUE — pending_creates & pending_updates
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
            id:           tempId,
            title:        data.title        ?? '',
            content:      data.content      ?? '',
            note_color:   data.note_color   ?? '#ffffff',
            is_pinned:    false,
            has_password: false,
            is_shared:    false,
            labels:       data.labels       ?? [],
            updated_at:   'Just now',
            created_at_ts: data.created_at_ts ?? Math.floor(Date.now() / 1000),
            _pending:     true,   // flag để UI hiện "Pending sync"
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
        // Keep local cache fresh
        const existing = await db.get(STORE_NOTES, noteId);
        if (existing) {
            await db.put(STORE_NOTES, {
                ...existing,
                title:   data.title   ?? existing.title,
                content: data.content ?? existing.content,
                updated_at: 'Pending sync…',
                _pending: true,
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
