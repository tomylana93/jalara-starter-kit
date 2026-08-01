<?php

return [

    'page' => [
        'title' => 'Chat',
        'description' => 'Pesan langsung dengan orang lain di aplikasi',
    ],

    'label' => [
        'conversations' => 'Percakapan',
        'messages' => 'Pesan',
        'search' => 'Cari orang',
        'composer' => 'Pesan',
        'unavailable' => 'Tidak tersedia',
        'read' => 'Dibaca',
        'sent' => 'Terkirim',
        'delivered' => 'Terkirim',
        'you' => 'Terkirim',
        'reconnecting' => 'Menyambungkan ulang',
        'image' => 'Gambar',
        'image_preview' => 'Pratinjau gambar chat ukuran penuh',
        'uploading' => 'Mengunggah gambar',
        'today' => 'Hari ini',
        'yesterday' => 'Kemarin',
    ],

    'placeholder' => [
        'search' => 'Cari berdasarkan nama',
        'composer' => 'Tulis pesan',
    ],

    'button' => [
        'send' => 'Kirim',
        'new' => 'Pesan baru',
        'load_older' => 'Muat pesan lama',
        'jump_to_latest' => 'Lompat ke pesan terbaru',
        'minimize' => 'Kecilkan chat',
        'expand' => 'Perbesar chat',
        'close' => 'Tutup chat',
        'retry' => 'Coba lagi',
        'add_image' => 'Tambahkan gambar',
        'remove_image' => 'Hapus gambar',
        'preview_image' => 'Pratinjau gambar',
        'react' => 'Beri reaksi pada pesan',
    ],

    'empty' => [
        'conversations' => 'Belum ada percakapan.',
        'conversations_description' => 'Mulai dengan mencari sebuah nama.',
        'messages' => 'Belum ada pesan.',
        'messages_description' => 'Percakapan dimulai dari pesan pertama.',
        'search' => 'Tidak ada orang yang cocok.',
        'search_hint' => 'Ketik minimal dua karakter.',
        'unselected' => 'Pilih sebuah percakapan.',
        'unselected_description' => 'Pilih percakapan di sebelah kiri, atau mulai yang baru.',
    ],

    'message' => [
        'disabled' => 'Chat sedang dinonaktifkan.',
        'recipient_unavailable' => 'Orang tersebut tidak dapat menerima pesan.',
        'peer_unavailable' => 'Orang ini sudah tidak dapat menerima pesan. Riwayat tetap tersedia.',
        'rate_limited' => 'Terlalu banyak pesan terkirim. Tunggu sebentar lalu coba lagi.',
        'send_failed' => 'Pesan tidak terkirim.',
        'reconnected' => 'Koneksi tersambung kembali.',
        'image_upload_disabled' => 'Upload gambar sedang dinonaktifkan.',
        'image_removed_disabled' => 'Gambar yang dipilih dihapus karena upload gambar dinonaktifkan.',
    ],

    'notification' => [
        'message' => 'Mengirim pesan langsung.',
    ],

    'audit' => [
        'title' => 'Audit chat',
        'description' => 'Catatan baca-saja untuk seluruh pesan langsung',
        'label' => [
            'participants' => 'Peserta',
            'messages' => 'Pesan',
            'last_activity' => 'Aktivitas terakhir',
            'access_log' => 'Log akses',
            'search' => 'Cari peserta',
            'viewer' => 'Dibuka oleh',
            'viewed_at' => 'Waktu dibuka',
            'ip_address' => 'Alamat IP',
            'user_agent' => 'User agent',
        ],
        'placeholder' => [
            'search' => 'Cari berdasarkan nama peserta',
        ],
        'button' => [
            'open' => 'Buka',
            'back' => 'Kembali ke audit',
        ],
        'empty' => [
            'conversations' => 'Belum ada percakapan tercatat.',
            'search' => 'Tidak ada percakapan yang cocok dengan peserta tersebut.',
            'messages' => 'Tidak ada pesan dalam percakapan ini.',
            'logs' => 'Belum ada akses sebelumnya yang tercatat.',
        ],
        'notice' => 'Pembukaan percakapan tercatat permanen dan tidak terlihat oleh pesertanya.',
    ],

];
