@extends('emails.layout')

@section('content')
  <div style="margin-bottom:24px;">
    <span style="display:inline-block; background-color:#edfce9; color:#003c33; font-size:11px; font-weight:600; padding:3px 10px; border-radius:9999px; margin-bottom:12px; border:1px solid #c7f3be;">
      PEMBAYARAN DIVERIFIKASI
    </span>
    <h1 style="margin:0 0 8px 0; font-size:20px; font-weight:700; color:#17171c; letter-spacing:-0.3px;">
      Bukti Pembayaran Langganan PRÉCIS
    </h1>
    <p style="margin:0; font-size:14px; color:#616161;">
      Halo <strong>{{ $ownerName }}</strong>, pembayaran untuk invoice <strong>{{ $invoiceNumber }}</strong> telah berhasil diverifikasi oleh tim keuangan PRÉCIS.
    </p>
  </div>

  <!-- Receipt Detail Box -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fafafa; border:1px solid #d9d9dd; border-radius:16px; margin:24px 0; overflow:hidden;">
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a; width:45%;">Nomor Invoice:</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-family:monospace; font-weight:600; color:#17171c; text-align:right;">{{ $invoiceNumber }}</td>
    </tr>
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Status Pembayaran:</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:700; color:#003c33; text-align:right;">LUNAS (VERIFIED)</td>
    </tr>
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Total Pembayaran:</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:700; color:#17171c; text-align:right;">
        Rp {{ number_format($totalAmount, 0, ',', '.') }}
      </td>
    </tr>
    <tr>
      <td style="padding:14px 20px; font-size:13px; color:#75758a;">Masa Aktif Hingga:</td>
      <td style="padding:14px 20px; font-size:13px; font-weight:600; color:#17171c; text-align:right;">{{ $activeUntil }}</td>
    </tr>
  </table>

  <div style="text-align:center; margin:32px 0;">
    <a href="{{ $billingUrl }}" style="display:inline-block; background-color:#17171c; color:#ffffff; font-size:13px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:9999px; letter-spacing:0.2px;">
      Lihat Status Langganan
    </a>
  </div>

  <p style="margin:0; font-size:12px; color:#75758a; line-height:1.5;">
    Terima kasih telah mempercayakan operasional bisnis F&amp;B Anda kepada PRÉCIS Workspace System.
  </p>
@endsection
