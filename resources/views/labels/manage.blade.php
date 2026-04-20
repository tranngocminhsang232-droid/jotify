@extends('layouts.app')
@section('title', 'Manage Labels - JOTIFY')
@section('header')
<h1 class="text-lg font-bold flex items-center gap-2">
    <span class="material-icons-outlined" style="color:var(--accent-dim)">label</span>
    Manage Labels
</h1>
@endsection
@section('content')
<div class="max-w-2xl mx-auto" x-data="labelPageManager()" x-init="loadLabels()">
    {{-- Add label --}}
    <div class="bg-card rounded-2xl border border-border p-5 mb-6">
        <h3 class="font-semibold mb-3">Create New Label</h3>
        <form @submit.prevent="addLabel()">
            <div class="flex gap-3">
                <input type="color" x-model="newColor" class="w-10 h-10 rounded-lg border border-border cursor-pointer flex-shrink-0">
                <input type="text" x-model="newName" class="form-input flex-1" placeholder="Label name..." required>
                <button type="submit" class="btn-primary">Create</button>
            </div>
            <p x-show="error" x-text="error" class="text-red-500 text-xs mt-2" style="display:none;"></p>
        </form>
    </div>

    {{-- Label list --}}
    <div class="bg-card rounded-2xl border border-border overflow-hidden">
        <template x-for="label in labels" :key="label.id">
            <div class="flex items-center gap-4 px-5 py-4 border-b border-border/50 hover:bg-hover transition-colors">
                <input type="color" :value="label.color" @change="updateLabel(label.id, label.name, $event.target.value)"
                       class="w-8 h-8 rounded-lg cursor-pointer border-0 flex-shrink-0">
                <template x-if="editingId !== label.id">
                    <span class="flex-1 font-medium cursor-pointer" @dblclick="startEditing(label)" x-text="label.name"></span>
                </template>
                <template x-if="editingId === label.id">
                    <input type="text" x-model="editingName" @keydown.enter="saveEdit(label.id)" @keydown.escape="cancelEdit()"
                           class="flex-1 form-input text-sm" autofocus>
                </template>
                <span class="text-sm text-muted" x-text="(label.notes_count||0) + ' notes'"></span>
                <button @click="deleteLabel(label.id)" class="p-2 rounded-lg hover:bg-red-500/10 text-muted hover:text-red-500 transition-colors">
                    <span class="material-icons-outlined text-lg">delete</span>
                </button>
            </div>
        </template>
        <div x-show="labels.length === 0" class="p-8 text-center text-muted">
            No labels yet. Create one above!
        </div>
    </div>
</div>

@push('scripts')
<script>
function labelPageManager() {
    return {
        labels: [], newName: '', newColor: '#16a34a', error: '',
        editingId: null, editingName: '',
        async loadLabels() { try { this.labels = await apiCall('/labels'); } catch(e) {} },
        async addLabel() {
            this.error = '';
            try {
                await apiCall('/labels', 'POST', { name: this.newName, color: this.newColor });
                this.newName = '';
                await this.loadLabels();
                showToast('Label created');
            } catch(e) { this.error = e.error || 'Error'; }
        },
        startEditing(label) { this.editingId = label.id; this.editingName = label.name; },
        cancelEdit() { this.editingId = null; },
        async saveEdit(id) {
            if (!this.editingName.trim()) return this.cancelEdit();
            try {
                const l = this.labels.find(x=>x.id===id);
                await apiCall(`/labels/${id}`, 'PUT', { name: this.editingName, color: l.color });
                this.cancelEdit();
                await this.loadLabels();
                showToast('Label renamed');
            } catch(e) { showToast(e.error || 'Error', 'error'); }
        },
        async updateLabel(id, name, color) {
            try { await apiCall(`/labels/${id}`, 'PUT', { name, color }); await this.loadLabels(); } catch(e) {}
        },
        async deleteLabel(id) {
            if (!confirm('Delete this label? Notes will not be affected.')) return;
            try { await apiCall(`/labels/${id}`, 'DELETE'); await this.loadLabels(); showToast('Label deleted'); } catch(e) {}
        },
    };
}
</script>
@endpush
@endsection
