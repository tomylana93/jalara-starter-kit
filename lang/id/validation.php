<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut berisi pesan error default yang dipakai
    | validator. Beberapa aturan memiliki beberapa versi, misalnya
    | aturan ukuran. Silakan ubah setiap pesan di sini sesuai kebutuhan.
    |
    */

    'accepted' => ':Attribute harus disetujui.',
    'accepted_if' => ':Attribute harus disetujui jika :other :value.',
    'active_url' => ':Attribute harus berupa URL yang valid.',
    'after' => ':Attribute harus tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, strip, dan underscore.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'any_of' => ':Attribute tidak valid.',
    'array' => ':Attribute harus berupa array.',
    'array_keys' => ':Attribute hanya boleh memiliki kunci berikut: :values.',
    'ascii' => ':Attribute hanya boleh berisi karakter alfanumerik dan simbol single-byte.',
    'base64' => ':Attribute harus berupa string Base64 yang valid.',
    'before' => ':Attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus memiliki :min sampai :max item.',
        'file' => ':Attribute harus berukuran :min sampai :max kilobyte.',
        'numeric' => ':Attribute harus di antara :min sampai :max.',
        'string' => ':Attribute harus :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus true atau false.',
    'can' => ':Attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => ':Attribute tidak memuat nilai yang diperlukan.',
    'current_password' => 'Password salah.',
    'date' => ':Attribute harus tanggal yang valid.',
    'date_equals' => ':Attribute harus tanggal yang sama dengan :date.',
    'date_format' => ':Attribute harus sesuai format :format.',
    'decimal' => ':Attribute harus memiliki :decimal angka desimal.',
    'declined' => ':Attribute harus ditolak.',
    'declined_if' => ':Attribute harus ditolak jika :other :value.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus :digits digit.',
    'digits_between' => ':Attribute harus :min sampai :max digit.',
    'dimensions' => 'Ukuran gambar :attribute tidak valid.',
    'distinct' => ':Attribute memiliki nilai duplikat.',
    'doesnt_contain' => ':Attribute tidak boleh memuat: :values.',
    'doesnt_end_with' => ':Attribute tidak boleh diakhiri dengan: :values.',
    'doesnt_start_with' => ':Attribute tidak boleh diawali dengan: :values.',
    'email' => ':Attribute harus berupa email yang valid.',
    'encoding' => ':Attribute harus dikodekan dalam :encoding.',
    'ends_with' => ':Attribute harus diakhiri dengan: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => ':Attribute harus memiliki ekstensi berikut: :values.',
    'file' => ':Attribute harus berupa file.',
    'filled' => ':Attribute harus memiliki nilai.',
    'gt' => [
        'array' => ':Attribute harus memiliki lebih dari :value item.',
        'file' => ':Attribute harus lebih dari :value kilobyte.',
        'numeric' => ':Attribute harus lebih dari :value.',
        'string' => ':Attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus memiliki :value item atau lebih.',
        'file' => ':Attribute harus :value kilobyte atau lebih.',
        'numeric' => ':Attribute harus :value atau lebih.',
        'string' => ':Attribute harus :value karakter atau lebih.',
    ],
    'hex_color' => ':Attribute harus berupa warna heksadesimal yang valid.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute harus ada di :other.',
    'in_array_keys' => ':Attribute harus memuat setidaknya satu dari kunci berikut: :values.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'ip' => ':Attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':Attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':Attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':Attribute harus berupa string JSON yang valid.',
    'list' => ':Attribute harus berupa list.',
    'lowercase' => ':Attribute harus menggunakan huruf kecil.',
    'lt' => [
        'array' => ':Attribute harus memiliki kurang dari :value item.',
        'file' => ':Attribute harus kurang dari :value kilobyte.',
        'numeric' => ':Attribute harus kurang dari :value.',
        'string' => ':Attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':Attribute tidak boleh memiliki lebih dari :value item.',
        'file' => ':Attribute harus :value kilobyte atau kurang.',
        'numeric' => ':Attribute harus :value atau kurang.',
        'string' => ':Attribute harus :value karakter atau kurang.',
    ],
    'mac_address' => ':Attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':Attribute tidak boleh memiliki lebih dari :max item.',
        'file' => ':Attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => ':Attribute tidak boleh lebih dari :max.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => ':Attribute tidak boleh lebih dari :max digit.',
    'mimes' => ':Attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => ':Attribute harus berupa file dengan tipe: :values.',
    'min' => [
        'array' => ':Attribute minimal memiliki :min item.',
        'file' => ':Attribute minimal :min kilobyte.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'min_digits' => ':Attribute minimal :min digit.',
    'missing' => ':Attribute harus kosong.',
    'missing_if' => ':Attribute harus kosong jika :other :value.',
    'missing_unless' => ':Attribute harus kosong kecuali :other :value.',
    'missing_with' => ':Attribute harus kosong jika :values ada.',
    'missing_with_all' => ':Attribute harus kosong jika :values ada.',
    'multiple_of' => ':Attribute harus kelipatan dari :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute minimal memuat satu huruf.',
        'mixed' => ':Attribute minimal memuat satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute minimal memuat satu angka.',
        'symbols' => ':Attribute minimal memuat satu simbol.',
        'uncompromised' => ':Attribute pernah muncul dalam kebocoran data. Gunakan :attribute lain.',
    ],
    'present' => ':Attribute harus ada.',
    'present_if' => ':Attribute harus ada jika :other :value.',
    'present_unless' => ':Attribute harus ada kecuali :other :value.',
    'present_with' => ':Attribute harus ada jika :values ada.',
    'present_with_all' => ':Attribute harus ada jika :values ada.',
    'prohibited' => ':Attribute tidak boleh diisi.',
    'prohibited_if' => ':Attribute tidak boleh diisi jika :other :value.',
    'prohibited_if_accepted' => ':Attribute tidak boleh diisi jika :other disetujui.',
    'prohibited_if_declined' => ':Attribute tidak boleh diisi jika :other ditolak.',
    'prohibited_unless' => ':Attribute tidak boleh diisi kecuali :other termasuk dalam :values.',
    'prohibits' => ':Attribute tidak boleh diisi bersamaan dengan :other.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_array_keys' => ':Attribute harus memuat entri untuk: :values.',
    'required_if' => ':Attribute wajib diisi jika :other :value.',
    'required_if_accepted' => ':Attribute wajib diisi jika :other disetujui.',
    'required_if_declined' => ':Attribute wajib diisi jika :other ditolak.',
    'required_unless' => ':Attribute wajib diisi kecuali :other termasuk dalam :values.',
    'required_with' => ':Attribute wajib diisi jika :values ada.',
    'required_with_all' => ':Attribute wajib diisi jika :values ada.',
    'required_without' => ':Attribute wajib diisi jika :values tidak ada.',
    'required_without_all' => ':Attribute wajib diisi jika tidak ada satu pun dari :values.',
    'same' => ':Attribute harus sama dengan :other.',
    'size' => [
        'array' => ':Attribute harus memuat :size item.',
        'file' => ':Attribute harus :size kilobyte.',
        'numeric' => ':Attribute harus :size.',
        'string' => ':Attribute harus :size karakter.',
    ],
    'starts_with' => ':Attribute harus diawali dengan: :values.',
    'string' => ':Attribute harus berupa string.',
    'timezone' => ':Attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah dipakai.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => ':Attribute harus menggunakan huruf besar.',
    'url' => ':Attribute harus berupa URL yang valid.',
    'ulid' => ':Attribute harus berupa ULID yang valid.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi Kustom
    |--------------------------------------------------------------------------
    |
    | Tentukan pesan validasi khusus untuk atribut dengan
    | konvensi "attribute.rule" sebagai nama baris. Jadi mudah
    | menentukan baris bahasa khusus untuk aturan atribut tertentu.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atribut Validasi Kustom
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut dipakai untuk mengganti placeholder atribut
    | dengan nama yang lebih mudah dibaca, misalnya "Alamat Email"
    | sebagai ganti "email". Agar pesannya lebih jelas.
    |
    */

    'attributes' => [],

];
