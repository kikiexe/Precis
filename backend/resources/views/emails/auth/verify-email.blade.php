@extends('emails.layout')

@section('content')
  <div style="margin-bottom:24px;">
    <h1 style="margin:0 0 8px 0; font-size:20px; font-weight:700; color:#17171c; letter-spacing:-0.3px;">
      Verifikasi Akun PRÉCIS Anda
    </h1>
    <p style="margin:0; font-size:14px; color:#616161;">
      Halo <strong>{{ $userName }}</strong>, terima kasih telah mendaftar di platform PRÉCIS.
    </p>
  </div>

  <p style="margin:0 0 20px 0; font-size:14px; color:#212121; line-height:1.6;">
    Untuk mengaktifkan akun Anda dan memastikan kepemilikan alamat email ini, silakan klik tombol verifikasi di bawah ini:
  </p>

  <div style="text-align:center; margin:32px 0;">
    <a href="{{ $verificationUrl }}" style="display:inline-block; background-color:#17171c; color:#ffffff; font-size:13px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:9999px; letter-spacing:0.2px;">
      Verifikasi Alamat Email
    </a>
  </div>

  <div style="background-color:#fafafa; border:1px solid #e5e5e5; border-radius:12px; padding:16px; margin:24px 0;">
    <p style="margin:0 0 6px 0; font-size:12px; font-weight:600; color:#17171c;">Kode Token Verifikasi:</p>
    <p style="margin:0; font-family:monospace; font-size:13px; color:#003c33; word-break:break-all;">
      {{ $token }}
    </p>
  </div>

  <p style="margin:0; font-size:12px; color:#75758a; line-height:1.5;">
    Jika tombol di atas tidak berfungsi, Anda dapat menyalin dan membuka tautan berikut di peramban Anda:<br>
    <a href="{{ $verificationUrl }}" style="color:#1863dc; text-decoration:underline; word-break:break-all;">
      {{ $verificationUrl }}
    </a>
  </p>
@endsection
