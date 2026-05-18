<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to {{ config('app.name') }}!</title>
</head>
<body style="margin:0;padding:0;background-color:#020617;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#020617;">
        <tr>
            <td align="center" style="padding:48px 16px;">

                <!-- Logo / App Name -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding-bottom:28px;">
                            <span style="font-size:20px;font-weight:700;color:#E2E8F0;letter-spacing:-0.4px;">
                                {{ config('app.name') }}
                            </span>
                        </td>
                    </tr>
                </table>

                <!-- Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#0B1220;border:1px solid #1F2937;border-radius:12px;">
                    <tr>
                        <td style="padding:40px;">

                            <!-- Accent bar -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
                                <tr>
                                    <td style="height:3px;border-radius:2px;font-size:0;line-height:0;background-color:#1E45FC;">&nbsp;</td>
                                </tr>
                            </table>

                            <!-- Heading -->
                            <h1 style="margin:0 0 10px 0;font-size:24px;font-weight:700;color:#E2E8F0;letter-spacing:-0.4px;">
                                Welcome to {{ config('app.name') }}!
                            </h1>
                            <p style="margin:0 0 28px 0;font-size:15px;color:#94A3B8;line-height:1.7;">
                                Hi {{ $user->name }}, your account is set up and ready to go. Here's what you can do next.
                            </p>

                            <!-- Feature list -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;background-color:#111827;border:1px solid #1F2937;border-radius:8px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 12px 0;font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:1px;">
                                            Get started
                                        </p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding:5px 0;font-size:14px;color:#94A3B8;line-height:1.5;">
                                                    <span style="color:#CDF12B;margin-right:8px;font-weight:700;">&#10003;</span>
                                                    Connect your Deriv account to start copy trading
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:14px;color:#94A3B8;line-height:1.5;">
                                                    <span style="color:#CDF12B;margin-right:8px;font-weight:700;">&#10003;</span>
                                                    Follow top-performing masters automatically
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:14px;color:#94A3B8;line-height:1.5;">
                                                    <span style="color:#CDF12B;margin-right:8px;font-weight:700;">&#10003;</span>
                                                    Monitor every copied trade in real time
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px;">
                                <tr>
                                    <td align="left" style="border-radius:8px;background-color:#1E45FC;">
                                        <a href="{{ route('dashboard') }}"
                                           target="_blank"
                                           style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                                            Go to Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding-top:24px;">
                            <p style="margin:0;font-size:12px;color:#475569;line-height:1.6;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
