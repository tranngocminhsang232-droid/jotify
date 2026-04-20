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
        <div class="max-h-72 overflow-y-auto">
            <template x-for="label in labels" :key="label.id">
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-hover transition-colors border-b border-border/50">
                    <input type="color" :value="label.color" @change="updateLabel(label.id, label.name, $event.target.value)"
                           class="w-6 h-6 rounded cursor-pointer border-0">
                    <template x-if="editingId !== label.id">
                        <span class="flex-1 text-sm font-medium cursor-pointer" @dblclick="startEditing(label)" x-text="label.name"></span>
                    </template>
                    <template x-if="editingId === label.id">
                        <input type="text" x-model="editingName" @keydown.enter="saveEdit(label.id)" @keydown.escape="cancelEdit()"
                               @blur="saveEdit(label.id)" class="flex-1 text-sm bg-hover rounded-lg px-2 py-1 border border-border" autofocus>
                    </template>
                    <span class="text-xs text-muted" x-text="(label.notes_count||0) + ' notes'"></span>
                    <button @click="deleteLabel(label.id)" class="p-1 rounded-lg hover:bg-red-500/10 text-muted hover:text-red-500 transition-colors">
                        <span class="material-icons-outlined text-sm">delete</span>
                    </button>
                </div>
            </template>
            <div x-show="labels.length === 0" class="p-6 text-center text-muted text-sm">
                No labels yet. Create one above!
            </div>
        </div>
    </div>
</div>

<script>
function labelManager() {
    return {
        labels: [],
        newName: '',
        newColor: '#16a34a',
        error: '',
        editingId: null,
        editingName: '',
        
        async init() {},

        async loadLabels() {
            try {
                this.labels = await apiCall('/labels');
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
            if (!confirm('Delete this label? Notes will not be affected.')) return;
            try {
                await apiCall(`/labels/${id}`, 'DELETE');
                await this.loadLabels();
                showToast('Label deleted');
            } catch(e) { showToast('Error', 'error'); }
        },
    };
}

function openLabelManager() {
    document.getElementById('label-modal').classList.remove('hidden');
    // Alpine v3: dispatch on window so @open-label-manager.window catches it
    window.dispatchEvent(new CustomEvent('open-label-manager'));
}

function closeLabelManager() {
    document.getElementById('label-modal').classList.add('hidden');
    // Refresh current page via AJAX to reflect label changes
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
</script>
