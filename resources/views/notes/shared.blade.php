@extends('layouts.app')
@section('title', 'Shared with Me - JOTIFY')

@section('header')
<h1 class="text-lg font-bold flex items-center gap-2">
    <span class="material-icons-outlined" style="color:var(--accent-dim);">people</span>
    Shared with Me
</h1>
@endsection

@section('content')
@if($sharedNotes->count() > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($sharedNotes as $share)
    <a href="/shared/{{ $share->id }}/view"
       class="bg-card rounded-xl border border-border hover:border-[var(--accent-border)] hover:shadow-lg hover:-translate-y-1 transition-all p-4 no-underline">
        <div class="flex items-center gap-1.5 mb-2">
            <span class="material-icons-outlined text-base" style="color:var(--accent-dim);">share</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                {{ $share->permission === 'edit' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
                {{ $share->permission === 'edit' ? 'Can Edit' : 'Read Only' }}
            </span>
        </div>
        <h3 class="font-semibold text-sm mb-1 truncate">{{ $share->note->title ?: 'Untitled' }}</h3>
        <p class="text-xs text-muted line-clamp-3 mb-3">{{ \Str::limit(strip_tags($share->note->content), 120) }}</p>
        <div class="flex items-center gap-2 pt-2 border-t border-border/50">
            <img src="{{ $share->owner->avatar_url }}" class="w-5 h-5 rounded-full" alt="">
            <span class="text-[11px] text-muted truncate">{{ $share->owner->display_name }}</span>
            <span class="text-[10px] text-muted ml-auto">{{ $share->shared_at->diffForHumans() }}</span>
        </div>
    </a>
    @endforeach
</div>
@else
<div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="w-24 h-24 rounded-3xl flex items-center justify-center mb-6" style="background:var(--accent-subtle);">
        <span class="material-icons-outlined text-5xl" style="color:var(--accent-dim);opacity:0.6;">folder_shared</span>
    </div>
    <h3 class="text-lg font-semibold mb-2">No shared notes</h3>
    <p class="text-muted text-sm">Notes shared with you will appear here</p>
</div>
@endif
@endsection
