<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('app.name') }}</title>
    <!--[if mso]>
    <noscript>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;background-color:#f0fdf4;color:#052e16;">

{{-- Email Wrapper --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
       style="background-color:#f0fdf4;min-height:100%;padding:40px 16px;">
    <tr>
        <td align="center" valign="top">

            {{-- Container --}}
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                   style="max-width:580px;width:100%;">

                {{-- ─── HEADER LOGO ─── --}}
                <tr>
                    <td align="center" style="padding-bottom:28px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:linear-gradient(135deg,#16a34a,#22c55e,#4ade80);
                                           border-radius:16px;padding:12px 24px;text-align:center;
                                           box-shadow:0 8px 32px rgba(34,197,94,0.35);">
                                    <span style="font-size:22px;font-weight:800;color:#ffffff;
                                                 letter-spacing:0.08em;text-decoration:none;">
                                         JOTIFY
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ─── CARD BODY ─── --}}
                <tr>
                    <td style="background:#ffffff;border-radius:20px;
                               box-shadow:0 4px 40px rgba(34,197,94,0.12),0 1px 4px rgba(0,0,0,0.06);
                               border:1px solid #bbf7d0;overflow:hidden;">

                        {{-- Top accent bar --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="height:5px;background:linear-gradient(90deg,#16a34a,#22c55e,#4ade80,#86efac);"></td>
                            </tr>
                        </table>

                        {{-- Content slot --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="padding:40px 40px 36px 40px;">
                                    @yield('email-body')
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- ─── FOOTER ─── --}}
                <tr>
                    <td align="center" style="padding-top:28px;">
                        <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">
                            This email was sent by <strong style="color:#16a34a;">JOTIFY</strong> — Your smart note companion
                        </p>
                        <p style="margin:0;font-size:12px;color:#9ca3af;">
                            If you didn't request this, you can safely ignore this email.
                        </p>
                        <p style="margin:12px 0 0;font-size:12px;color:#d1d5db;">
                            © {{ date('Y') }} JOTIFY. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
