@extends('emails.layout')

@php
  $subject     = 'Payment Receipt – ETB ' . number_format($payment->amount, 2);
  $previewText = 'Payment confirmed! ETB ' . number_format($payment->amount, 2) . ' for booking ' . $booking->booking_reference;
@endphp

@section('content')

  {{-- ── Header ──────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);padding:36px 40px 32px;text-align:center;" class="pad-mobile">
      <p style="margin:0 0 4px;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">
        HR<span style="color:#a7f3d0;">&amp;</span>MS
      </p>
      <p style="margin:0 0 24px;font-size:11px;color:rgba(255,255,255,0.65);letter-spacing:0.8px;text-transform:uppercase;">
        SATAAB Hotel
      </p>
      <p style="margin:0 0 10px;font-size:48px;line-height:1;">🧾</p>
      <h1 class="header-title" style="margin:0 0 6px;font-size:26px;font-weight:800;color:#ffffff;line-height:1.2;">
        Payment Receipt
      </h1>
      <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.75);">
        Transaction confirmed &middot; {{ $payment->transaction_id }}
      </p>
    </td>
  </tr>

  {{-- ── Body ────────────────────────────────────────────────────── --}}
  <tr>
    <td style="padding:36px 40px 0;" class="pad-mobile">

      <p style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a202c;">Hello, {{ $user->name }}</p>
      <p style="margin:0 0 28px;font-size:15px;color:#4a5568;line-height:1.7;">
        This is your official payment receipt for booking
        <strong style="color:#1a202c;">{{ $booking->booking_reference }}</strong>.
        Please keep this for your records.
      </p>

      {{-- Receipt details card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f7f8ff;border:1.5px solid #e0e7ff;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 6px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:1px;">
              🧾 Receipt Details
            </p>
          </td>
        </tr>
        @foreach([
          ['Receipt No.',    '<span style="font-family:monospace;color:#059669;">RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) . '</span>'],
          ['Date',           ($payment->paid_at ?? now())->format('d M Y, H:i')],
          ['Transaction ID', '<span style="font-family:monospace;font-size:12px;">' . $payment->transaction_id . '</span>'],
          ['Gateway',        ucfirst(str_replace('_', ' ', $payment->gateway))],
          ['Status',         '<span style="color:#059669;font-weight:800;">✓ Paid</span>'],
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

      {{-- Booking summary card --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f7f8ff;border:1.5px solid #e0e7ff;border-radius:12px;margin-bottom:20px;">
        <tr>
          <td style="padding:20px 22px 6px;">
            <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:1px;">
              🏨 Booking Summary
            </p>
          </td>
        </tr>
        @foreach([
          ['Booking Ref',  '<span style="font-family:monospace;color:#4f46e5;">' . $booking->booking_reference . '</span>'],
          ['Room',         $booking->room->name],
          ['Check-in',     $booking->check_in_date->format('D, d M Y')],
          ['Check-out',    $booking->check_out_date->format('D, d M Y')],
          ['Nights',       $booking->nights()],
          ['Room Rate',    'ETB ' . number_format($booking->room->price_per_night, 2) . '/night'],
          ['Subtotal',     'ETB ' . number_format($booking->total_amount, 2)],
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
        @if($booking->discount_amount > 0)
        <tr>
          <td style="padding:0 22px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="border-top:1px solid #e0e7ff;">
              <tr>
                <td class="info-row-label" style="padding:9px 0;font-size:14px;color:#6b7280;font-weight:600;width:45%;">Discount</td>
                <td class="info-row-value" style="padding:9px 0;font-size:14px;color:#10b981;font-weight:700;text-align:right;">
                  &minus; ETB {{ number_format($booking->discount_amount, 2) }}
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif
        {{-- Total row --}}
        <tr>
          <td style="padding:0 22px 14px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                   style="border-top:2px solid #c7d2fe;margin-top:4px;">
              <tr>
                <td class="info-row-label" style="padding:12px 0 0;font-size:15px;color:#1a202c;font-weight:800;">Total Paid</td>
                <td class="info-row-value" style="padding:12px 0 0;font-size:20px;color:#059669;font-weight:900;text-align:right;">
                  ETB {{ number_format($payment->amount, 2) }}
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      {{-- CTA --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:28px 0;">
        <tr>
          <td style="text-align:center;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
              href="{{ config('app.url') }}/profile"
              style="height:50px;v-text-anchor:middle;width:240px;" arcsize="20%"
              fillcolor="#059669" strokecolor="#059669">
              <w:anchorlock/>
              <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:800;">View Booking Details</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ config('app.url') }}/profile" class="cta-btn"
               style="display:inline-block;padding:15px 40px;background:linear-gradient(135deg,#059669,#10b981);color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;border-radius:10px;">
              View Booking Details
            </a>
            <!--<![endif]-->
          </td>
        </tr>
      </table>

      {{-- Success alert --}}
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
             style="background-color:#f0fdf4;border-left:4px solid #10b981;border-radius:0 8px 8px 0;margin-bottom:24px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;font-size:13.5px;color:#065f46;line-height:1.6;">
              ✅ Your payment has been successfully processed and your room is reserved.
            </p>
          </td>
        </tr>
      </table>

      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:20px;">
        <tr><td style="border-top:1px solid #e2e8f0;"></td></tr>
      </table>

      <p style="margin:0 0 32px;font-size:13.5px;color:#718096;line-height:1.7;">
        If you did not make this payment or have any concerns, contact us immediately at
        <a href="mailto:{{ config('mail.from.address') }}" style="color:#4f46e5;font-weight:600;">{{ config('mail.from.address') }}</a>.
      </p>

    </td>
  </tr>

@endsection
