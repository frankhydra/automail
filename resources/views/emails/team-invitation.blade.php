<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>You're invited</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background-color:#f3f4f6;padding:24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;padding:32px;">
                    <tr>
                        <td>
                            <h2 style="margin:0 0 16px 0;color:#111827;">You're invited to {{ $invitation->organization->name }}</h2>
                            <p style="color:#374151;font-size:15px;line-height:1.6;">
                                {{ $invitation->inviter?->name ?? 'A team member' }} has invited you to join
                                <strong>{{ $invitation->organization->name }}</strong> on AutoMail as a
                                <strong>{{ ucfirst($invitation->role) }}</strong>.
                            </p>
                            <p style="text-align:center;margin:24px 0;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block;padding:12px 24px;background-color:#4f46e5;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">
                                    Accept Invitation
                                </a>
                            </p>
                            <p style="color:#9ca3af;font-size:12px;">
                                This invitation expires in 7 days. If you weren't expecting this, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
