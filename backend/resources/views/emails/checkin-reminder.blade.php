@extends('emails.layout')

@php
  $subject     = 'Check-in Tomorrow – ' . $booking->room->name;
  $previewText = 'Your stay begins tomorrow! Check-in from 2:00 PM. Reference: ' . $booking->booking_reference;
@endphp

@section('content')

  {{-- ── Header ──────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(135deg,#0ea5e9 0%,#6366f1 100%);padding:36px 40px 32px;text-align:center;" class="pad-mobile">
      <p style="margin:0 0 4px;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">
        HR<span style="color:#bae6fd;">&amp;</span>MS
      </p>
      <p style="margin:0 0 24px;font-size:11px;color:rgba(255,255,255,0.65);letter-spacing:0.8px;text-transform:uppercase;">
        SATAAB Hotel
      </p>
      <p style="margin:0 0 10px;font-size:48px;line-height:1;">🔔</p>
      <h1 class="header-title" style="margin:0 0 6px;font-size:26px;font-weight:800;color:#ffffff;line-height:1.2;">
        Check-in Tomorrow!
      </h1>
      <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.75);">
        {{ $booking->check_in_date->format('l, d F Y') }}
      </p>
    </td>
  </tr>

  {{-- ── Body ────────────────────────────────────────────────────── --}}
  <tr>
    <td style="padding:36px 40px 0;" class="pad-mobile">

      <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a202c;">Hello, {{ $user->name }} 👋</p>
      <p style="margin:0 0 28px;font-size:15px;color:#4a5568;line-height:1.7;">
        Your stay at <strong style="color:#1a202c;">{{ config('app.name') }}</strong> begins tomorrow.
        We're getting everything ready for your arrival — here's what you need to know.
      </p>

      {{-- Reservation card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f7f8ff;border:1.5px solid #e0e7ff;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 6px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:1px;">
              📋 Your Reservation
            </p>
          </td>
        </tr>
        @foreach([
          ['Reference',   '<span style="font-family:monospace;color:#4f46e5;">' . $booking->booking_reference . '</span>'],
          ['Room',        $booking->room->name . ' · Room ' . $booking->room->room_number],
          ['Floor',       'Floor ' . $booking->room->floor],
          ['Check-in',    '<span style="color:#0ea5e9;font-weight:800;">' . $booking->check_in_date->format('D, d M Y') . '</span>'],
          ['Check-out',   $booking->check_out_date->format('D, d M Y')],
          ['Guests',      $booking->guests_count],
        ] as [$label, $value])
        <tr>
          <td style="padding:0 22px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="border-top:1px solid #e0e7ff;">
              <tr>
                <td class="info-row-label" style="padding:9px 0;font-size:14px;color:#6b7280;font-weight:600;width:45%;">{{ $label }}</td>
                <td class="info-row-value" style="padding:9px 0;font-size:14px;color:#1a202c;font-weight:700;text-align:right;">{!! $value !!}</td>
              </tr>
            </table>
          </td>
        </tr>
        @endforeach
        <tr><td style="height:14px;"></td></tr>
      </table>

      {{-- Pre-arrival checklist --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 8px;">
            <p style="margin:0 0 16px;font-size:11px;font-weight:700;color:#0284c7;text-transform:uppercase;letter-spacing:1px;">
              ✅ Pre-Arrival Checklist
            </p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
              @foreach([
                ['📄', 'Bring a <strong>valid photo ID</strong> (passport or national ID)'],
                ['🔖', 'Have your <strong>booking reference</strong> ready: <code style="background:#e0f2fe;padding:2px 6px;border-radius:4px;font-size:12px;color:#0369a1;font-family:monospace;">' . $booking->booking_reference . '</code>'],
                ['🕑', '<strong>Check-in time:</strong> From 2:00 PM — early check-in subject to availability'],
                ['🚗', 'Parking is available on-site — please inform reception on arrival'],
                ['📱', 'Save our number: <strong>' . config('app.phone', '+251 11 XXX XXXX') . '</strong>'],
              ] as [$icon, $text])
              <tr>
                <td style="padding:0 0 12px;font-size:14px;color:#374151;line-height:1.5;">
                  {{ $icon }} &nbsp;{!! $text !!}
                </td>
              </tr>
              @endforeach
            </table>
          </td>
        </tr>
      </table>

      @if($booking->special_requests)
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#eff6ff;border-left:4px solid #3b82f6;border-radius:0 8px 8px 0;margin-bottom:20px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;font-size:13.5px;color:#1e40af;line-height:1.6;">
              📝 <strong>Your special requests have been noted:</strong><br>{{ $booking->special_requests }}
            </p>
          </td>
        </tr>
      </table>
      @endif

      {{-- CTA --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:28px 0;">
        <tr>
          <td style="text-align:center;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
              href="{{ config('app.url') }}/profile"
              style="height:50px;v-text-anchor:middle;width:240px;" arcsize="20%"
              fillcolor="#0ea5e9" strokecolor="#0ea5e9">
              <w:anchorlock/>
              <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:800;">View Booking Details</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ config('app.url') }}/profile" class="cta-btn"
               style="display:inline-block;padding:15px 40px;background:linear-gradient(135deg,#0ea5e9,#6366f1);color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;border-radius:10px;">
              View Booking Details
            </a>
            <!--<![endif]-->
          </td>
        </tr>
      </table>

      {{-- Cancellation warning --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#fffbeb;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0;margin-bottom:24px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;font-size:13.5px;color:#78350f;line-height:1.6;">
              ⚠️ <strong>Last-minute cancellation:</strong> Free cancellation is no longer available as check-in is within 24 hours.
            </p>
          </td>
        </tr>
      </table>

      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr><td style="border-top:1px solid #e2e8f0;"></td></tr>
      </table>

      <p style="margin:0 0 32px;font-size:13.5px;color:#718096;line-height:1.7;">
        Need to make changes or have questions? Contact us at
        <a href="mailto:{{ config('mail.from.address') }}" style="color:#4f46e5;font-weight:600;">{{ config('mail.from.address') }}</a>
        or call <strong>{{ config('app.phone', '+251 11 XXX XXXX') }}</strong>.
        We look forward to seeing you tomorrow!
      </p>

    </td>
  </tr>

@endsection
