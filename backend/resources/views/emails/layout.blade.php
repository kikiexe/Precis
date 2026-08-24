<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'PRÉCIS Notification' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eeece7; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eeece7; padding:40px 16px;">
    <tr>
      <td align="center">
        <!-- Main Card Container -->
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px; background-color:#ffffff; border-radius:24px; border:1px solid #d9d9dd; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
          <!-- Header Brand -->
          <tr>
            <td style="padding:28px 32px 22px 32px; border-bottom:1px solid #f0f0f2; background-color:#ffffff;">
              <span style="font-size:18px; font-weight:800; letter-spacing:-0.5px; color:#17171c; text-transform:uppercase;">PRÉCIS</span>
              <span style="font-size:11px; font-family:monospace; color:#75758a; margin-left:8px; padding:2px 6px; background:#f4f4f5; border-radius:4px;">WORKSPACE SYSTEM</span>
            </td>
          </tr>
          <!-- Body Content -->
          <tr>
            <td style="padding:32px; color:#212121; font-size:14px; line-height:1.6;">
              @yield('content')
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding:24px 32px; background-color:#fafafa; border-top:1px solid #f0f0f2; font-size:11px; color:#75758a; line-height:1.5;">
              <p style="margin:0 0 6px 0;">Email ini dikirim secara otomatis oleh sistem PRÉCIS. Mohon untuk tidak membalas email ini.</p>
              <p style="margin:0;">&copy; {{ date('Y') }} PRÉCIS Point of Sale &amp; Workforce Management.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
