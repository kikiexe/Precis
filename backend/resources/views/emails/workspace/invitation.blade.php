@extends('emails.layout')

@section('content')
  <div style="margin-bottom:24px;">
    <span style="display:inline-block; background-color:#edfce9; color:#003c33; font-size:11px; font-weight:600; padding:3px 10px; border-radius:9999px; margin-bottom:12px; border:1px solid #c7f3be;">
      UNDANGAN WORKSPACE
    </span>
    <h1 style="margin:0 0 8px 0; font-size:20px; font-weight:700; color:#17171c; letter-spacing:-0.3px;">
      Bergabung dengan {{ $workspaceName }}
    </h1>
    <p style="margin:0; font-size:14px; color:#616161;">
      <strong>{{ $inviterName }}</strong> telah mengundang Anda untuk bergabung ke dalam tim di PRÉCIS.
    </p>
  </div>

  <!-- Invitation Detail Box -->
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#fafafa; border:1px solid #d9d9dd; border-radius:16px; margin:24px 0; overflow:hidden;">
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a; width:35%;">Workspace:</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#17171c;">{{ $workspaceName }}</td>
    </tr>
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Posisi / Jabatan:</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#17171c;">{{ $jobTitle }}</td>
    </tr>
    <tr>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; color:#75758a;">Hak Akses (Peran):</td>
      <td style="padding:14px 20px; border-bottom:1px solid #eeece7; font-size:13px; font-weight:600; color:#003c33;">{{ $role }}</td>
    </tr>
    <tr>
      <td style="padding:14px 20px; font-size:13px; color:#75758a;">Penempatan Cabang:</td>
      <td style="padding:14px 20px; font-size:13px; font-weight:600; color:#17171c;">{{ $branchName ?? 'Semua Cabang' }}</td>
    </tr>
  </table>

  <div style="text-align:center; margin:32px 0;">
    <a href="{{ $inviteUrl }}" style="display:inline-block; background-color:#17171c; color:#ffffff; font-size:13px; font-weight:600; text-decoration:none; padding:12px 36px; border-radius:9999px; letter-spacing:0.2px;">
      Terima Undangan Tim
    </a>
  </div>

  <p style="margin:0 0 10px 0; font-size:12px; color:#75758a; line-height:1.5;">
    Tautan undangan ini berlaku hingga <strong>{{ $expiresAt }}</strong>. Jika Anda belum memiliki akun PRÉCIS, Anda akan diarahkan untuk melengkapi data akun terlebih dahulu.
  </p>

  <p style="margin:0; font-size:11px; color:#93939f; line-height:1.4;">
    Jika Anda merasa tidak mengenali pengundang ini, Anda dapat mengabaikan email ini.
  </p>
@endsection
