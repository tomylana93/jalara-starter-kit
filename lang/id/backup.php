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
        'description' => 'Sepuluh percobaan backup dan pemulihan terakhir, dari mana pun asalnya.',
        'empty' => 'Belum ada proses yang tercatat.',
        'type' => 'Jenis',
        'status' => 'Status',
        'started_by' => 'Dimulai oleh',
        'scheduled' => 'Terjadwal',
        'started_at' => 'Mulai',
        'completed_at' => 'Selesai',
    ],

    'type' => [
        'backup' => 'Backup',
        'restore' => 'Pemulihan',
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
        'upload' => 'Unggah backup',
        'restore' => 'Pulihkan arsip',
    ],

    'confirm' => [
        'delete' => [
            'title' => 'Hapus arsip ini?',
            'description' => 'Arsip :filename akan dihapus dari disk tujuannya. Tindakan ini tidak dapat dibatalkan.',
            'confirm' => 'Hapus',
            'cancel' => 'Batal',
        ],
        'restore' => [
            'title' => 'Pulihkan arsip ini?',
            'description' => 'Memulihkan :filename akan mengganti database saat ini dan mengembalikan media dari arsip. Salinan database saat ini diambil lebih dulu, disimpan di samping arsip pada disk tujuan. Semua sesi login berakhir dan pekerjaan yang mengantre dibuang. Pemulihan berjalan di latar belakang.',
            'confirm' => 'Pulihkan',
            'cancel' => 'Batal',
        ],
        'upload' => [
            'title' => 'Unggah arsip',
            'description' => 'Pilih arsip ZIP yang dihasilkan aplikasi ini. Isinya diperiksa satu per satu dan ditolak jika memuat apa pun yang bukan bagian dari backup.',
            'select' => 'Pilih file ZIP',
            'confirm' => 'Unggah',
            'cancel' => 'Batal',
        ],
    ],

    'message' => [
        'started' => 'Backup sudah masuk antrean dan berjalan di latar belakang.',
        'restore_started' => 'Pemulihan sudah masuk antrean dan berjalan di latar belakang. Halaman ini akan melaporkan hasilnya.',
        'already_running' => 'Backup atau pemulihan lain sedang berjalan. Tunggu sampai selesai sebelum memulai yang baru.',
        'deleted' => 'Arsip berhasil dihapus.',
        'uploaded' => 'Arsip berhasil diunggah.',
    ],

    'validation' => [
        'archive' => 'File ini bukan arsip backup dari aplikasi ini.',
    ],

    'error' => [
        'failed' => 'Backup tidak selesai.',
        'missing_archive' => 'Backup melaporkan sukses tetapi tidak ada arsip yang ditemukan di tujuan.',
        'restore_failed' => 'Pemulihan tidak selesai.',
        'restore_missing_archive' => 'Arsip sudah tidak ada di tujuan, jadi tidak ada yang dipulihkan.',
        'restore_unreadable_archive' => 'Arsip tidak bisa dibuka, jadi tidak ada yang dipulihkan.',
        'restore_unsupported_dump' => 'Arsip memuat dump database terkompresi yang tidak bisa dijalankan ulang. Tidak ada yang dipulihkan.',
        'restore_snapshot_failed' => 'Database saat ini tidak bisa disalin lebih dulu, jadi tidak ada yang dipulihkan.',
        'restore_import_failed' => 'Pemulihan berhenti di tengah jalan: database tidak lengkap. Salinan yang diambil sebelumnya ada di disk backup, dan namanya tercatat di log.',
    ],

    'notice' => [
        'worker' => 'Backup berjalan di koneksi antrean :connection dan membutuhkan worker yang memprosesnya.',
    ],

];
