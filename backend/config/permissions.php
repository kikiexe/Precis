<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Katalog Hak Akses (Permissions Catalog)
    |--------------------------------------------------------------------------
    |
    | Daftar seluruh permission yang tersedia dalam sistem Precis SaaS.
    | Dikelompokkan per modul bisnis dengan label, deskripsi, dan tingkat risiko.
    |
    */

    'modules' => [
        'katalog' => [
            'id' => 'katalog',
            'name' => 'Katalog & Menu',
            'description' => 'Akses pengaturan daftar menu, kategori, dan harga produk',
            'permissions' => [
                'catalog.view' => [
                    'name' => 'Lihat Menu & Kategori',
                    'description' => 'Melihat daftar produk, harga, varian, dan kategori di portal',
                    'is_high_risk' => false,
                ],
                'catalog.manage' => [
                    'name' => 'Kelola Menu & Kategori',
                    'description' => 'Menambah, mengubah foto/harga, dan menghapus menu atau kategori',
                    'is_high_risk' => false,
                ],
            ],
        ],

        'inventaris' => [
            'id' => 'inventaris',
            'name' => 'Inventaris & Bahan',
            'description' => 'Akses pemantauan stok bahan baku dan penyesuaian opname',
            'permissions' => [
                'inventory.view' => [
                    'name' => 'Lihat Stok Inventaris',
                    'description' => 'Melihat status ketersediaan dan sisa stok bahan baku outlet',
                    'is_high_risk' => false,
                ],
                'inventory.adjust' => [
                    'name' => 'Penyesuaian Stok (Stock In/Out)',
                    'description' => 'Menginput penambahan barang masuk, terbuang (waste), atau opname',
                    'is_high_risk' => false,
                ],
            ],
        ],

        'operasional' => [
            'id' => 'operasional',
            'name' => 'Jadwal & Presensi',
            'description' => 'Akses penjadwalan kerja tim dan pemantauan absensi',
            'permissions' => [
                'attendance.view_all' => [
                    'name' => 'Lihat Presensi Live (Wall of Faces)',
                    'description' => 'Memantau foto selfie absensi dan jam kerja seluruh karyawan',
                    'is_high_risk' => false,
                ],
                'attendance.exempt_penalty' => [
                    'name' => 'Bebas Denda Keterlambatan',
                    'description' => 'Mengecualikan potongan denda keterlambatan saat kalkulasi penggajian',
                    'is_high_risk' => false,
                ],
                'shifts.manage' => [
                    'name' => 'Kelola Jadwal & Template Shift',
                    'description' => 'Menyusun roster jadwal kerja dan membuat template jam kerja',
                    'is_high_risk' => false,
                ],
                'shifts.approve_swap' => [
                    'name' => 'Persetujuan Tukar Shift',
                    'description' => 'Menyetujui atau menolak permohonan pergantian shift antar staf',
                    'is_high_risk' => false,
                ],
            ],
        ],

        'keuangan' => [
            'id' => 'keuangan',
            'name' => 'Keuangan & Payroll',
            'description' => 'Akses laporan penjualan omzet, persetujuan kasbon, dan penggajian',
            'permissions' => [
                'sales.view_analytics' => [
                    'name' => 'Lihat Laporan Omzet & Analitik',
                    'description' => 'Melihat tren penjualan, pendapatan bersih, dan rata-rata order',
                    'is_high_risk' => false,
                ],
                'reports.export' => [
                    'name' => 'Ekspor Laporan Penjualan (CSV/Excel)',
                    'description' => 'Mengunduh berkas rekap omzet dan data transaksi penjualan',
                    'is_high_risk' => false,
                ],
                'cash_advance.approve' => [
                    'name' => 'Persetujuan Kasbon Karyawan',
                    'description' => 'Menyetujui atau menolak pencairan uang muka kasbon staf',
                    'is_high_risk' => false,
                ],
                'payroll.view' => [
                    'name' => 'Lihat Rekap Gaji Tim',
                    'description' => 'Melihat kalkulasi nominal gaji seluruh anggota tim',
                    'is_high_risk' => true,
                ],
                'payroll.disburse' => [
                    'name' => 'Cairkan Payroll & Ekspor Bank',
                    'description' => 'Mengeksekusi pencairan gaji dan download CSV transfer bank',
                    'is_high_risk' => true,
                ],
            ],
        ],

        'tim' => [
            'id' => 'tim',
            'name' => 'Manajemen Tim & Akses',
            'description' => 'Akses daftar staf, undangan tim, dan konfigurasi hak akses',
            'permissions' => [
                'members.view' => [
                    'name' => 'Lihat Profil Staf',
                    'description' => 'Melihat daftar karyawan, nomor telepon, dan penempatan cabang',
                    'is_high_risk' => false,
                ],
                'members.manage' => [
                    'name' => 'Kelola Data & Undangan Staf',
                    'description' => 'Mengundang staf baru, mengubah gaji pokok, dan menonaktifkan akun',
                    'is_high_risk' => true,
                ],
                'roles.manage' => [
                    'name' => 'Kelola Peran & Izin Kustom',
                    'description' => 'Membuat peran baru dan menentukan checklist hak akses',
                    'is_high_risk' => true,
                ],
            ],
        ],

        'pos' => [
            'id' => 'pos',
            'name' => 'Terminal Kasir (POS)',
            'description' => 'Akses otorisasi khusus pada perangkat aplikasi kasir',
            'permissions' => [
                'pos.manage_terminals' => [
                    'name' => 'Kelola Terminal Kasir',
                    'description' => 'Menambah terminal kasir dan membuat pairing token perangkat',
                    'is_high_risk' => false,
                ],
                'pos.open_cash_drawer' => [
                    'name' => 'Buka Laci Kasir Manual',
                    'description' => 'Membuka cash drawer laci kasir tanpa transaksi penjualan',
                    'is_high_risk' => true,
                ],
                'pos.reprint_receipt' => [
                    'name' => 'Cetak Ulang Struk Transaksi',
                    'description' => 'Mencetak ulang struk pembayaran pesanan yang telah selesai',
                    'is_high_risk' => false,
                ],
                'pos.void_order' => [
                    'name' => 'Otorisasi Void / Batal Transaksi',
                    'description' => 'Membatalkan pesanan yang sudah tercatat di kasir dengan master PIN',
                    'is_high_risk' => true,
                ],
                'pos.refund_order' => [
                    'name' => 'Otorisasi Refund Dana Transaksi',
                    'description' => 'Mengembalikan dana pembayaran pesanan ke pelanggan',
                    'is_high_risk' => true,
                ],
                'pos.apply_discount' => [
                    'name' => 'Otorisasi Diskon Khusus',
                    'description' => 'Memberikan potongan harga manual khusus di aplikasi kasir',
                    'is_high_risk' => false,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preset Template Default
    |--------------------------------------------------------------------------
    */
    'presets' => [
        'manager' => [
            'name' => 'Manajer Operasional',
            'description' => 'Akses penuh ke operasional toko, katalog menu, stok, jadwal shift, dan kasbon',
            'permissions' => [
                'catalog.view',
                'catalog.manage',
                'inventory.view',
                'inventory.adjust',
                'attendance.view_all',
                'shifts.manage',
                'shifts.approve_swap',
                'sales.view_analytics',
                'cash_advance.approve',
                'members.view',
                'pos.manage_terminals',
                'pos.void_order',
                'pos.refund_order',
                'pos.apply_discount',
            ],
        ],
        'supervisor' => [
            'name' => 'Supervisor Shift / Bar Lead',
            'description' => 'Fokus pada kelola menu, kontrol stok opname, dan approval tukar shift',
            'permissions' => [
                'catalog.view',
                'catalog.manage',
                'inventory.view',
                'inventory.adjust',
                'attendance.view_all',
                'shifts.approve_swap',
                'members.view',
                'pos.apply_discount',
            ],
        ],
        'cashier' => [
            'name' => 'Kasir & Frontliner',
            'description' => 'Akses melihat menu, presensi harian, dan operasional kasir POS',
            'permissions' => [
                'catalog.view',
                'inventory.view',
                'members.view',
            ],
        ],
        'finance' => [
            'name' => 'Akuntan & Keuangan',
            'description' => 'Akses analitik penjualan, rekap gaji payroll, dan ekspor CSV perbankan',
            'permissions' => [
                'sales.view_analytics',
                'payroll.view',
                'payroll.disburse',
                'members.view',
            ],
        ],
        'staff' => [
            'name' => 'Karyawan / Barista',
            'description' => 'Akses dasar melihat katalog dan informasi profil tim',
            'permissions' => [
                'catalog.view',
                'members.view',
            ],
        ],
    ],
];
