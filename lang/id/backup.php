<?php

return [

    'title' => 'Backup',
    'description' => 'Arsip database dan media terjadwal, beserta proses yang menghasilkannya.',

    'archive' => [
        'title' => 'Arsip',
        'description' => 'Semua arsip yang saat ini tersimpan di tujuan yang dikonfigurasi.',
        'empty' => 'Belum ada arsip. Backup terjadwal pertama akan membuatnya.',
        'filename' => 'Arsip',
        'disk' => 'Disk',
        'size' => 'Ukuran',
        'created_at' => 'Dibuat',
    ],

    'run' => [
        'title' => 'Proses terakhir',
        'description' => 'Sepuluh percobaan backup terakhir, dari mana pun asalnya.',
        'empty' => 'Belum ada proses yang tercatat.',
        'status' => 'Status',
        'started_by' => 'Dimulai oleh',
        'scheduled' => 'Terjadwal',
        'started_at' => 'Mulai',
        'completed_at' => 'Selesai',
    ],

    'status' => [
        'pending' => 'Mengantre',
        'running' => 'Berjalan',
        'completed' => 'Selesai',
        'failed' => 'Gagal',
    ],

    'action' => [
        'run' => 'Backup sekarang',
        'running' => 'Backup sedang berjalan…',
        'download' => 'Unduh arsip',
        'delete' => 'Hapus arsip',
    ],

    'confirm' => [
        'delete' => [
            'title' => 'Hapus arsip ini?',
            'description' => 'Arsip :filename akan dihapus dari disk tujuannya. Tindakan ini tidak dapat dibatalkan.',
            'confirm' => 'Hapus',
            'cancel' => 'Batal',
        ],
    ],

    'message' => [
        'started' => 'Backup sudah masuk antrean dan berjalan di latar belakang.',
        'already_running' => 'Backup lain sedang berjalan. Tunggu sampai selesai sebelum memulai yang baru.',
        'deleted' => 'Arsip berhasil dihapus.',
    ],

    'error' => [
        'failed' => 'Backup tidak selesai.',
        'missing_archive' => 'Backup melaporkan sukses tetapi tidak ada arsip yang ditemukan di tujuan.',
    ],

    'notice' => [
        'worker' => 'Backup berjalan di koneksi antrean :connection dan membutuhkan worker yang memprosesnya.',
    ],

];
