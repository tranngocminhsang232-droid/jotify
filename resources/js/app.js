import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import {
    saveNotesToIDB, mergeServerNotesIntoIDB,
    getNotesFromIDB, deleteNoteFromIDB, updateNoteInIDB, hasOfflineNotes,
    getNoteById, createNoteOfflineFirst, updateNoteOfflineFirst,
    queueCreate, queueUpdate,
    getPendingCreates, getPendingUpdates,
    removePendingCreate, removePendingUpdate,
    syncAllPending, getPendingSyncCount, getAllNotes,
    saveLabelsToIDB, getLabelsFromIDB,
    savePreferencesToIDB, getPreferencesFromIDB,
    saveProfileToIDB, getProfileFromIDB,
    queueProfileUpdate, getProfileQueue, clearProfileQueue,
} from './offline-db.js';
import {
    handleRoute, navigateToNote, navigateToList,
    createNoteOffline, loadNotesState, renderList, initOfflineEvents,
} from './offline-router.js';
import bcrypt from 'bcryptjs';

// Fonts: Inter bundled via @fontsource in app.css, Material Icons served from public/fonts/

Alpine.plugin(persist);
window.Alpine = Alpine;
Alpine.start();

/* ══════════════════════════════════════════════════════════════
   LARAVEL ECHO — WebSocket realtime via Pusher
   ══════════════════════════════════════════════════════════════ */
window.Pusher = Pusher;

// ── WebSocket (Pusher) — chỉ kết nối khi online, không crash khi offline ──
if (navigator.onLine && import.meta.env.VITE_PUSHER_APP_KEY) {
    try {
        window.Echo = new Echo({
            broadcaster:   'pusher',
            key:           import.meta.env.VITE_PUSHER_APP_KEY,
            cluster:       import.meta.env.VITE_PUSHER_APP_CLUSTER,
            forceTLS:      true,
            authEndpoint:  '/broadcasting/auth',
        });
    } catch (e) {
        console.warn('[Echo] WebSocket unavailable (offline):', e.message);
        window.Echo = null;
    }
} else {
    window.Echo = null;
}

/* ══════════════════════════════════════════════════════════════
   OFFLINE / IndexedDB — expose globally for Blade scripts
   ══════════════════════════════════════════════════════════════ */
window.saveNotesToIDB         = saveNotesToIDB;
window.mergeServerNotesIntoIDB = mergeServerNotesIntoIDB;
window.getNotesFromIDB        = getNotesFromIDB;
window.deleteNoteFromIDB      = deleteNoteFromIDB;
window.updateNoteInIDB        = updateNoteInIDB;
window.hasOfflineNotes        = hasOfflineNotes;
window.getNoteById            = getNoteById;
window.createNoteOfflineFirst = createNoteOfflineFirst;
window.updateNoteOfflineFirst = updateNoteOfflineFirst;
window.queueCreate            = queueCreate;
window.queueUpdate            = queueUpdate;
window.getPendingCreates      = getPendingCreates;
window.getPendingUpdates      = getPendingUpdates;
window.removePendingCreate    = removePendingCreate;
window.removePendingUpdate    = removePendingUpdate;
window.syncAllPending         = syncAllPending;
window.getPendingSyncCount    = getPendingSyncCount;
window.saveLabelsToIDB        = saveLabelsToIDB;
window.getLabelsFromIDB       = getLabelsFromIDB;
window.savePreferencesToIDB   = savePreferencesToIDB;
window.getPreferencesFromIDB  = getPreferencesFromIDB;
// Profile offline helpers
window.saveProfileToIDB   = saveProfileToIDB;
window.getProfileFromIDB  = getProfileFromIDB;
window.queueProfileUpdate = queueProfileUpdate;
window.getProfileQueue    = getProfileQueue;
window.clearProfileQueue  = clearProfileQueue;
// Offline password verification (bcryptjs)
window.bcryptCompareSync  = bcrypt.compareSync;

// Offline router — expose globally (no shouldIntercept — explicit route detection)
window.offlineRouter = {
    handleRoute, navigateToNote, navigateToList,
    createNoteOffline, loadNotesState, renderList,
};

// Initialize online/offline event handlers
initOfflineEvents();

// ── Explicit offline route init ──────────────────────────────
// When the page loads offline on a /notes route, the client-side
// router takes over immediately (no heuristic interception).
document.addEventListener('DOMContentLoaded', () => {
    const isNotesRoute = location.pathname === '/notes'
        || location.pathname.startsWith('/notes/');
    if (!navigator.onLine && isNotesRoute) {
        handleRoute();
    }
});


/* ══════════════════════════════════════════════════════════════
   RIPPLE EFFECT — Material-design style click feedback
   ══════════════════════════════════════════════════════════════ */
function createRipple(event) {
    const btn = event.currentTarget;
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 1.4;
    const x    = event.clientX - rect.left - size / 2;
    const y    = event.clientY - rect.top  - size / 2;

    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px;background:currentColor;`;
    btn.querySelectorAll('.ripple').forEach(r => r.remove());
    btn.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
}

function attachRipple(root = document) {
    root.querySelectorAll('.btn-primary, .btn-secondary, .btn-danger').forEach(btn => {
        if (!btn.dataset.rippleAttached) {
            btn.addEventListener('pointerdown', createRipple);
            btn.dataset.rippleAttached = '1';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => attachRipple());

const observer = new MutationObserver(mutations => {
    mutations.forEach(m => m.addedNodes.forEach(node => {
        if (node.nodeType === 1) attachRipple(node);
    }));
});
observer.observe(document.body, { childList: true, subtree: true });

/* ══════════════════════════════════════════════════════════════
   INSTANT CLICK FEEDBACK — nav links áp dụng active state ngay
   khi bấm, không chờ AJAX xong
   ══════════════════════════════════════════════════════════════ */
document.addEventListener('pointerdown', e => {
    const link = e.target.closest('a.nav-link[href]');
    if (!link || link.classList.contains('active')) return;
    link.style.opacity = '0.7';
    link.style.transform = 'translateX(4px) scale(0.98)';
    setTimeout(() => {
        link.style.opacity = '';
        link.style.transform = '';
    }, 300);
}, { passive: true });

/* ══════════════════════════════════════════════════════════════
   BUTTON LOADING STATE — tránh double-submit
   ══════════════════════════════════════════════════════════════ */
document.addEventListener('submit', e => {
    const form = e.target;
    const btn  = form.querySelector('[type="submit"]');
    if (!btn || btn.dataset.loadingAttached) return;
    btn.dataset.loadingAttached = '1';
    const orig = btn.innerHTML;
    btn.innerHTML = `<span class="material-icons-outlined animate-spin" style="font-size:1.1em;">refresh</span>`;
    btn.disabled = true;
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = orig;
        delete btn.dataset.loadingAttached;
    }, 5000);
}, { passive: true });
