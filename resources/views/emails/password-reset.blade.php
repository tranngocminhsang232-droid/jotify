<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;background:#f8fafc;padding:40px 20px;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:16px;padding:40px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#1e293b;font-size:24px;margin-bottom:8px;">Reset Your Password</h1>
    <p style="text-align:center;color:#64748b;margin-bottom:20px;">You requested a password reset. Use either the button below or the OTP code.</p>
    <div style="text-align:center;margin-bottom:20px;">
        <a href="{{ $resetUrl }}" style="display:inline-block;background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;text-decoration:none;padding:14px 32px;border-radius:12px;font-weight:600;">Reset Password</a>
    </div>
    <div style="text-align:center;margin-bottom:20px;">
        <p style="color:#64748b;font-size:14px;">Or use this OTP code:</p>
        <p style="font-size:32px;font-weight:800;letter-spacing:8px;color:#1e293b;font-family:monospace;">{{ $otp }}</p>
    </div>
    <p style="color:#94a3b8;font-size:12px;text-align:center;">This code expires in 60 minutes. If you didn't request this, please ignore this email.</p>
</div>
</body></html>
