<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Reset your password</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:25px 32px;border-bottom:1px solid #e2e8f0;">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="SS Advisory" width="180" style="display:block;width:180px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 18px;">
                            <p style="margin:0 0 10px;color:#00a8b5;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Account security</p>
                            <h1 style="margin:0 0 20px;color:#081b33;font-size:25px;line-height:1.3;">Reset your password</h1>
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;">Hi {{ $userName }},</p>
                            <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.7;">We received a request to reset the password for your SS Advisory account. Use the button below to choose a new password.</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;">
                                <tr>
                                    <td bgcolor="#081b33" style="border-radius:7px;">
                                        <a href="{{ $url }}" style="display:inline-block;padding:14px 24px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;border-radius:7px;">Reset Password</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 12px;color:#64748b;font-size:13px;line-height:1.7;">This link expires in {{ $expiration }} minutes. If you did not request a password reset, you can ignore this email; your password will remain unchanged.</p>
                            <p style="margin:0;color:#64748b;font-size:13px;line-height:1.7;">If the button does not work, copy and paste this link into your browser:<br><a href="{{ $url }}" style="color:#008b98;word-break:break-all;">{{ $url }}</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;line-height:1.6;">
                            SS Advisory Insurance Brokerage<br>
                            This is an automated security email. Please do not reply.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
