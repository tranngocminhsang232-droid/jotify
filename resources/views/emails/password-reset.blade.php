@extends('emails.layout')

@section('email-body')

{{-- Icon --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <div style="display:inline-block;width:72px;height:72px;border-radius:50%;
                        background:linear-gradient(135deg,#fef9c3,#fde68a);
                        border:2px solid #fcd34d;
                        text-align:center;line-height:72px;font-size:32px;">
                &#128272;
            </div>
        </td>
    </tr>

    {{-- Tiêu đề --}}
    <tr>
        <td align="center" style="padding-bottom:10px;">
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#052e16;letter-spacing:-0.5px;">
                Reset Your Password
            </h1>
        </td>
    </tr>

    {{-- Mô tả --}}
    <tr>
        <td align="center" style="padding-bottom:32px;">
            <p style="margin:0;font-size:15px;color:#166534;line-height:1.6;max-width:420px;">
                You requested a password reset for your JOTIFY account.<br>
                Use either option below — whichever is easiest for you.
            </p>
        </td>
    </tr>

    {{-- Nút reset --}}
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <a href="{{ $resetUrl }}"
               style="display:inline-block;background:linear-gradient(135deg,#16a34a,#22c55e);
                      color:#ffffff;text-decoration:none;
                      padding:16px 40px;border-radius:14px;
                      font-size:16px;font-weight:700;letter-spacing:0.02em;
                      box-shadow:0 8px 24px rgba(34,197,94,0.38);">
                Reset My Password
            </a>
        </td>
    </tr>

    {{-- Divider với "OR" --}}
    <tr>
        <td style="padding-bottom:24px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="border-top:1px dashed #bbf7d0;width:45%;"></td>
                    <td align="center" width="10%"
                        style="font-size:12px;color:#6b7280;font-weight:600;padding:0 12px;white-space:nowrap;">
                        OR
                    </td>
                    <td style="border-top:1px dashed #bbf7d0;width:45%;"></td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- OTP Box --}}
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <p style="margin:0 0 12px;font-size:14px;color:#166534;font-weight:500;">
                Use this one-time code on the verification page:
            </p>
            <div style="display:inline-block;background:linear-gradient(135deg,#f0fdf4,#dcfce7);
                        border:2px solid #4ade80;border-radius:16px;padding:20px 40px;
                        box-shadow:0 4px 20px rgba(34,197,94,0.15);">
                <span style="font-family:'Courier New',Courier,monospace;
                             font-size:40px;font-weight:900;letter-spacing:12px;
                             color:#15803d;display:inline-block;">
                    {{ $otp }}
                </span>
            </div>
        </td>
    </tr>

    {{-- Thông tin thêm --}}
    <tr>
        <td style="padding-bottom:16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background:#fefce8;border-radius:12px;border:1px solid #fde68a;
                               padding:14px 18px;">
                        <p style="margin:0;font-size:13px;color:#854d0e;text-align:center;">
                            This code and link expire in <strong>60 minutes</strong>.
                            Do not share this with anyone.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Link dự phòng --}}
    <tr>
        <td align="center">
            <p style="margin:0 0 6px;font-size:13px;color:#9ca3af;">
                Or paste this link manually into your browser:
            </p>
            <p style="margin:0;word-break:break-all;">
                <a href="{{ $resetUrl }}"
                   style="font-size:11px;color:#16a34a;text-decoration:underline;">
                    {{ $resetUrl }}
                </a>
            </p>
        </td>
    </tr>
</table>

@endsection
