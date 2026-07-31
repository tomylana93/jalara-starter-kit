<?php

return [

    'title' => 'Dokumentasi',
    'description' => 'Panduan internal untuk menggunakan aplikasi.',

    'button' => [
        'manage' => 'Kelola',
        'read' => 'Buka dokumentasi',
        'create' => 'Dokumentasi baru',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'delete' => 'Hapus',
    ],

    'status' => [
        'draft' => 'Draf',
        'published' => 'Terbit',
    ],

    'empty' => [
        'reader' => 'Belum ada dokumentasi yang diterbitkan.',
        'manage' => 'Belum ada dokumentasi.',
    ],

    'reader' => [
        'list' => 'Daftar dokumentasi',
        'list_description' => 'Pilih dokumentasi yang ingin dibaca.',
    ],

    'manage' => [
        'title' => 'Kelola dokumentasi',
        'description' => 'Atur kategori, urutan, status, dan isi dokumentasi.',

        'category' => [
            'title' => 'Kategori',
            'label' => 'Nama kategori',
            'placeholder' => 'Kategori baru',
            'add' => 'Tambah kategori',
            'move_up' => 'Naikkan kategori',
            'move_down' => 'Turunkan kategori',
            'rename' => 'Ubah nama kategori',
            'rename_description' => 'Nama baru akan muncul di semua daftar kategori.',
            'delete' => 'Hapus kategori',
            'delete_title' => 'Hapus kategori :name?',
            'delete_description' => 'Kategori hanya bisa dihapus setelah tidak lagi memuat dokumentasi.',
        ],

        'document' => [
            'title' => 'Dokumentasi',
            'move_up' => 'Naikkan dokumentasi',
            'move_down' => 'Turunkan dokumentasi',
            'edit' => 'Edit dokumentasi',
            'delete' => 'Hapus dokumentasi',
            'delete_title' => 'Hapus dokumentasi secara permanen?',
            'delete_description' => 'Tindakan ini tidak dapat dibatalkan.',
        ],

        'column' => [
            'title' => 'Judul',
            'category' => 'Kategori',
            'status' => 'Status',
            'actions' => 'Aksi',
        ],
    ],

    'form' => [
        'create' => 'Dokumentasi baru',
        'edit' => 'Edit dokumentasi',
        'description' => 'Susun dokumentasi internal dengan editor terstruktur.',

        'label' => [
            'title' => 'Judul',
            'slug' => 'Slug',
            'category' => 'Kategori',
            'status' => 'Status',
        ],

        'placeholder' => [
            'category' => 'Pilih kategori',
        ],

        'message' => [
            'discard' => 'Buang perubahan yang belum disimpan?',
        ],
    ],

    'search' => [
        'title' => 'Pencarian global',
        'description' => 'Cari navigasi aplikasi dan dokumentasi internal.',
        'placeholder' => 'Cari navigasi atau dokumentasi…',
        'empty' => 'Tidak ada hasil.',

        'group' => [
            'navigation' => 'Navigasi',
            'documentation' => 'Dokumentasi',
        ],
    ],

    'validation' => [
        'invalid_content' => 'Struktur isi dokumentasi tidak valid.',
        'invalid_heading' => 'Dokumentasi hanya mendukung heading tingkat 1 sampai 3.',
        'invalid_link' => 'Tautan hanya boleh memakai HTTP, HTTPS, atau path internal.',
        'category_in_use' => 'Kategori masih memiliki dokumentasi. Pindahkan atau hapus dokumentasinya terlebih dahulu.',
    ],
];
