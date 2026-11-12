<?php

// Data usaha yang tampil di halaman invoice dan PDF.
// Bisa diubah langsung di sini atau lewat file .env.
return [
    'company' => [
        'name' => env('INVOICELY_COMPANY', 'Nama Usaha Anda'),
        'address' => env('INVOICELY_ADDRESS', 'Alamat usaha Anda'),
        'email' => env('INVOICELY_EMAIL', 'email@usaha.com'),
        'phone' => env('INVOICELY_PHONE', ''),
    ],
];
