{{-- Label Management Modal --}}
<div id="label-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center hidden"
     x-data="labelManager()"
     @open-label-manager.window="loadLabels()">
    <div class="bg-card rounded-2xl shadow-2xl border border-border w-full max-w-md mx-4 overflow-hidden">
        <div class="p-5 border-b border-border flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <span class="material-icons-outlined" style="color:var(--accent-dim)">label</span>
                Manage Labels
            </h3>
            <button onclick="closeLabelManager()" class="p-1 rounded-lg hover:bg-hover transition-colors">
                <span class="material-icons-outlined">close</span>
            </button>
        </div>

        {{-- Add label --}}
        <div class="p-4 border-b border-border">
            <form @submit.prevent="addLabel()">
                <div class="flex gap-2">
                    <input type="color" x-model="newColor" class="w-10 h-10 rounded-lg border border-border cursor-pointer">
                    <input type="text" x-model="newName" class="form-input flex-1 text-sm" placeholder="New label name..." required>
                    <button type="submit" class="btn-primary text-sm !py-0 px-3">Add</button>
                </div>
                <p x-show="error" x-text="error" class="text-red-500 text-xs mt-1" style="display:none;"></p>
            </form>
        </div>

        {{-- Label list --}}
        <div class="max-h-72 overflow-y-auto" id="label-list-scroll">
            <template x-for="label in labels" :key="label.id">
                {{-- Swipe wrapper --}}
                <div class="lbl-swipe-row relative border-b border-border/50"
                     :data-label-id="label.id">

                    {{-- Delete reveal: mobile swipe only (bg on parent row) --}}
                    <div class="lbl-del-reveal absolute inset-y-0 right-0 w-20
                                flex flex-col items-center justify-center gap-0.5
                                opacity-0 pointer-events-none"
                         aria-hidden="true">
                        <span class="material-icons-outlined text-white" style="font-size:1.5rem">delete</span>
                        <span style="font-size:9px;font-weight:700;color:#fff;letter-spacing:0.08em;text-transform:uppercase;">Delete</span>
                    </div>

                    {{-- Row inner (slides on swipe) --}}
                    <div class="lbl-row-inner flex items-center gap-3 px-4 py-3
                                bg-card hover:bg-hover transition-colors relative">

                        <input type="color" :value="label.color"
                               @change="updateLabel(label.id, label.name, $event.target.value)"
                               class="w-6 h-6 rounded cursor-pointer border-0 flex-shrink-0">

                        <template x-if="editingId !== label.id">
                            <span class="flex-1 text-sm font-medium cursor-pointer"
                                  @dblclick="startEditing(label)" x-text="label.name"></span>
                        </template>
                        <template x-if="editingId === label.id">
                            <input type="text" x-model="editingName"
                                   @keydown.enter="saveEdit(label.id)"
                                   @keydown.escape="cancelEdit()"
                                   @blur="saveEdit(label.id)"
                                   class="flex-1 text-sm bg-hover rounded-lg px-2 py-1 border border-border" autofocus>
                        </template>

                        <span class="text-xs text-muted flex-shrink-0"
                              x-text="(label.notes_count||0) + ' notes'"></span>

                        {{-- Delete btn: desktop hover-reveal --}}
                        <button @click.stop="deleteLabel(label.id)"
                                class="lbl-del-btn flex-shrink-0 p-1 rounded-lg text-muted"
                                title="Delete label" aria-label="Delete label">
                            <span class="material-icons-outlined text-sm">delete</span>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="labels.length === 0" class="p-6 text-center text-muted text-sm">
                No labels yet. Create one above!
            </div>
        </div>
    </div>
</div>

{{-- Label delete confirm modal --}}
<div id="lbl-delete-modal"
     class="lbl-modal-overlay lbl-modal-hidden fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center"
     style="display:none;z-index:9999;">
    <div class="lbl-modal-box bg-card rounded-2xl shadow-2xl border border-border w-full max-w-sm mx-4 p-6">
        <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
            <span class="material-icons-outlined text-red-500">delete</span>
            Delete Label
        </h3>
        <p class="text-muted text-sm mb-6">Are you sure you want to delete this label? Notes will not be affected.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="closeLblDeleteModal()" class="btn-secondary">Cancel</button>
            <button id="lbl-confirm-delete-btn" class="btn-danger">Delete</button>
        </div>
    </div>
</div>

