<?php

return [

    'password_policy' => [
        'basic' => 'Dasar',
        'standard' => 'Standar',
        'strict' => 'Ketat',
    ],

    'branding' => [
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
        ],

        'font_preset' => [
            'instrument_sans' => 'Instrument Sans',
            'system_sans' => 'Sans-serif sistem',
            'system_serif' => 'Serif sistem',
            'system_mono' => 'Monospace sistem',
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

    'mail' => [
        'test' => [
            'subject' => 'Uji konfigurasi email untuk :company',
            'heading' => 'Uji konfigurasi email',
            'intro' => 'Pesan ini memastikan :company dapat mengirim email dengan pengaturan email saat ini.',
            'sender' => 'Pesan dikirim sebagai :name (:address).',
            'sent' => 'Pesan percobaan telah dikirim.',
        ],
    ],

];
