@extends('emails.layout')

@section('content')
  <div style="margin-bottom:24px;">
    <h1 style="margin:0 0 8px 0; font-size:20px; font-weight:700; color:#17171c; letter-spacing:-0.3px;">
      Atur Ulang Kata Sandi Akun
    </h1>
    <p style="margin:0; font-size:14px; color:#616161;">
      Halo <strong>{{ $userName }}</strong>, kami menerima permintaan untuk mengatur ulang kata sandi akun PRÉCIS Anda.
    </p>
  </div>

  <p style="margin:0 0 20px 0; font-size:14px; color:#212121; line-height:1.6;">
    Silakan klik tombol di bawah ini untuk membuat kata sandi baru. Tautan ini hanya berlaku selama <strong>60 menit</strong>.
  </p>

  <div style="text-align:center; margin:32px 0;">
    <a href="{{ $resetUrl }}" style="display:inline-block; background-color:#17171c; color:#ffffff; font-size:13px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:9999px; letter-spacing:0.2px;">
      Atur Ulang Kata Sandi
    </a>
  </div>

  <div style="background-color:#fafafa; border:1px solid #e5e5e5; border-radius:12px; padding:16px; margin:24px 0;">
    <p style="margin:0 0 6px 0; font-size:12px; font-weight:600; color:#17171c;">Kode Token Pemulihan:</p>
    <p style="margin:0; font-family:monospace; font-size:13px; color:#003c33; word-break:break-all;">
      {{ $token }}
    </p>
  </div>

  <p style="margin:0; font-size:12px; color:#75758a; line-height:1.5;">
    Jika Anda tidak meminta pengaturan ulang kata sandi, mohon abaikan email ini. Akun dan kata sandi Anda tetap aman.
  </p>
@endsection
