<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="x-apple-disable-message-reformatting" />
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no" />
  <title>{{ $subject ?? config('app.name') }}</title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style>
    /* Reset */
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

    /* Responsive */
    @media only screen and (max-width: 600px) {
      .email-container { width: 100% !important; max-width: 100% !important; }
      .fluid { width: 100% !important; max-width: 100% !important; height: auto !important; }
      .stack-column, .stack-column-center { display: block !important; width: 100% !important; max-width: 100% !important; direction: ltr !important; }
      .stack-column-center { text-align: center !important; }
      .center-on-narrow { text-align: center !important; display: block !important; margin-left: auto !important; margin-right: auto !important; float: none !important; }
      .pad-mobile { padding-left: 20px !important; padding-right: 20px !important; }
      .hide-mobile { display: none !important; max-height: 0 !important; overflow: hidden !important; }
      .info-row-label, .info-row-value { display: block !important; width: 100% !important; text-align: left !important; }
      .info-row-value { padding-top: 2px !important; padding-bottom: 8px !important; }
      .cta-btn { padding: 14px 24px !important; font-size: 15px !important; }
      .header-title { font-size: 22px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;word-spacing:normal;">

<!-- Preview text (hidden) -->
<div style="display:none;font-size:1px;color:#f0f4f8;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
  {{ $previewText ?? config('app.name') . ' — ' . ($subject ?? '') }}
  &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
</div>

<!-- Outer wrapper -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f0f4f8;">
  <tr>
    <td style="padding:32px 16px;">

      <!-- Email container -->
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="580" class="email-container" style="margin:0 auto;background-color:#ffffff;border-radius:16px;overflow:hidden;">

        @yield('content')

        <!-- ── Footer ─────────────────────────────────────────────── -->
        <tr>
          <td style="background-color:#f7f8ff;border-top:1px solid #e0e7ff;padding:24px 40px;" class="pad-mobile">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
              <tr>
                <td style="text-align:center;padding-bottom:12px;">
                  <a href="{{ config('app.url') }}" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">Home</a>
                  <a href="{{ config('app.url') }}/rooms" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">Rooms</a>
                  <a href="{{ config('app.url') }}/profile" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">My Bookings</a>
                  <a href="mailto:{{ config('mail.from.address') }}" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">Contact</a>
                </td>
              </tr>
              <tr>
                <td style="text-align:center;padding-bottom:6px;">
                  <p style="margin:0;font-size:12px;color:#a0aec0;line-height:1.6;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                  </p>
                </td>
              </tr>
              <tr>
                <td style="text-align:center;">
                  <p style="margin:0;font-size:11px;color:#cbd5e0;">
                    Sent to {{ $recipientEmail ?? '' }}
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
      <!-- /Email container -->

    </td>
  </tr>
</table>

</body>
</html>
