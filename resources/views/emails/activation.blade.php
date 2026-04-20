<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;background:#f8fafc;padding:40px 20px;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div style="text-align:center;margin-bottom:30px;">
        <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,#6366f1,#a855f7);display:inline-flex;align-items:center;justify-content:center;">
            <span style="color:#fff;font-size:28px;">✉</span>
        </div>
    </div>
    <h1 style="text-align:center;color:#1e293b;font-size:24px;margin-bottom:8px;">Activate Your Account</h1>
    <p style="text-align:center;color:#64748b;margin-bottom:30px;">Hi {{ $user->display_name }}, welcome to JOTIFY! Please activate your account by clicking the button below.</p>
    <div style="text-align:center;margin-bottom:30px;">
        <a href="{{ $activationUrl }}" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;text-decoration:none;padding:14px 32px;border-radius:12px;font-weight:600;font-size:16px;">Activate Account</a>
    </div>
    <p style="color:#94a3b8;font-size:13px;text-align:center;">If the button doesn't work, copy and paste this link:<br><a href="{{ $activationUrl }}" style="color:#6366f1;word-break:break-all;">{{ $activationUrl }}</a></p>
</div>
</body></html>
