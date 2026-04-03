@extends('emails.layout')

@php
  $subject     = 'Booking Confirmed – ' . $booking->booking_reference;
  $previewText = 'Your booking is confirmed! Reference: ' . $booking->booking_reference . '. Check-in: ' . $booking->check_in_date->format('d M Y');
@endphp

@section('content')

  {{-- ── Header ──────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:36px 40px 32px;text-align:center;" class="pad-mobile">
      {{-- Logo --}}
      <p style="margin:0 0 4px;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">
        HR<span style="color:#c4b5fd;">&amp;</span>MS
      </p>
      <p style="margin:0 0 24px;font-size:11px;color:rgba(255,255,255,0.65);letter-spacing:0.8px;text-transform:uppercase;">
        SATAAB Hotel
      </p>
      {{-- Icon + title --}}
      <p style="margin:0 0 10px;font-size:48px;line-height:1;">🎉</p>
      <h1 class="header-title" style="margin:0 0 6px;font-size:26px;font-weight:800;color:#ffffff;line-height:1.2;">
        Booking Confirmed!
      </h1>
      <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.75);">Your reservation is all set</p>
    </td>
  </tr>

  {{-- ── Body ────────────────────────────────────────────────────── --}}
  <tr>
    <td style="padding:36px 40px 0;" class="pad-mobile">

      <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a202c;">Hello, {{ $user->name }} 👋</p>
      <p style="margin:0 0 28px;font-size:15px;color:#4a5568;line-height:1.7;">
        Great news — your booking has been confirmed and your payment has been received.
        We look forward to welcoming you!
      </p>

      {{-- Booking details card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f7f8ff;border:1.5px solid #e0e7ff;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 6px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:1px;">
              📋 Booking Details
            </p>
          </td>
        </tr>
        @foreach([
          ['Reference',    '<span style="font-family:monospace;color:#4f46e5;">' . $booking->booking_reference . '</span>'],
          ['Room',         $booking->room->name . ' (' . ucfirst($booking->room->type) . ')'],
          ['Room Number',  $booking->room->room_number . ' · Floor ' . $booking->room->floor],
          ['Check-in',     $booking->check_in_date->format('D, d M Y')],
          ['Check-out',    $booking->check_out_date->format('D, d M Y')],
          ['Duration',     $booking->nights() . ' night' . ($booking->nights() > 1 ? 's' : '')],
          ['Guests',       $booking->guests_count],
        ] as [$label, $value])
        <tr>
          <td style="padding:0 22px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="border-top:1px solid #e0e7ff;">
              <tr>
                <td class="info-row-label" style="padding:9px 0;font-size:14px;color:#6b7280;font-weight:600;width:45%;">
                  {{ $label }}
                </td>
                <td class="info-row-value" style="padding:9px 0;font-size:14px;color:#1a202c;font-weight:700;text-align:right;">
                  {!! $value !!}
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endforeach
        <tr><td style="height:14px;"></td></tr>
      </table>

      {{-- Payment summary card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f7f8ff;border:1.5px solid #e0e7ff;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 6px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:1px;">
              💳 Payment Summary
            </p>
          </td>
        </tr>
        @foreach([
          ['Amount Paid',     '<span style="color:#10b981;font-weight:800;">ETB ' . number_format($payment->amount, 2) . '</span>'],
          ['Payment Method',  ucfirst(str_replace('_', ' ', $payment->gateway))],
          ['Transaction ID',  '<span style="font-family:monospace;font-size:12px;">' . $payment->transaction_id . '</span>'],
          ['Paid On',         ($payment->paid_at ?? now())->format('d M Y, H:i')],
        ] as [$label, $value])
        <tr>
          <td style="padding:0 22px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="border-top:1px solid #e0e7ff;">
              <tr>
                <td class="info-row-label" style="padding:9px 0;font-size:14px;color:#6b7280;font-weight:600;width:45%;">
                  {{ $label }}
                </td>
                <td class="info-row-value" style="padding:9px 0;font-size:14px;color:#1a202c;font-weight:700;text-align:right;">
                  {!! $value !!}
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endforeach
        <tr><td style="height:14px;"></td></tr>
      </table>

      @if($booking->special_requests)
      {{-- Special requests alert --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#eff6ff;border-left:4px solid #3b82f6;border-radius:0 8px 8px 0;margin-bottom:20px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;font-size:13.5px;color:#1e40af;line-height:1.6;">
              📝 <strong>Special Requests Noted:</strong> {{ $booking->special_requests }}
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
              style="height:50px;v-text-anchor:middle;width:220px;" arcsize="20%"
              fillcolor="#4f46e5" strokecolor="#4f46e5">
              <w:anchorlock/>
              <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:800;">View My Booking</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ config('app.url') }}/profile" class="cta-btn"
               style="display:inline-block;padding:15px 40px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;border-radius:10px;letter-spacing:0.2px;">
              View My Booking
            </a>
            <!--<![endif]-->
          </td>
        </tr>
      </table>

      {{-- Policy alert --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#fffbeb;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0;margin-bottom:24px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0 0 6px;font-size:13.5px;color:#78350f;line-height:1.6;">
              ⏰ <strong>Check-in time:</strong> From 2:00 PM on your arrival date.
            </p>
            <p style="margin:0 0 6px;font-size:13.5px;color:#78350f;line-height:1.6;">
              🔑 Please bring a valid photo ID and this booking reference at check-in.
            </p>
            <p style="margin:0;font-size:13.5px;color:#78350f;line-height:1.6;">
              ❌ <strong>Free cancellation</strong> up to 24 hours before check-in.
            </p>
          </td>
        </tr>
      </table>

      {{-- Divider --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr><td style="border-top:1px solid #e2e8f0;"></td></tr>
      </table>

      <p style="margin:0 0 32px;font-size:13.5px;color:#718096;line-height:1.7;">
        Questions about your booking? Reply to this email or contact us at
        <a href="mailto:{{ config('mail.from.address') }}" style="color:#4f46e5;font-weight:600;">{{ config('mail.from.address') }}</a>.
      </p>

    </td>
  </tr>

@endsection
