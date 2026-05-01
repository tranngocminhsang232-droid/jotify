@extends('layouts.app')
@section('title', 'Note Not Found - JOTIFY')

@section('header')
<div class="flex items-center gap-3 flex-1">
    <a href="/shared" class="p-2 rounded-lg hover:bg-hover transition-colors">
        <span class="material-icons-outlined" style="color:var(--color-muted);">arrow_back</span>
    </a>
    <span class="text-sm font-semibold" style="color:var(--color-muted);">Shared Note</span>
</div>
@endsection

@section('content')
<div class="max-w-lg mx-auto flex flex-col items-center justify-center text-center" style="padding:4rem 1rem;">

    {{-- Animated icon --}}
    <div style="
        width:96px;height:96px;border-radius:50%;
        background:rgba(239,68,68,0.08);
        display:flex;align-items:center;justify-content:center;
        margin-bottom:1.5rem;
        animation:notfound-pulse 2.4s ease-in-out infinite;
    ">
        @if(($reason ?? '') === 'note_deleted')
            <span class="material-icons-outlined" style="font-size:2.5rem;color:#ef4444;">delete_forever</span>
        @else
            <span class="material-icons-outlined" style="font-size:2.5rem;color:#ef4444;">link_off</span>
        @endif
    </div>

    {{-- Heading --}}
    <h1 style="font-size:1.5rem;font-weight:800;margin:0 0 0.5rem;color:var(--color-body-text);">
        @if(($reason ?? '') === 'note_deleted')
            Note has been deleted
        @else
            Access revoked
        @endif
    </h1>

    {{-- Subtext --}}
    <p style="font-size:0.9rem;color:var(--color-muted);margin:0 0 2rem;line-height:1.6;max-width:340px;">
        @if(($reason ?? '') === 'note_deleted')
            The note you're looking for no longer exists. It may have been deleted by the owner.
        @else
            You no longer have access to this shared note. The owner may have revoked your access.
        @endif
    </p>

    {{-- Action button --}}
    <a href="/shared"
       style="
           display:inline-flex;align-items:center;gap:0.5rem;
           padding:0.625rem 1.25rem;
           background:var(--accent,#16a34a);color:#fff;
           border-radius:0.75rem;font-size:0.875rem;font-weight:600;
           text-decoration:none;
           transition:opacity 0.15s ease,transform 0.15s ease;
       "
       onmouseenter="this.style.opacity='0.88'"
       onmouseleave="this.style.opacity='1'"
       onmousedown="this.style.transform='scale(0.97)'"
       onmouseup="this.style.transform='scale(1)'">
        <span class="material-icons-outlined" style="font-size:1.1rem;">folder_shared</span>
        Back to Shared Notes
    </a>

</div>

<style>
@keyframes notfound-pulse {
    0%, 100% { transform: scale(1);   box-shadow: 0 0 0 0 rgba(239,68,68,0.12); }
    50%       { transform: scale(1.06); box-shadow: 0 0 0 14px rgba(239,68,68,0); }
}
</style>
@endsection
