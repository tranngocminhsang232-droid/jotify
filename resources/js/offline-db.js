/**
 * offline-db.js — JOTIFY IndexedDB wrapper (dùng idb library)
 * Lưu notes vào IndexedDB để đọc được khi offline.
 */
import { openDB } from 'idb';

const DB_NAME    = 'jotify-offline';
const DB_VERSION = 1;
const STORE      = 'notes';

/** Mở (hoặc tạo) database */
function getDB() {
    return openDB(DB_NAME, DB_VERSION, {
        upgrade(db) {
            if (!db.objectStoreNames.contains(STORE)) {
                const store = db.createObjectStore(STORE, { keyPath: 'id' });
                store.createIndex('is_pinned',  'is_pinned',  { unique: false });
                store.createIndex('updated_at', 'updated_at', { unique: false });
            }
        },
    });
}

/**
 * Lưu toàn bộ mảng notes vào IDB (replace all).
 * @param {Array} notes  — mảng note objects từ server
 */
export async function saveNotesToIDB(notes) {
    try {
        const db = await getDB();
        const tx = db.transaction(STORE, 'readwrite');
        // Clear old data và insert fresh
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

/**
 * Đọc tất cả notes từ IDB, sort: pinned first, sau đó by created_at desc.
 * @returns {Promise<Array>}
 */
export async function getNotesFromIDB() {
    try {
        const db    = await getDB();
        const notes = await db.getAll(STORE);
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

/**
 * Xoá 1 note khỏi IDB (sau khi delete thành công hoặc offline queue sync).
 * @param {number} id
 */
export async function deleteNoteFromIDB(id) {
    try {
        const db = await getDB();
        await db.delete(STORE, id);
    } catch (e) {
        console.warn('[IDB] deleteNoteFromIDB failed:', e);
    }
}

/**
 * Cập nhật 1 note trong IDB (sau khi auto-save offline).
 * @param {object} note
 */
export async function updateNoteInIDB(note) {
    try {
        const db = await getDB();
        const existing = await db.get(STORE, note.id);
        if (existing) {
            await db.put(STORE, { ...existing, ...note });
        }
    } catch (e) {
        console.warn('[IDB] updateNoteInIDB failed:', e);
    }
}

/** Kiểm tra IDB có notes không (để biết đã sync lần nào chưa) */
export async function hasOfflineNotes() {
    try {
        const db    = await getDB();
        const count = await db.count(STORE);
        return count > 0;
    } catch (e) {
        return false;
    }
}
