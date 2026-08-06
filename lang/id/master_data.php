<?php

return [

    'layout' => [
        'title' => 'Master data',
        'description' => 'Data acuan yang dipakai aplikasi.',
    ],

    'user' => [
        'title' => 'User',
        'description' => 'Daftar user aplikasi beserta role dan status aksesnya.',

        'create' => [
            'title' => 'Tambah user',
            'description' => 'User baru langsung aktif dan masuk memakai default password yang sudah diatur.',
        ],

        'edit' => [
            'title' => 'Ubah user',
            'description' => 'Ubah nama, email, role, atau status akses user yang sudah ada.',
        ],

        'import' => [
            'title' => 'Import user',
            'description' => 'Membuat banyak user sekaligus dari sebuah spreadsheet. File diterima seluruhnya atau ditolak seluruhnya.',

            'help' => 'Template memperlihatkan kolom yang diharapkan. Kolom yang tidak dikenal diabaikan, dan setiap baris menjadi user baru.',

            'label' => [
                'file' => 'Spreadsheet',
            ],

            'button' => [
                'open' => 'Import',
                'template' => 'Unduh template',
                'submit' => 'Import user',
            ],

            'message' => [
                'imported' => ':count user berhasil dibuat.',
                'password_missing' => 'Default password harus diatur lebih dulu di pengaturan user provisioning sebelum user bisa di-import.',
            ],

            'error' => [
                'more' => 'Dan :count masalah lainnya.',
            ],

            'validation' => [
                'unreadable' => 'File tidak bisa dibaca sebagai spreadsheet XLSX.',
                'columns' => 'Baris header tidak memuat kolom :columns.',
                'empty' => 'Spreadsheet tidak memuat baris data.',
                'too_many_rows' => 'Satu import menerima maksimal :max baris.',
                'row' => 'Baris :row: :message',
            ],
        ],

        'label' => [
            'name' => 'Nama',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
            'created_at' => 'Dibuat',
            'actions' => 'Aksi',
        ],

        'placeholder' => [
            'name' => 'Nama lengkap',
            'email' => 'nama@example.com',
            'role' => 'Pilih role',
            'status' => 'Pilih status',
            'search' => 'Cari berdasarkan nama atau email…',
        ],

        'button' => [
            'create' => 'Tambah user',
            'save' => 'Simpan',
            'cancel' => 'Batal',
            'edit' => 'Ubah',
            'export' => 'Ekspor XLSX',
        ],

        'message' => [
            'created' => 'User berhasil dibuat.',
            'updated' => 'User berhasil diperbarui.',
        ],

        'empty' => [
            'title' => 'User tidak ditemukan',
            'description' => 'Tidak ada user yang cocok dengan pencarian saat ini.',
        ],

        'filter' => [
            'status' => 'Status',
            'role' => 'Role',
        ],

        'role_missing' => 'Tanpa role',
    ],

];
