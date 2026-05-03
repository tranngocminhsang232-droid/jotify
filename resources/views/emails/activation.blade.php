@extends('emails.layout')

@section('email-body')

{{-- Icon vòng tròn --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <div style="display:inline-block;width:72px;height:72px;border-radius:50%;
                        background:linear-gradient(135deg,#dcfce7,#bbf7d0);
                        border:2px solid #86efac;
                        text-align:center;line-height:72px;font-size:32px;">
                &#9993;
            </div>
        </td>
    </tr>

    {{-- Tiêu đề --}}
    <tr>
        <td align="center" style="padding-bottom:10px;">
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#052e16;letter-spacing:-0.5px;">
                Activate Your Account
            </h1>
        </td>
    </tr>

    {{-- Phụ đề --}}
    <tr>
        <td align="center" style="padding-bottom:32px;">
            <p style="margin:0;font-size:15px;color:#166534;line-height:1.6;max-width:400px;">
                Hi <strong style="color:#15803d;">{{ $user->display_name ?? $user->name }}</strong>,
                welcome to JOTIFY!<br>
                Please activate your account to start organizing your notes.
            </p>
        </td>
    </tr>

    {{-- Nút CTA --}}
    <tr>
        <td align="center" style="padding-bottom:32px;">
            <a href="{{ $activationUrl }}"
               style="display:inline-block;background:linear-gradient(135deg,#16a34a,#22c55e);
                      color:#ffffff;text-decoration:none;
                      padding:16px 40px;border-radius:14px;
                      font-size:16px;font-weight:700;letter-spacing:0.02em;
                      box-shadow:0 8px 24px rgba(34,197,94,0.40);">
                Activate My Account
            </a>
        </td>
    </tr>

    {{-- Divider --}}
    <tr>
        <td style="padding-bottom:24px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="border-top:1px solid #dcfce7;"></td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Link dự phòng --}}
    <tr>
        <td align="center" style="padding-bottom:8px;">
            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
                Button not working? Copy and paste this link:
            </p>
            <p style="margin:0;word-break:break-all;">
                <a href="{{ $activationUrl }}"
                   style="font-size:12px;color:#16a34a;text-decoration:underline;">
                    {{ $activationUrl }}
                </a>
            </p>
        </td>
    </tr>

    {{-- Cảnh báo --}}
    <tr>
        <td style="padding-top:24px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;
                               padding:14px 18px;">
                        <p style="margin:0;font-size:13px;color:#166534;text-align:center;">
                            This link expires in <strong>24 hours</strong>.
                            If you didn't create a JOTIFY account, you can safely ignore this email.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@endsection