<style>
/* ── Modal overlay/box (dùng cho lbl-delete-modal) ──────────────── */
.lbl-modal-overlay {
    transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.lbl-modal-overlay.lbl-modal-hidden {
    opacity: 0;
    pointer-events: none;
}
.lbl-modal-overlay .lbl-modal-box {
    transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.28s cubic-bezier(0.34, 1.3, 0.64, 1);
}
.lbl-modal-overlay.lbl-modal-hidden .lbl-modal-box {
    opacity: 0;
    transform: scale(0.92) translateY(12px);
}
.lbl-modal-overlay:not(.lbl-modal-hidden) .lbl-modal-box {
    opacity: 1;
    transform: scale(1) translateY(0);
}

/* ── Desktop: delete btn ẩn mặc định, hiện khi hover ─────────────── */
@media (hover: hover) and (min-width: 640px) {
    .lbl-del-btn {
        opacity: 0;
        transform: scale(0.82) translateY(1px);
        pointer-events: none;
        transition: opacity 0.16s ease,
                    transform 0.18s cubic-bezier(0.34, 1.3, 0.64, 1),
                    color 0.15s ease,
                    background-color 0.15s ease;
    }
    .lbl-swipe-row:hover .lbl-del-btn {
        opacity: 1;
        transform: scale(1) translateY(0);
        pointer-events: auto;
    }
    /* Hover trực tiếp vào nút → icon đỏ */
    .lbl-del-btn:hover {
        color: #ef4444;
        background-color: rgb(239 68 68 / 0.1);
        border-radius: 0.5rem;
    }
    /* Ẩn hoàn toàn swipe-reveal panel trên desktop */
    .lbl-del-reveal { display: none !important; }
}

/* ── Mobile: ẩn desktop delete btn, dùng swipe để xóa ─────────────── */
@media (max-width: 639px) {
    .lbl-del-btn { display: none !important; }
    /* Background đỏ luôn sẵn — inner row trượt sang trái → đỏ lộ ra tự nhiên */
    .lbl-swipe-row {
        overflow: hidden;
        background: linear-gradient(to left, #ef4444, #f87171);
    }
    /* Inner row nằm trên (z=1), che background đỏ phía dưới */
    .lbl-row-inner {
        position: relative;
        z-index: 1;
        will-change: transform;
        background: var(--color-card, #fff);
        -webkit-transform: translateZ(0);
    }
    /* Icon reveal: luôn full size, không scale — chỉ fade in */
    .lbl-del-reveal {
        z-index: 0;
        opacity: 0;
        transition: opacity 0.1s ease;
    }
    .lbl-del-reveal.lbl-triggered {
        animation: lbl-icon-pulse 0.18s cubic-bezier(0.34, 1.8, 0.64, 1);
    }
    @keyframes lbl-icon-pulse {
        0%   { transform: scale(1); }
        50%  { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
}
</style>

<script>
// Guard: AJAX nav re-executes body scripts on every navigation — run only once
if (!window._labelModalScriptInit) {
window._labelModalScriptInit = true;
function _refreshLabelUI(labels) {
    // 1) Label filter chips (#label-chips trên trang notes/index)
    let chipsWrap = document.getElementById('label-chips');
    if (!chipsWrap && labels.length > 0) {
        const notesContainer = document.getElementById('notes-container');
        if (notesContainer) {
            chipsWrap = document.createElement('div');
            chipsWrap.id = 'label-chips';
            chipsWrap.className = 'flex flex-wrap gap-2 mb-6';
            notesContainer.parentElement.insertBefore(chipsWrap, notesContainer);
        }
    }
    if (chipsWrap) {
        let allChip = chipsWrap.querySelector('[data-label-id=""]');
        chipsWrap.innerHTML = '';
        if (!allChip) {
            allChip = document.createElement('a');
            allChip.href = '/notes';
            allChip.className = 'label-chip inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all bg-[#16a34a] text-white';
            allChip.dataset.labelId = '';
            allChip.textContent = 'All Notes';
            allChip.addEventListener('click', function(e) {
                e.preventDefault();
                if (window._currentLabels !== undefined) window._currentLabels = '';
                document.querySelectorAll('.label-chip').forEach(c => {
                    c.classList.remove('bg-[#16a34a]', 'text-white', 'shadow-md');
                    c.classList.add('bg-hover', 'text-muted', 'border', 'border-border');
                    c.style.backgroundColor = '';
                });
                this.classList.remove('bg-hover', 'text-muted', 'border', 'border-border');
                this.classList.add('bg-[#16a34a]', 'text-white');
                if (window.doSearch) window.doSearch(document.getElementById('search-input')?.value || '', '');
            });
        }
        chipsWrap.appendChild(allChip);

        labels.forEach(label => {
            const a = document.createElement('a');
            a.href = `/notes?labels=${label.id}`;
            a.className = 'label-chip inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all bg-hover text-muted hover:text-body border border-border';
            a.dataset.labelId = label.id;
            a.innerHTML = `<span class="w-2 h-2 rounded-full" style="background-color:${label.color}"></span>${label.name}`;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                if (window._currentLabels !== undefined) window._currentLabels = this.dataset.labelId;
                document.querySelectorAll('.label-chip').forEach(c => {
                    c.classList.remove('bg-[#16a34a]', 'text-white', 'shadow-md');
                    c.classList.add('bg-hover', 'text-muted', 'border', 'border-border');
                    c.style.backgroundColor = '';
                });
                this.classList.remove('bg-hover', 'text-muted', 'border', 'border-border');
                this.classList.add('bg-[#16a34a]', 'text-white');
                if (window.doSearch) {
                    const q = document.getElementById('search-input')?.value || '';
                    window.doSearch(q, this.dataset.labelId);
                }
            });
            chipsWrap.appendChild(a);
        });

        chipsWrap.style.display = labels.length === 0 ? 'none' : '';
    }

    // 2) Sidebar label list
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        const nav = sidebar.querySelector('nav');
        if (nav) {
            Array.from(nav.querySelectorAll('a.nav-link[href*="labels="]')).forEach(el => el.remove());
            const settingsDiv = Array.from(nav.querySelectorAll('.sidebar-section-label'))
                .find(el => el.textContent.trim() === 'Settings')
                ?.closest('div') || null;
            labels.forEach(label => {
                const a = document.createElement('a');
                a.href = `/notes?labels=${label.id}`;
                a.className = 'nav-link';
                a.innerHTML = `<span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color:${label.color}"></span><span class="truncate">${label.name}</span>`;
                nav.insertBefore(a, settingsDiv);
            });
        }
    }
}

// ── Swipe-to-delete labels (mobile) — event delegation, init 1 lần ──────────
let _lmRef = null; // luôn trỏ đến Alpine instance mới nhất

function _initLabelSwipe(lmRef) {
    _lmRef = lmRef; // cập nhật reference mới nhất (dù đã init rồi)

    const scroll = document.getElementById('label-list-scroll');
    if (!scroll || scroll._lblSwipeInit) return;
    scroll._lblSwipeInit = true;

    const THRESHOLD = 72;
    let startX = 0, startY = 0;
    let activeInner = null, activeRow = null, activeReveal = null;
    let isHoriz = false, thresholdHit = false;

    const resetSwipe = (inner, reveal) => {
        if (!inner) return;
        inner.style.transition = 'transform 0.28s cubic-bezier(0.25,0.46,0.45,0.94)';
        inner.style.transform  = '';
        if (reveal) {
            reveal.style.opacity = '0';
            reveal.classList.remove('lbl-triggered');
        }
    };

    // Đặt transform-origin ngay khi touch start để CSS và JS nhất quán
    scroll.addEventListener('touchstart', e => {
        const inner = e.target.closest('.lbl-row-inner');
        if (!inner) return;
        const row = inner.closest('.lbl-swipe-row');
        if (!row) return;
        if (activeInner && activeInner !== inner) resetSwipe(activeInner, activeReveal);
        activeInner  = inner;
        activeRow    = row;
        activeReveal = row.querySelector('.lbl-del-reveal');
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        isHoriz = false; thresholdHit = false;
        if (activeReveal) { activeReveal.style.opacity = '0'; activeReveal.classList.remove('lbl-triggered'); }
        requestAnimationFrame(() => { if (activeInner === inner) inner.style.transition = 'none'; });
    }, { passive: true });

    scroll.addEventListener('touchmove', e => {
        if (!activeInner) return;
        const dx = e.touches[0].clientX - startX;
        const dy = e.touches[0].clientY - startY;
        if (!isHoriz) {
            if (Math.abs(dy) > Math.abs(dx) + 6) { activeInner = null; return; }
            if (Math.abs(dx) < 6) return;
            isHoriz = true;
        }
        // Chỉ xử lý kéo trái (delete), phải → reset
        if (dx > 0) { resetSwipe(activeInner, activeReveal); activeInner = null; return; }
        e.preventDefault();
        const clamped = Math.max(-(THRESHOLD * 1.4), dx);
        activeInner.style.transform = `translateX(${clamped}px)`;
        const progress = Math.min(Math.abs(clamped) / THRESHOLD, 1);
        if (activeReveal) {
            // Chỉ fade in icon — bg đỏ lấy từ .lbl-swipe-row, icon luôn căn giữa
            activeReveal.style.opacity = String(progress);
        }
        if (Math.abs(dx) >= THRESHOLD && !thresholdHit) {
            thresholdHit = true;
            activeReveal?.classList.add('lbl-triggered');
            if (navigator.vibrate) navigator.vibrate(10);
        } else if (Math.abs(dx) < THRESHOLD && thresholdHit) {
            thresholdHit = false;
            activeReveal?.classList.remove('lbl-triggered');
        }
    }, { passive: false });

    scroll.addEventListener('touchend', e => {
        if (!activeInner || !isHoriz) { activeInner = null; return; }
        const dx      = e.changedTouches[0].clientX - startX;
        const inner   = activeInner;
        const row     = activeRow;
        const reveal  = activeReveal;
        const labelId = parseInt(row.dataset.labelId, 10);
        resetSwipe(inner, reveal);
        activeInner = null;
        if (dx <= -THRESHOLD) {
            setTimeout(() => openLblDeleteModal(labelId, _lmRef), 250);
        }
    });

    scroll.addEventListener('touchcancel', () => {
        resetSwipe(activeInner, activeReveal);
        activeInner = null;
    });
}

function labelManager() {
    return {
        labels: [],
        newName: '',
        newColor: '#16a34a',
        error: '',
        editingId: null,
        editingName: '',

        async init() {
            // Chỉ init 1 lần, event delegation tự xử lý các row mới sau re-render
            this.$nextTick(() => _initLabelSwipe(this));
        },

        async loadLabels() {
            try {
                this.labels = await apiCall('/labels');
                _refreshLabelUI(this.labels);
                // Sau lần loadLabels đầu tiên, scroll đã có _lblSwipeInit=true → không re-attach
                // Nhưng nếu modal bị đóng/mở lại (DOM reset), cần init lại
                this.$nextTick(() => _initLabelSwipe(this));
            } catch(e) {}
        },

        async addLabel() {
            this.error = '';
            try {
                await apiCall('/labels', 'POST', { name: this.newName, color: this.newColor });
                this.newName = '';
                await this.loadLabels();
                showToast('Label created');
            } catch(e) {
                this.error = e.error || 'Error creating label';
            }
        },

        startEditing(label) {
            this.editingId = label.id;
            this.editingName = label.name;
        },

        cancelEdit() {
            this.editingId = null;
            this.editingName = '';
        },

        async saveEdit(id) {
            if (!this.editingName.trim()) { this.cancelEdit(); return; }
            try {
                const label = this.labels.find(l => l.id === id);
                await apiCall(`/labels/${id}`, 'PUT', { name: this.editingName, color: label.color });
                this.cancelEdit();
                await this.loadLabels();
                showToast('Label renamed');
            } catch(e) { showToast(e.error || 'Error', 'error'); }
        },

        async updateLabel(id, name, color) {
            try {
                await apiCall(`/labels/${id}`, 'PUT', { name, color });
                await this.loadLabels();
            } catch(e) { showToast('Error', 'error'); }
        },

        async deleteLabel(id) {
            openLblDeleteModal(id, this);
        },
    };
}

function openLblDeleteModal(labelId, lmRef) {
    const modal = document.getElementById('lbl-delete-modal');
    if (!modal) return;

    // Teleport ra body để thoát khỏi stacking context của label-modal
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    modal.style.display = 'flex';
    modal.getBoundingClientRect(); // force reflow để transition chạy
    modal.classList.remove('lbl-modal-hidden');

    // Đóng khi click backdrop (ngoài modal-box)
    modal._backdropHandler = (e) => {
        if (e.target === modal) closeLblDeleteModal();
    };
    modal.addEventListener('click', modal._backdropHandler);

    // Clone button để xóa listener cũ, tránh trigger nhiều lần
    const oldBtn = document.getElementById('lbl-confirm-delete-btn');
    if (!oldBtn) return;
    const newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    newBtn.addEventListener('click', async () => {
        closeLblDeleteModal();
        try {
            await apiCall(`/labels/${labelId}`, 'DELETE');
            await (lmRef || _lmRef)?.loadLabels();
            showToast('Label deleted');
        } catch(e) { showToast(e.error || 'Error', 'error'); }
    });
}

function closeLblDeleteModal() {
    const modal = document.getElementById('lbl-delete-modal');
    if (!modal) return;
    modal.classList.add('lbl-modal-hidden');
    if (modal._backdropHandler) {
        modal.removeEventListener('click', modal._backdropHandler);
        modal._backdropHandler = null;
    }
    setTimeout(() => { modal.style.display = 'none'; }, 220);
}

function openLabelManager() {
    document.getElementById('label-modal').classList.remove('hidden');
    window.dispatchEvent(new CustomEvent('open-label-manager'));
}

function closeLabelManager() {
    document.getElementById('label-modal').classList.add('hidden');
    if (window.location.pathname.includes('/notes')) {
        const searchVal = document.getElementById('search-input')?.value || '';
        const params = new URLSearchParams();
        if (searchVal) params.set('search', searchVal);
        fetch('/notes?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.notes) renderNotes(data.notes);
        }).catch(() => window.location.reload());
    } else {
        window.location.reload();
    }
}
} // end _labelModalScriptInit guard
</script>
