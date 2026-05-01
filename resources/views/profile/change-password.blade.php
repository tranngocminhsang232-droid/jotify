@extends('layouts.app')
@section('title', 'Change Password - JOTIFY')
@section('header')
<div class="flex items-center gap-3">
    <a href="/profile" class="p-2 rounded-lg hover:bg-hover transition-colors"><span class="material-icons-outlined">arrow_back</span></a>
    <h1 class="text-lg font-bold">Change Password</h1>
</div>
@endsection
@section('content')
<div class="max-w-lg mx-auto">
    <form action="/profile/change-password" method="POST" class="bg-card rounded-2xl border border-border p-6 space-y-5">
        @csrf @method('PUT')
        @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3">
            @foreach($errors->all() as $error)
            <div class="flex items-center gap-2 text-sm text-red-500">
                <span class="material-icons-outlined text-base">error</span>{{ $error }}
            </div>
            @endforeach
        </div>
        @endif
        <div>
            <label class="block text-sm font-medium mb-2">Current Password</label>
            <input type="password" name="current_password" class="form-input w-full" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">New Password <span class="text-muted text-xs font-normal">(min. 6 characters)</span></label>
            <input type="password" name="password" class="form-input w-full" minlength="6" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-input w-full" required>
        </div>
        <button type="submit" class="btn-primary">
            <span class="material-icons-outlined text-lg">lock</span>
            Change Password
        </button>
    </form>
</div>
@endsection
