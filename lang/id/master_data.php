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

        'label' => [
            'id' => 'ID',
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
