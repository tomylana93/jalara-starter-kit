<?php

return [

    'layout' => [
        'title' => 'Akun',
        'description' => 'Kelola akun, security, dan preferensi',
        'label' => [
            'profile' => 'Profil',
            'security' => 'Security',
            'api_tokens' => 'Token API',
        ],
    ],

    'profile' => [
        'title' => 'Profil',
        'description' => 'Perbarui nama dan alamat email',
        'label' => [
            'name' => 'Nama',
            'email' => 'Alamat email',
            'avatar' => 'Avatar',
        ],
        'placeholder' => [
            'name' => 'Nama lengkap',
            'email' => 'Alamat email',
        ],
        'button' => [
            'save' => 'Simpan',
            'upload' => 'Unggah',
            'replace' => 'Ganti',
            'remove' => 'Hapus',
        ],
        'message' => [
            'updated' => 'Profil berhasil diperbarui.',
            'avatar_updated' => 'Avatar berhasil diperbarui.',
            'avatar_removed' => 'Avatar berhasil dihapus.',
        ],
        'disable' => [
            'title' => 'Nonaktifkan akun',
            'description' => 'Nonaktifkan akses tanpa menghapus data akun',
            'warning' => 'Akun akan logout dan memerlukan administrator untuk diaktifkan kembali.',
            'confirmation_title' => 'Nonaktifkan akun ini?',
            'confirmation_description' => 'Akun dan data terkait tetap tersimpan. Masukkan password untuk konfirmasi logout dan penonaktifan akses.',
            'label' => [
                'password' => 'Password',
                'warning' => 'Peringatan',
            ],
            'placeholder' => [
                'password' => 'Password',
            ],
            'button' => [
                'cancel' => 'Batal',
                'disable' => 'Nonaktifkan akun',
            ],
        ],
    ],

    'security' => [
        'title' => 'Perbarui password',
        'description' => 'Gunakan password yang panjang dan acak agar akun tetap aman',
        'label' => [
            'current_password' => 'Password saat ini',
            'password' => 'Password baru',
            'password_confirmation' => 'Konfirmasi password',
        ],
        'placeholder' => [
            'current_password' => 'Password saat ini',
            'password' => 'Password baru',
            'password_confirmation' => 'Konfirmasi password',
        ],
        'button' => [
            'save' => 'Simpan',
        ],
        'message' => [
            'updated' => 'Password berhasil diperbarui.',
            'must_change_password_title' => 'Password wajib diganti',
            'must_change_password' => 'Tetapkan password baru sebelum melanjutkan ke aplikasi.',
        ],
    ],

    'api_token' => [
        'title' => 'Token API',
        'description' => 'Terbitkan token untuk mengautentikasi request API dari client tanpa session browser',
        'label' => [
            'name' => 'Nama token',
            'created' => 'Dibuat',
            'last_used' => 'Terakhir dipakai',
            'never_used' => 'Belum pernah dipakai',
            'plain_text' => 'Token baru',
        ],
        'placeholder' => [
            'name' => 'Laptop, deploy bot, aplikasi mobile',
        ],
        'button' => [
            'create' => 'Buat token',
            'revoke' => 'Cabut',
            'copy' => 'Salin',
            'copied' => 'Tersalin',
            'cancel' => 'Batal',
            'dismiss' => 'Selesai',
        ],
        'message' => [
            'created' => 'Token dibuat.',
            'revoked' => 'Token dicabut.',
            'copy_once' => 'Token hanya ditampilkan sekali. Salin sebelum meninggalkan halaman ini.',
        ],
        'empty' => [
            'title' => 'Belum ada token',
            'description' => 'Token mengautentikasi request API tanpa session browser.',
        ],
        'confirmation_title' => 'Cabut token ini?',
        'confirmation_description' => 'Client yang memakai token ini langsung kehilangan akses. Tindakan ini tidak bisa dibatalkan.',
    ],

    'appearance' => [
        'label' => [
            'light' => 'Terang',
            'dark' => 'Gelap',
            'system' => 'Sistem',
        ],
        'button' => [
            'toggle' => 'Ubah tampilan',
        ],
    ],

];
