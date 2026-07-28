<?php

return [

    'failed' => 'Email atau password tidak sesuai.',
    'password' => 'Password yang dimasukkan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Coba lagi dalam :seconds detik.',

    'session' => [
        'button' => [
            'logout' => 'Logout',
        ],
    ],

    'login' => [
        'title' => 'Login',
        'description' => 'Masukkan alamat email dan password untuk login',
        'label' => [
            'email' => 'Alamat email',
            'password' => 'Password',
            'remember' => 'Tetap login',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
            'password' => 'Password',
        ],
        'button' => [
            'submit' => 'Login',
        ],
        'link' => [
            'forgot_password' => 'Lupa password?',
        ],
    ],

    'forgot_password' => [
        'title' => 'Lupa password',
        'description' => 'Masukkan alamat email untuk menerima link reset password',
        'label' => [
            'email' => 'Alamat email',
        ],
        'placeholder' => [
            'email' => 'email@example.com',
        ],
        'button' => [
            'submit' => 'Kirim link reset password',
        ],
        'link' => [
            'return' => 'Atau, kembali ke',
            'login' => 'login',
        ],
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'description' => 'Masukkan password baru',
        'label' => [
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Konfirmasi password',
        ],
        'placeholder' => [
            'password' => 'Password',
            'password_confirmation' => 'Konfirmasi password',
        ],
        'button' => [
            'submit' => 'Reset password',
        ],
    ],

    'confirm_password' => [
        'title' => 'Konfirmasi password',
        'description' => 'Area ini dilindungi. Konfirmasi password sebelum melanjutkan.',
        'label' => [
            'password' => 'Password',
        ],
        'button' => [
            'submit' => 'Konfirmasi password',
        ],
    ],

    'verify_email' => [
        'title' => 'Verifikasi email',
        'description' => 'Buka link verifikasi yang dikirim lewat email sebelum melanjutkan.',
        'message' => [
            'instructions' => 'Periksa inbox dan buka link verifikasi. Jika belum diterima, kirim ulang email verifikasi.',
            'sent' => 'Link verifikasi baru sudah dikirim lewat email.',
        ],
        'button' => [
            'resend' => 'Kirim ulang email verifikasi',
        ],
    ],

];
