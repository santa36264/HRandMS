<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="x-apple-disable-message-reformatting" />
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no" />
  <title>Email Verification &ndash; {{ config('app.name') }}</title>
  <!--[if mso]>
  <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
  <![endif]-->
  <style>
    body, table, td, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
    table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
    img { -ms-interpolation-mode:bicubic; border:0; height:auto; line-height:100%; outline:none; text-decoration:none; }
    body { margin:0 !important; padding:0 !important; width:100% !important; }
    @media only screen and (max-width:600px) {
      .email-container { width:100% !important; max-width:100% !important; }
      .pad-mobile { padding-left:20px !important; padding-right:20px !important; }
      .otp-digit  { width:40px !important; height:50px !important; line-height:50px !important; font-size:22px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;">

<!-- Preview text -->
<div style="display:none;font-size:1px;color:#f0f4f8;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
  Your verification code is {{ $otp }}. Expires in 2 minutes. Do not share this code.
  &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
</div>

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#f0f4f8;">
  <tr>
    <td style="padding:32px 16px;">

      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="560" class="email-container"
             style="margin:0 auto;background-color:#ffffff;border-radius:16px;overflow:hidden;">

        {{-- ── Header ──────────────────────────────────────────────── --}}
        <tr>
          <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:36px 40px 28px;text-align:center;" class="pad-mobile">
            <p style="margin:0 0 4px;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">
              HR<span style="color:#c4b5fd;">&amp;</span>MS
            </p>
            <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.65);letter-spacing:0.8px;text-transform:uppercase;">
              SATAAB Hotel
            </p>
          </td>
        </tr>

        {{-- ── Body ────────────────────────────────────────────────── --}}
        <tr>
          <td style="padding:36px 40px 0;" class="pad-mobile">

            <p style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1a202c;">
              Hello, {{ $user->name }} &#128075;
            </p>
            <p style="margin:0 0 32px;font-size:15px;color:#4a5568;line-height:1.7;">
              Thanks for signing up. To complete your registration and secure your account,
              please verify your email address using the code below.
            </p>

            {{-- OTP box --}}
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="background-color:#f7f8ff;border:2px dashed #c7d2fe;border-radius:12px;margin-bottom:28px;">
              <tr>
                <td style="padding:28px 24px;text-align:center;">
                  <p style="margin:0 0 16px;font-size:11px;font-weight:700;color:#6366f1;letter-spacing:1.5px;text-transform:uppercase;">
                    Your Verification Code
                  </p>
                  {{-- OTP digits --}}
                  <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                    <tr>
                      @foreach(str_split($otp) as $digit)
                      <td style="padding:0 4px;">
                        <span class="otp-digit"
                              style="display:inline-block;width:52px;height:60px;line-height:60px;background:#ffffff;border:2px solid #e0e7ff;border-radius:10px;font-size:28px;font-weight:800;color:#4f46e5;text-align:center;">
                          {{ $digit }}
                        </span>
                      </td>
                      @endforeach
                    </tr>
                  </table>
                  <p style="margin:16px 0 0;font-size:13px;color:#718096;">
                    This code expires in <strong style="color:#e53e3e;">2 minutes</strong>
                  </p>
                </td>
              </tr>
            </table>

            {{-- Security warning --}}
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="background-color:#fffbeb;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0;margin-bottom:28px;">
              <tr>
                <td style="padding:14px 18px;">
                  <p style="margin:0 0 6px;font-size:13.5px;color:#78350f;line-height:1.6;">
                    &#9888;&#65039; <strong>Never share this code</strong> with anyone, including hotel staff.
                  </p>
                  <p style="margin:0;font-size:13.5px;color:#78350f;line-height:1.6;">
                    &#128274; Our team will never ask for your verification code via phone or email.
                  </p>
                </td>
              </tr>
            </table>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
              <tr><td style="border-top:1px solid #e2e8f0;"></td></tr>
            </table>

            <p style="margin:0 0 32px;font-size:13.5px;color:#718096;line-height:1.7;">
              If you didn't create an account with <strong>{{ config('app.name') }}</strong>,
              you can safely ignore this email. No account will be created without verification.<br /><br />
              Need help? Contact us at
              <a href="mailto:{{ config('mail.from.address') }}" style="color:#4f46e5;font-weight:600;text-decoration:underline;">{{ config('mail.from.address') }}</a>
            </p>

          </td>
        </tr>

        {{-- ── Footer ──────────────────────────────────────────────── --}}
        <tr>
          <td style="background-color:#f7f8ff;border-top:1px solid #e0e7ff;padding:24px 40px;" class="pad-mobile">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
              <tr>
                <td style="text-align:center;padding-bottom:12px;">
                  <a href="{{ config('app.url') }}" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">Home</a>
                  <a href="{{ config('app.url') }}/privacy" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">Privacy Policy</a>
                  <a href="mailto:{{ config('mail.from.address') }}" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;margin:0 10px;">Contact Us</a>
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
                    This email was sent to {{ $user->email }}
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
