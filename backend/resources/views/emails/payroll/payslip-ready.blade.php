@extends('emails.layout')

@section('content')
  <div style="margin-bottom:24px;">
    <span style="display:inline-block; background-color:#edfce9; color:#003c33; font-size:11px; font-weight:600; padding:3px 10px; border-radius:9999px; margin-bottom:12px; border:1px solid #c7f3be;">
      PEMBERITAHUAN GAJI
    </span>
    <h1 style="margin:0 0 8px 0; font-size:20px; font-weight:700; color:#17171c; letter-spacing:-0.3px;">
      Slip Gaji Periode {{ $periodStart }} s/d {{ $periodEnd }}
    </h1>
    <p style="margin:0; font-size:14px; color:#616161;">
      Halo <strong>{{ $employeeName }}</strong>, pembayaran gaji periode ini telah dicairkan oleh manajemen <strong>{{ $workspaceName }}</strong>.
    </p>
  </div>

  <!-- Breakdown Table -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fafafa; border:1px solid #d9d9dd; border-radius:16px; margin:24px 0; overflow:hidden;">
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a; width:50%;">Gaji Pokok</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#17171c; text-align:right;">
        Rp {{ number_format($baseSalary, 0, ',', '.') }}
      </td>
    </tr>
    @if($overtimePay > 0)
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Uang Lembur ({{ $overtimeMinutes }} menit)</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#003c33; text-align:right;">
        +Rp {{ number_format($overtimePay, 0, ',', '.') }}
      </td>
    </tr>
    @endif
    @if($latePenalty > 0)
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Denda Keterlambatan ({{ $lateMinutes }} menit)</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#b30000; text-align:right;">
        -Rp {{ number_format($latePenalty, 0, ',', '.') }}
      </td>
    </tr>
    @endif
    @if($cashAdvanceDeduction > 0)
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Potongan Kasbon / Pinjaman</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#b30000; text-align:right;">
        -Rp {{ number_format($cashAdvanceDeduction, 0, ',', '.') }}
      </td>
    </tr>
    @endif
    <tr style="background-color:#ffffff;">
      <td style="padding:16px 20px; font-size:14px; font-weight:700; color:#17171c;">Total Gaji Bersih (Net)</td>
      <td style="padding:16px 20px; font-size:16px; font-weight:800; color:#003c33; text-align:right;">
        Rp {{ number_format($netSalary, 0, ',', '.') }}
      </td>
    </tr>
  </table>

  <div style="text-align:center; margin:32px 0;">
    <a href="{{ $slipUrl }}" style="display:inline-block; background-color:#17171c; color:#ffffff; font-size:13px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:9999px; letter-spacing:0.2px;">
      Buka Slip Gaji Digital
    </a>
  </div>

  <p style="margin:0; font-size:12px; color:#75758a; line-height:1.5;">
    Rincian presensi harian dan rekap jam kerja dapat diakses melalui menu Keuangan &gt; Slip Gaji pada aplikasi portal staf.
  </p>
@endsection
