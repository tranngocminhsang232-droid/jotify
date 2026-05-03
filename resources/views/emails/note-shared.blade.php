@extends('emails.layout')

@section('email-body')

{{-- Icon --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <div style="display:inline-block;width:72px;height:72px;border-radius:50%;
                        background:linear-gradient(135deg,#dcfce7,#bbf7d0);
                        border:2px solid #4ade80;
                        text-align:center;line-height:72px;font-size:32px;">
                &#9997;
            </div>
        </td>
    </tr>

    {{-- Tiêu đề --}}
    <tr>
        <td align="center" style="padding-bottom:10px;">
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#052e16;letter-spacing:-0.5px;">
                A Note Was Shared With You
            </h1>
        </td>
    </tr>

    {{-- Mô tả --}}
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <p style="margin:0;font-size:15px;color:#166534;line-height:1.6;">
                <strong style="color:#15803d;">{{ $sharer->display_name }}</strong>
                has shared a note with you on JOTIFY.
            </p>
        </td>
    </tr>

    {{-- Note preview card --}}
    <tr>
        <td style="padding-bottom:28px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);
                               border-radius:16px;border:1px solid #86efac;
                               padding:24px;
                               box-shadow:0 4px 16px rgba(34,197,94,0.10);">

                        {{-- Label --}}
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;
                                  letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;">
                            Note Title
                        </p>

                        {{-- Title --}}
                        <p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#052e16;">
                            {{ $note->title ?: 'Untitled Note' }}
                        </p>

                        {{-- Divider --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                               style="margin-bottom:16px;">
                            <tr><td style="border-top:1px solid #bbf7d0;"></td></tr>
                        </table>

                        {{-- Permission badge --}}
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:{{ $permission === 'edit' ? '#22c55e' : '#3b82f6' }};
                                           color:#ffffff;border-radius:8px;padding:5px 14px;
                                           font-size:12px;font-weight:700;letter-spacing:0.04em;">
                                    @if($permission === 'edit')
                                        Can Edit
                                    @else
                                        Read Only
                                    @endif
                                </td>
                                <td style="padding-left:12px;font-size:13px;color:#6b7280;">
                                    Shared by <strong>{{ $sharer->email }}</strong>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- CTA Button --}}
    <tr>
        <td align="center" style="padding-bottom:28px;">
            <a href="{{ url('/shared') }}"
               style="display:inline-block;background:linear-gradient(135deg,#16a34a,#22c55e);
                      color:#ffffff;text-decoration:none;
                      padding:16px 40px;border-radius:14px;
                      font-size:16px;font-weight:700;letter-spacing:0.02em;
                      box-shadow:0 8px 24px rgba(34,197,94,0.38);">
                View Shared Note
            </a>
        </td>
    </tr>

    {{-- Info --}}
    <tr>
        <td>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;
                               padding:14px 18px;">
                        <p style="margin:0;font-size:13px;color:#166534;text-align:center;">
                            Log in to your JOTIFY account to access this note.
                            It will appear in your <strong>"Shared with Me"</strong> section.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@endsection
