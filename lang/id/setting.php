<?php

return [

    'layout' => [
        'title' => 'Pengaturan',
        'description' => 'Kelola konfigurasi seluruh aplikasi',
        'label' => [
            'general' => 'Umum',
            'authentication' => 'Autentikasi',
            'user_provisioning' => 'Penyediaan user',
            'mail' => 'Email',
            'security' => 'Keamanan',
            'branding' => 'Branding',
            'chat' => 'Chat',
        ],
    ],

    'general' => [
        'title' => 'Umum',
        'description' => 'Identitas aplikasi, bahasa, dan tampilan tanggal',
        'label' => [
            'application_name' => 'Nama aplikasi',
            'description' => 'Deskripsi',
            'default_locale' => 'Bahasa default',
            'date_format' => 'Format tanggal',
        ],
        'placeholder' => [
            'application_name' => 'Nama aplikasi',
            'description' => 'Deskripsi singkat aplikasi',
        ],
        'help' => [
            'application_name' => 'Dipakai sebagai akhiran judul dokumen dan identitas runtime untuk email, notifikasi, serta teks lain di sisi server.',
            'description' => 'Ringkasan internal mengenai kegunaan aplikasi.',
            'default_locale' => 'Bahasa yang dipakai ketika tidak ada preferensi lain.',
            'date_format' => 'Format yang dipakai untuk menampilkan tanggal.',
        ],
        'button' => [
            'save' => 'Simpan',
        ],
        'message' => [
            'updated' => 'Pengaturan umum berhasil diperbarui.',
        ],
    ],

    'authentication' => [
        'title' => 'Autentikasi',
        'description' => 'Verifikasi, kekuatan password, dan masa berlaku sesi',
        'label' => [
            'require_email_verification' => 'Wajibkan verifikasi email',
            'password_policy' => 'Kebijakan password',
            'session_lifetime_minutes' => 'Masa berlaku sesi (menit)',
        ],
        'help' => [
            'require_email_verification' => 'Saat aktif, akun yang belum terverifikasi tidak dapat mengakses aplikasi.',
            'password_policy' => 'Berlaku untuk setiap password baru, termasuk password default untuk user baru.',
            'session_lifetime_minutes' => 'Sesi yang tidak aktif berakhir setelah jumlah menit ini.',
        ],
        'button' => [
            'save' => 'Simpan',
        ],
        'message' => [
            'updated' => 'Pengaturan autentikasi berhasil diperbarui.',
        ],
    ],

    'password_policy' => [
        'basic' => 'Dasar',
        'standard' => 'Standar',
        'strict' => 'Ketat',
        'description' => [
            'basic' => 'Minimal 8 karakter.',
            'standard' => 'Minimal 10 karakter, dengan huruf besar, huruf kecil, dan angka.',
            'strict' => 'Minimal 12 karakter, dengan kombinasi huruf besar-kecil, huruf, angka, simbol, dan bukan password yang pernah bocor.',
        ],
    ],

    'branding' => [
        'title' => 'Branding',
        'description' => 'Identitas yang terlihat, layout, warna, dan tipografi',
        'label' => [
            'company_name' => 'Nama perusahaan',
            'footer_text' => 'Teks footer',
            'identity_mode_group' => 'Mode identitas',
            'logo' => 'Logo',
            'logo_dark' => 'Logo (mode gelap)',
            'icon' => 'Ikon',
            'icon_dark' => 'Ikon (mode gelap)',
            'auth_background' => 'Latar autentikasi (layout terbagi)',
            'auth_layout_group' => 'Layout autentikasi',
            'app_layout_group' => 'Layout aplikasi',
            'color_theme_group' => 'Tema warna',
            'font_pair_group' => 'Kombinasi font',
        ],
        'placeholder' => [
            'company_name' => 'Nama perusahaan',
            'footer_text' => 'Teks yang tampil di bagian bawah setiap halaman',
        ],
        'help' => [
            'company_name' => 'Identitas perusahaan yang terlihat pada teks logo dan elemen antarmuka berbrand. Akhiran judul dokumen diatur pada pengaturan umum.',
            'footer_text' => 'Baris opsional yang tampil pada footer halaman autentikasi dan aplikasi.',
            'identity_mode_group' => 'Menentukan apakah area berbrand menampilkan logo, atau ikon berdampingan dengan nama aplikasi.',
            'auth_layout_group' => 'Susunan halaman login, registrasi, dan password.',
            'app_layout_group' => 'Susunan navigasi di dalam aplikasi.',
            'color_theme_group' => 'Token warna brand. Mode terang dan gelap tetap preferensi terpisah.',
            'font_pair_group' => 'Pasangan font untuk judul dan teks biasa di seluruh antarmuka.',
        ],
        'preview' => [
            'font_body' => 'Teks biasa yang nyaman dibaca di seluruh aplikasi',
        ],
        'button' => [
            'save' => 'Simpan',
            'upload' => 'Unggah',
            'replace' => 'Ganti',
            'remove' => 'Hapus',
        ],
        'message' => [
            'updated' => 'Pengaturan branding berhasil diperbarui.',
            'asset_updated' => 'Gambar branding berhasil diperbarui.',
            'asset_removed' => 'Gambar branding berhasil dihapus.',
        ],

        'identity_mode' => [
            'logo' => 'Logo',
            'icon_text' => 'Ikon dan nama aplikasi',
        ],

        'auth_layout' => [
            'simple' => 'Sederhana',
            'card' => 'Kartu',
            'split' => 'Terbagi',
        ],

        'app_layout' => [
            'sidebar' => 'Bilah sisi',
            'header' => 'Bilah atas',
        ],

        'color_theme' => [
            'neutral' => 'Netral',
            'blue' => 'Biru',
            'emerald' => 'Zamrud',
            'violet' => 'Ungu',
            'rose' => 'Merah muda',
            'amber' => 'Kuning',
            'teal' => 'Hijau kebiruan',
            'cyan' => 'Sian',
            'indigo' => 'Nila',
            'orange' => 'Oranye',
        ],

        'font_pair' => [
            'instrument_sans' => 'Instrument Sans + Instrument Sans',
            'space_grotesk_inter' => 'Space Grotesk + Inter',
            'poppins_inter' => 'Poppins + Inter',
            'montserrat_open_sans' => 'Montserrat + Open Sans',
            'playfair_display_source_sans' => 'Playfair Display + Source Sans 3',
        ],
    ],

    'date_format' => [
        'iso' => 'Tahun-Bulan-Tanggal (2026-07-28)',
        'day_month_year_slashed' => 'Tanggal/Bulan/Tahun (28/07/2026)',
        'month_day_year_slashed' => 'Bulan/Tanggal/Tahun (07/28/2026)',
        'day_short_month_year' => 'Tanggal Bulan Tahun (28 Jul 2026)',
    ],

    'locale' => [
        'en' => 'Inggris',
        'id' => 'Indonesia',
    ],

    'user_provisioning' => [
        'title' => 'Penyediaan user',
        'description' => 'Password yang diberikan pada akun yang dibuat administrator',
        'label' => [
            'status' => 'Status',
            'default_password' => 'Password default',
            'default_password_confirmation' => 'Konfirmasi password default',
        ],
        'placeholder' => [
            'default_password' => 'Password default',
            'default_password_confirmation' => 'Konfirmasi password default',
        ],
        'help' => [
            'default_password' => 'Hanya dipakai saat administrator membuat akun. Akun tersebut wajib menggantinya pada login pertama.',
            'stored' => 'Password yang tersimpan tidak pernah ditampilkan lagi. Menyimpan nilai baru akan menggantinya.',
        ],
        'status' => [
            'configured' => 'Sudah dikonfigurasi',
            'not_configured' => 'Belum dikonfigurasi',
        ],
        'button' => [
            'save' => 'Simpan',
            'remove' => 'Hapus password default',
            'cancel' => 'Batal',
            'confirm_remove' => 'Hapus',
        ],
        'confirmation' => [
            'title' => 'Hapus password default?',
            'description' => 'Pembuatan akun tidak dapat dilakukan sampai password default baru dikonfigurasi.',
        ],

        'default_password' => [
            'policy_conflict' => 'Password default untuk pengguna baru tidak memenuhi kebijakan yang dipilih. Perbarui password default terlebih dahulu.',
            'updated' => 'Password default berhasil diperbarui.',
            'removed' => 'Password default berhasil dihapus.',
            'not_configured' => 'Password default untuk pengguna baru belum dikonfigurasi.',
        ],
    ],

    'maintenance' => [
        'message' => 'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi nanti.',
    ],

    'security' => [
        'title' => 'Keamanan',
        'description' => 'Penanganan login gagal dan mode pemeliharaan',
        'label' => [
            'max_failed_login_attempts' => 'Maksimum percobaan login gagal',
            'suspension_duration_minutes' => 'Jendela pembatasan login (menit)',
            'maintenance_enabled' => 'Mode pemeliharaan',
        ],
        'help' => [
            'max_failed_login_attempts' => 'Maksimum percobaan gagal untuk satu kombinasi email dan alamat IP.',
            'suspension_duration_minutes' => 'Lama kombinasi email dan alamat IP tersebut harus menunggu sebelum mencoba lagi.',
            'maintenance_enabled' => 'Saat aktif, aplikasi menampilkan pemberitahuan pemeliharaan.',
        ],
        'alert' => [
            'maintenance_title' => 'Mode pemeliharaan',
            'maintenance' => 'Login, logout, dan halaman pengaturan tetap dapat diakses, dan akun yang memiliki permission pengaturan tetap memiliki akses penuh.',
        ],
        'button' => [
            'save' => 'Simpan',
        ],
        'message' => [
            'updated' => 'Pengaturan keamanan berhasil diperbarui.',
        ],
    ],

    'mail' => [
        'title' => 'Email',
        'description' => 'Identitas pengirim untuk email keluar',
        'label' => [
            'from_name' => 'Nama pengirim',
            'from_address' => 'Alamat pengirim',
        ],
        'placeholder' => [
            'from_name' => 'Nama pengirim',
            'from_address' => 'pengirim@example.com',
        ],
        'help' => [
            'from_name' => 'Nama yang tampil sebagai pengirim setiap pesan.',
            'from_address' => 'Alamat asal pengiriman pesan.',
            'test' => 'Pesan percobaan dikirim ke alamat email administrator yang sedang login.',
        ],
        'button' => [
            'save' => 'Simpan',
            'test' => 'Kirim email percobaan',
        ],
        'message' => [
            'updated' => 'Pengaturan email berhasil diperbarui.',
        ],

        'test' => [
            'subject' => 'Uji konfigurasi email untuk :company',
            'heading' => 'Uji konfigurasi email',
            'intro' => 'Pesan ini memastikan :company dapat mengirim email dengan pengaturan email saat ini.',
            'sender' => 'Pesan dikirim sebagai :name (:address).',
            'sent' => 'Pesan percobaan telah dikirim.',
        ],
    ],

    'chat' => [
        'title' => 'Chat',
        'description' => 'Ketersediaan pesan langsung antar pengguna',
        'label' => [
            'chat_enabled' => 'Aktifkan chat',
        ],
        'help' => [
            'chat_enabled' => 'Saat dinonaktifkan, halaman chat, widget desktop, dan notifikasi chat tertutup untuk semua orang. Percakapan, pesan, dan catatan audit tetap tersimpan.',
        ],
        'button' => [
            'save' => 'Simpan',
        ],
        'message' => [
            'updated' => 'Pengaturan chat diperbarui.',
        ],
    ],

];
