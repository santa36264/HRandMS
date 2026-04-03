@extends('emails.layout')

@php
  $subject     = 'How Was Your Stay? Share Your Experience';
  $previewText = 'We hope you had a wonderful stay! Share your experience and help future guests.';
@endphp

@section('content')

  {{-- ── Header ──────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);padding:36px 40px 32px;text-align:center;" class="pad-mobile">
      <p style="margin:0 0 4px;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">
        HR<span style="color:#fde68a;">&amp;</span>MS
      </p>
      <p style="margin:0 0 24px;font-size:11px;color:rgba(255,255,255,0.65);letter-spacing:0.8px;text-transform:uppercase;">
        SATAAB Hotel
      </p>
      <p style="margin:0 0 10px;font-size:48px;line-height:1;">&#11088;</p>
      <h1 class="header-title" style="margin:0 0 6px;font-size:26px;font-weight:800;color:#ffffff;line-height:1.2;">
        How Was Your Stay?
      </h1>
      <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.75);">We'd love to hear your feedback</p>
    </td>
  </tr>

  {{-- ── Body ────────────────────────────────────────────────────── --}}
  <tr>
    <td style="padding:36px 40px 0;" class="pad-mobile">

      <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a202c;">Hello, {{ $user->name }} &#128075;</p>
      <p style="margin:0 0 28px;font-size:15px;color:#4a5568;line-height:1.7;">
        We hope you had a wonderful stay at <strong style="color:#1a202c;">{{ config('app.name') }}</strong>.
        Your experience matters to us &mdash; it takes just 2 minutes and helps future guests make the right choice.
      </p>

      {{-- Stay summary card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f7f8ff;border:1.5px solid #e0e7ff;border-radius:12px;margin-bottom:24px;">
        <tr>
          <td style="padding:20px 22px 6px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:1px;">
              &#127968; Your Recent Stay
            </p>
          </td>
        </tr>
        @foreach([
          ['Room',      $booking->room->name . ' (' . ucfirst($booking->room->type) . ')'],
          ['Check-in',  $booking->check_in_date->format('D, d M Y')],
          ['Check-out', $booking->check_out_date->format('D, d M Y')],
          ['Duration',  $booking->nights() . ' night' . ($booking->nights() > 1 ? 's' : '')],
        ] as [$label, $value])
        <tr>
          <td style="padding:0 22px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="border-top:1px solid #e0e7ff;">
              <tr>
                <td class="info-row-label" style="padding:9px 0;font-size:14px;color:#6b7280;font-weight:600;width:45%;">{{ $label }}</td>
                <td class="info-row-value" style="padding:9px 0;font-size:14px;color:#1a202c;font-weight:700;text-align:right;">{{ $value }}</td>
              </tr>
            </table>
          </td>
        </tr>
        @endforeach
        <tr><td style="height:14px;"></td></tr>
      </table>

      {{-- Star rating row --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:24px;">
        <tr>
          <td style="text-align:center;padding-bottom:12px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:1px;">
              Tap to rate your overall experience
            </p>
          </td>
        </tr>
        <tr>
          <td style="text-align:center;padding-bottom:10px;">
            @for($i = 1; $i <= 5; $i++)
            <a href="{{ config('app.url') }}/profile?review={{ $booking->id }}&rating={{ $i }}"
               style="display:inline-block;width:48px;height:48px;line-height:48px;background:#ffffff;border:2px solid #fde68a;border-radius:10px;font-size:26px;text-align:center;text-decoration:none;margin:0 3px;color:#f59e0b;">
              &#9733;
            </a>
            @endfor
          </td>
        </tr>
        <tr>
          <td style="text-align:center;">
            <p style="margin:0;font-size:12px;color:#9ca3af;">1 = Poor &nbsp;&middot;&nbsp; 5 = Excellent</p>
          </td>
        </tr>
      </table>

      {{-- CTA --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;">
        <tr>
          <td style="text-align:center;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
              href="{{ config('app.url') }}/profile?tab=bookings&review={{ $booking->id }}"
              style="height:50px;v-text-anchor:middle;width:220px;" arcsize="20%"
              fillcolor="#f59e0b" strokecolor="#f59e0b">
              <w:anchorlock/>
              <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:800;">Write a Full Review</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ config('app.url') }}/profile?tab=bookings&review={{ $booking->id }}" class="cta-btn"
               style="display:inline-block;padding:15px 40px;background:linear-gradient(135deg,#f59e0b,#ef4444);color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;border-radius:10px;">
              Write a Full Review
            </a>
            <!--<![endif]-->
          </td>
        </tr>
      </table>

      {{-- What to review card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 8px;">
            <p style="margin:0 0 16px;font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:1px;">
              &#128172; What to Include
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
              <tr><td style="padding:0 0 12px;font-size:13.5px;color:#78350f;line-height:1.5;">&#129529; &nbsp;<strong>Cleanliness</strong> &mdash; Was the room clean and well-maintained?</td></tr>
              <tr><td style="padding:0 0 12px;font-size:13.5px;color:#78350f;line-height:1.5;">&#128718; &nbsp;<strong>Service</strong> &mdash; How was the staff and responsiveness?</td></tr>
              <tr><td style="padding:0 0 12px;font-size:13.5px;color:#78350f;line-height:1.5;">&#128205; &nbsp;<strong>Location</strong> &mdash; Was the hotel easy to reach?</td></tr>
              <tr><td style="padding:0 0 12px;font-size:13.5px;color:#78350f;line-height:1.5;">&#128161; &nbsp;<strong>Overall</strong> &mdash; Would you recommend us to others?</td></tr>
            </table>
          </td>
        </tr>
      </table>

      {{-- Thank you alert --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f0fdf4;border-left:4px solid #10b981;border-radius:0 8px 8px 0;margin-bottom:24px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;font-size:13.5px;color:#065f46;line-height:1.6;">
              &#128591; Thank you for staying with us. We hope to welcome you back soon!
            </p>
          </td>
        </tr>
      </table>

      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr><td style="border-top:1px solid #e2e8f0;"></td></tr>
      </table>

      <p style="margin:0 0 32px;font-size:13.5px;color:#718096;line-height:1.7;">
        If you had any issues during your stay that weren't resolved, please contact us at
        <a href="mailto:{{ config('mail.from.address') }}" style="color:#4f46e5;font-weight:600;">{{ config('mail.from.address') }}</a>
        &mdash; we'd like to make it right.
      </p>

    </td>
  </tr>

@endsection
