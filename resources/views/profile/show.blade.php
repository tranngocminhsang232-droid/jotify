@extends('layouts.app')
@section('title', 'Profile - JOTIFY')

@section('header')
<h1 class="text-lg font-bold">Profile</h1>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-card rounded-2xl border border-border overflow-hidden">
        {{-- Cover --}}
        <div class="h-32 relative" style="background: linear-gradient(135deg, var(--color-hover) 0%, var(--accent-subtle) 50%, rgba(34,197,94,0.3) 100%);"></div>
        
        {{-- Avatar --}}
        <div class="px-6 -mt-12 relative">
            <img src="{{ $user->avatar_url }}" alt="Avatar" 
                 class="w-24 h-24 rounded-2xl object-cover border-4 border-card shadow-xl">
        </div>

        <div class="p-6 pt-4">
            <h2 class="text-xl font-bold">{{ $user->display_name }}</h2>
            <p class="text-muted text-sm">{{ $user->email }}</p>
            
            @if($user->is_activated)
            <span class="inline-flex items-center gap-1 mt-2 text-xs text-emerald-500 font-medium">
                <span class="material-icons-outlined text-sm">verified</span> Verified
            </span>
            @else
            <span class="inline-flex items-center gap-1 mt-2 text-xs text-amber-500 font-medium">
                <span class="material-icons-outlined text-sm">warning</span> Not Verified
            </span>
            @endif
        </div>

        <div class="border-t border-border p-6 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted">Member since</span>
                <span class="text-sm font-medium">{{ $user->created_at->format('M d, Y') }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted">Notes created</span>
                <span class="text-sm font-medium">{{ $user->notes()->count() }}</span>
            </div>
        </div>

        <div class="border-t border-border p-6 flex flex-wrap gap-3">
            <a href="/profile/edit" class="btn-primary text-sm">
                <span class="material-icons-outlined text-lg">edit</span>
                Edit Profile
            </a>
            <a href="/profile/change-password" class="btn-secondary text-sm">
                <span class="material-icons-outlined text-lg">lock</span>
                Change Password
            </a>
        </div>
    </div>
</div>
@endsection
