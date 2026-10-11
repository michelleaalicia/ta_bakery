<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
</head>

<body style="margin:0; padding:30px 12px; background:#f4f5f7; font-family:Arial,sans-serif; color:#252525;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style="max-width:560px; background:#ffffff; border:1px solid #e5e7eb; border-radius:8px;">
                    <tr>
                        <td style="padding:28px 30px; text-align:center; border-bottom:1px solid #eeeeee;">
                            <h1 style="margin:0; font-size:20px; line-height:1.5; font-weight:600;">
                                Sistem Informasi Manajemen Produksi Bakery
                            </h1>
                            <p style="margin:8px 0 0; font-size:13px; color:#777777;">
                                Verifikasi Alamat Email
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 30px;">
                            <p style="font-size:15px; line-height:1.7;">
                                Yth. {{ $user->name }},
                            </p>

                            <p style="font-size:15px; line-height:1.7;">
                                Terima kasih telah melakukan registrasi pada Sistem Informasi Manajemen Produksi Bakery.
                                Silakan klik tombol di bawah ini untuk memverifikasi alamat email dan menyelesaikan
                                proses pendaftaran akun Anda.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" align="center"
                                style="margin:28px auto;">
                                <tr>
                                    <td style="background:#252525; border-radius:5px; text-align:center;">
                                        <a href="{{ $verificationUrl }}"
                                            style="display:inline-block; padding:13px 24px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">
                                            Verifikasi Alamat Email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px; line-height:1.7; color:#555555;">
                                Tautan verifikasi berlaku selama {{ $expireMinutes }} menit.
                                Jika tautan kedaluwarsa, silakan minta pengiriman ulang email verifikasi.
                            </p>

                            <p style="font-size:14px; line-height:1.7; color:#555555;">
                                Jika Anda tidak melakukan registrasi, abaikan email ini.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="padding:18px 24px; background:#fafafa; border-top:1px solid #eeeeee; text-align:center;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#888888;">
                                Email otomatis dari Sistem Informasi Manajemen Produksi Bakery.
                                Mohon tidak membalas email ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>