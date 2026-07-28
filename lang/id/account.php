<?php

return [

    'layout' => [
        'title' => 'Akun',
        'description' => 'Kelola akun, security, dan preferensi',
        'label' => [
            'profile' => 'Profil',
            'security' => 'Security',
            'appearance' => 'Tampilan',
        ],
    ],

    'profile' => [
        'title' => 'Profil',
        'description' => 'Perbarui nama dan alamat email',
        'label' => [
            'name' => 'Nama',
            'email' => 'Alamat email',
        ],
        'placeholder' => [
            'name' => 'Nama lengkap',
            'email' => 'Alamat email',
        ],
        'button' => [
            'save' => 'Simpan',
        ],
        'message' => [
            'updated' => 'Profil berhasil diperbarui.',
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

    'appearance' => [
        'title' => 'Tampilan',
        'description' => 'Perbarui pengaturan tampilan akun',
        'label' => [
            'light' => 'Terang',
            'dark' => 'Gelap',
            'system' => 'Sistem',
        ],
    ],

];
