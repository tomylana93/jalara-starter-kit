<?php

return [

    'upload' => [
        'status' => [
            'pending' => 'Menunggu diproses.',
            'processing' => 'Memproses gambar.',
        ],
        'message' => [
            'cancelled' => 'Upload dibatalkan.',
            'conflict' => 'Upload lain untuk gambar ini masih berjalan.',
            'conflict_other_owner' => 'Administrator lain sedang mengunggah gambar ini. Coba lagi setelah selesai.',
            'timed_out' => 'Proses berjalan lebih lama dari perkiraan.',
        ],
        'error' => [
            'processing_failed' => 'Gambar gagal diproses.',
            'unauthorized' => 'Gambar tidak dapat diterapkan karena akses berubah.',
            'target_unavailable' => 'Gambar tidak dapat diterapkan karena tujuannya sudah tidak tersedia.',
        ],
        'button' => [
            'cancel' => 'Batalkan upload',
            'retry' => 'Coba lagi',
            'check_again' => 'Periksa lagi',
        ],
    ],

];
