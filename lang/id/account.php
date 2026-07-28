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
        'delete' => [
            'title' => 'Hapus akun',
            'description' => 'Hapus akun beserta semua resource terkait',
            'warning' => 'Tindakan ini tidak dapat dibatalkan.',
            'confirmation_title' => 'Hapus akun ini secara permanen?',
            'confirmation_description' => 'Semua resource dan data terkait akan dihapus secara permanen. Masukkan password untuk konfirmasi.',
            'label' => [
                'password' => 'Password',
            ],
            'placeholder' => [
                'password' => 'Password',
            ],
            'button' => [
                'cancel' => 'Batal',
                'delete' => 'Hapus akun',
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
