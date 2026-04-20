<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;background:#f8fafc;padding:40px 20px;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#1e293b;font-size:24px;margin-bottom:8px;">A Note Was Shared With You</h1>
    <p style="text-align:center;color:#64748b;margin-bottom:20px;">{{ $sharer->display_name }} ({{ $sharer->email }}) shared a note with you.</p>
    <div style="background:#f1f5f9;border-radius:12px;padding:20px;margin-bottom:20px;">
        <p style="font-weight:600;color:#1e293b;margin-bottom:4px;">{{ $note->title ?: 'Untitled' }}</p>
        <p style="color:#64748b;font-size:14px;">Permission: {{ $permission === 'edit' ? 'Can Edit' : 'Read Only' }}</p>
    </div>
    <p style="color:#94a3b8;font-size:13px;text-align:center;">Log in to JOTIFY to view this note.</p>
</div>
</body></html>
