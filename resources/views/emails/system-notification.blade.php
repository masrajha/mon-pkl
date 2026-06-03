<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $notification->subject }}</title>
</head>
<body style="margin:0;background:#f5f7fb;color:#0f172a;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fb;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 26px;background:#2563eb;color:#ffffff;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">SiLAT</div>
                            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;">{{ $notification->subject }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px;color:#334155;font-size:15px;line-height:1.7;">
                            @if ($notification->recipient_name)
                                <p style="margin:0 0 16px;">Yth. {{ $notification->recipient_name }},</p>
                            @endif

                            @foreach ($notification->body_lines as $line)
                                <p style="margin:0 0 14px;">{{ $line }}</p>
                            @endforeach

                            @if ($notification->action_url)
                                <p style="margin:24px 0;">
                                    <a href="{{ $notification->action_url }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:8px;">
                                        {{ $notification->action_text ?: 'Buka SiLAT' }}
                                    </a>
                                </p>
                            @endif

                            <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
                                Email ini dikirim otomatis oleh SiLAT. Abaikan jika informasi ini tidak relevan untuk Anda.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
