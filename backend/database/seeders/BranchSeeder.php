<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\OutletPurchase;
use App\Models\PosTerminal;
use App\Models\StockWaste;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $pilotCreationDate = (clone $now)->subYears(5)->startOfMonth();

        $workspaceSeturan = Workspace::where('slug', 'norde-coffee')->firstOrFail();
        $workspaceKaliurang = Workspace::where('slug', 'norde-coffee-kaliurang')->first() ?? $workspaceSeturan;
        $owner = User::where('email', 'kiki@gmail.com')->first();

        // 1. Cabang 1: Norde Coffee - Seturan (pada Workspace 1: Seturan)
        $branchSeturan = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'name' => 'Norde Coffee - Seturan',
            ],
            [
                'lat' => -7.76543000,
                'lng' => 110.40912000,
                'radius_meters' => 50,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        BranchSetting::withoutGlobalScopes()->updateOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
                'tax_enabled' => true,
                'tax_name' => 'PB1',
                'tax_rate' => 10.00,
                'tax_type' => 'INCLUSIVE',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $terminalSeturan = PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'branch_id' => $branchSeturan->id,
                'terminal_name' => 'Norde POS Tab Seturan',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-seturan-01'),
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        // 2. Cabang 2: Norde Coffee - Kaliurang (pada Workspace 2: Kaliurang)
        $targetWsKaliurang = ($workspaceKaliurang->id !== $workspaceSeturan->id) ? $workspaceKaliurang : $workspaceSeturan;

        $branchKaliurang = Branch::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $targetWsKaliurang->id,
                'name' => 'Norde Coffee - Kaliurang',
            ],
            [
                'lat' => -7.72145000,
                'lng' => 110.39567000,
                'radius_meters' => 60,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        BranchSetting::withoutGlobalScopes()->updateOrCreate(
            [
                'workspace_id' => $targetWsKaliurang->id,
                'branch_id' => $branchKaliurang->id,
            ],
            [
                'late_penalty_per_minute' => 1000.00,
                'overtime_pay_per_hour' => 20000.00,
                'min_overtime_threshold_minutes' => 30,
                'tax_enabled' => true,
                'tax_name' => 'PB1',
                'tax_rate' => 10.00,
                'tax_type' => 'INCLUSIVE',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        $terminalKaliurang = PosTerminal::withoutGlobalScopes()->firstOrCreate(
            [
                'workspace_id' => $targetWsKaliurang->id,
                'branch_id' => $branchKaliurang->id,
                'terminal_name' => 'Norde POS Tab Kaliurang',
            ],
            [
                'device_token_hash' => hash('sha256', 'pos-device-token-kaliurang-01'),
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        if (app()->environment('testing') || app()->runningUnitTests()) {
            return;
        }

        // 3. sample riwayat belanja kasir outlet purchases petty cash
        $purchasesSample = [
            [
                'item_name' => 'Es Batu Kristal Higienis 5 Pack',
                'unit' => 'Pack',
                'quantity' => 5,
                'unit_price' => 10000.00,
                'total_price' => 50000.00,
                'category' => 'BAHAN_BAKU_DARURAT',
                'funding_source' => 'CASH_DRAWER',
                'notes' => 'Pembelian es batu darurat karena freezer mati sementara saat rush hour',
            ],
            [
                'item_name' => 'Sabun Cuci Piring & Spons Bar 3 pcs',
                'unit' => 'Pcs',
                'quantity' => 3,
                'unit_price' => 12000.00,
                'total_price' => 36000.00,
                'category' => 'KEBERSIHAN',
                'funding_source' => 'CASH_DRAWER',
                'notes' => 'Stok pembersih bar habis sebelum closing',
            ],
            [
                'item_name' => 'Paper Bag & Kantong Takeaway 100 pcs',
                'unit' => 'Pack',
                'quantity' => 1,
                'unit_price' => 75000.00,
                'total_price' => 75000.00,
                'category' => 'OPERASIONAL_TOKO',
                'funding_source' => 'EXTERNAL_REIMBURSE',
                'notes' => 'Beli di agen kemasan dekat outlet',
            ],
        ];

        foreach ([$branchSeturan, $branchKaliurang] as $branch) {
            foreach ($purchasesSample as $p) {
                OutletPurchase::withoutGlobalScopes()->firstOrCreate(
                    [
                        'workspace_id' => $branch->workspace_id,
                        'branch_id' => $branch->id,
                        'item_name' => $p['item_name'],
                    ],
                    [
                        'unit' => $p['unit'],
                        'quantity' => $p['quantity'],
                        'unit_price' => $p['unit_price'],
                        'total_price' => $p['total_price'],
                        'category' => $p['category'],
                        'funding_source' => $p['funding_source'],
                        'notes' => $p['notes'],
                        'recorded_by_user_id' => $owner?->id ?? (string) \Illuminate\Support\Str::uuid(),
                        'created_at' => (clone $now)->subDays(rand(1, 10)),
                        'updated_at' => (clone $now)->subDays(rand(1, 10)),
                    ]
                );
            }
        }

        // 4. sample riwayat barang rusak terbuang stock waste
        $wastesSample = [
            [
                'item_name' => 'Sirup Monin Vanilla 700ml',
                'quantity' => 1,
                'unit' => 'Botol',
                'cost_per_unit' => 135000.00,
                'total_loss_cost' => 135000.00,
                'reason' => 'ACCIDENT_SPILL',
                'notes' => 'Botol tersenggol staf saat rush hour malam minggu',
            ],
            [
                'item_name' => 'Fresh Milk Diamond 1L (2 Karton)',
                'quantity' => 2,
                'unit' => 'Karton',
                'cost_per_unit' => 19500.00,
                'total_loss_cost' => 39000.00,
                'reason' => 'EXPIRED',
                'notes' => 'Kedaluwarsa karena tidak terpakai saat libur operasional',
            ],
            [
                'item_name' => 'Butter French Croissant Dough',
                'quantity' => 3,
                'unit' => 'Pcs',
                'cost_per_unit' => 12000.00,
                'total_loss_cost' => 36000.00,
                'reason' => 'BARISTA_MISTAKE',
                'notes' => 'Overbaked saat pemanggangan pagi',
            ],
        ];

        foreach ([$branchSeturan, $branchKaliurang] as $branch) {
            foreach ($wastesSample as $w) {
                StockWaste::withoutGlobalScopes()->firstOrCreate(
                    [
                        'workspace_id' => $branch->workspace_id,
                        'branch_id' => $branch->id,
                        'item_name' => $w['item_name'],
                    ],
                    [
                        'quantity' => $w['quantity'],
                        'unit' => $w['unit'],
                        'cost_per_unit' => $w['cost_per_unit'],
                        'total_loss_cost' => $w['total_loss_cost'],
                        'reason' => $w['reason'],
                        'notes' => $w['notes'],
                        'recorded_by_user_id' => $owner?->id ?? (string) \Illuminate\Support\Str::uuid(),
                        'created_at' => (clone $now)->subDays(rand(1, 10)),
                        'updated_at' => (clone $now)->subDays(rand(1, 10)),
                    ]
                );
            }
        }
        $rawMaterialsSample = [
            ['name' => 'Fresh Milk Diamond 1L', 'current_stock' => 18, 'min_stock_alert' => 10, 'unit' => 'liter'],
            ['name' => 'Oatside Barista Oat Milk 1L', 'current_stock' => 12, 'min_stock_alert' => 5, 'unit' => 'liter'],
            ['name' => 'Biji Kopi Arabica House Blend 1kg', 'current_stock' => 15, 'min_stock_alert' => 5, 'unit' => 'kg'],
            ['name' => 'Sirup Monin Vanilla 700ml', 'current_stock' => 4, 'min_stock_alert' => 2, 'unit' => 'botol'],
            ['name' => 'Paper Cup 8oz Cold / Hot', 'current_stock' => 250, 'min_stock_alert' => 50, 'unit' => 'pcs'],
        ];

        foreach ($rawMaterialsSample as $mat) {
            \App\Models\RawMaterial::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $branchSeturan->workspace_id,
                    'name' => $mat['name'],
                ],
                [
                    'current_stock' => $mat['current_stock'],
                    'min_stock_alert' => $mat['min_stock_alert'],
                    'unit' => $mat['unit'],
                    'last_adjusted_at' => $now,
                ]
            );
        }
    }
}
