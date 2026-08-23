<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesHistorySeeder extends Seeder
{
    /**
     * Generate 3-month dynamic sales history backwards from current execution date.
     */
    public function run(): void
    {
        if (app()->environment('testing') || app()->runningUnitTests() || config('database.default') === 'sqlite') {
            return;
        }

        $workspace = Workspace::where('slug', 'amore-coffee')->first();
        if (! $workspace) {
            $this->command?->warn('Workspace "amore-coffee" tidak ditemukan. Jalankan TenantPilotSeeder terlebih dahulu.');
            return;
        }

        $branches = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->get();
        if ($branches->isEmpty()) {
            $this->command?->warn('Tidak ada cabang outlet ditemukan. Jalankan BranchSeeder terlebih dahulu.');
            return;
        }

        $products = Product::withoutGlobalScopes()->where('workspace_id', $workspace->id)->where('is_active', true)->get();
        if ($products->isEmpty()) {
            $this->command?->warn('Tidak ada produk katalog ditemukan. Jalankan ProductCatalogSeeder terlebih dahulu.');
            return;
        }

        // Tanggal patokan: 3 bulan ke belakang dari saat seeder dijalankan
        $now = Carbon::now();
        $startDate = (clone $now)->subMonths(3)->startOfDay();
        $endDate = clone $now;

        $this->command?->info("Membuat histori penjualan 3 bulan: {$startDate->toDateString()} s/d {$endDate->toDateString()}...");

        $customerNames = [
            'Rian', 'Anisa', 'Fajar', 'Mega', 'Bayu', 'Dina', 'Reza', 'Putri',
            'Adit', 'Nadia', 'Bagus', 'Tasya', 'Gilang', 'Salsa', 'Kevin',
            'Dewi', 'Arya', 'Maya', 'Rizky', 'Bella', 'Hendra', 'Wulan', null, null,
        ];

        $paymentMethods = ['QRIS', 'QRIS', 'QRIS', 'CASH', 'CASH', 'TRANSFER'];

        // Ambil mapping kasir & terminal per cabang
        $branchResources = [];
        foreach ($branches as $branch) {
            $terminal = PosTerminal::withoutGlobalScopes()->where('branch_id', $branch->id)->first();
            $cashierMemberships = WorkspaceMember::withoutGlobalScopes()
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->pluck('user_id');

            $cashiers = User::whereIn('id', $cashierMemberships)->get();
            if ($cashiers->isEmpty()) {
                $cashiers = User::where('email', 'like', '%amorecoffee.id')->get();
            }

            $branchResources[$branch->id] = [
                'branch' => $branch,
                'terminal' => $terminal,
                'cashiers' => $cashiers,
                'slug_prefix' => str_contains(strtolower($branch->name), 'sleman') ? 'SLM' : 'MLB',
            ];
        }

        $currentDate = clone $startDate;
        $totalOrdersCreated = 0;
        $totalSessionsCreated = 0;

        while ($currentDate->lte($endDate)) {
            $isToday = $currentDate->isSameDay($now);
            $isWeekend = $currentDate->isWeekend();

            foreach ($branches as $branch) {
                $resource = $branchResources[$branch->id];
                $terminal = $resource['terminal'];
                $cashiers = $resource['cashiers'];
                $slugPrefix = $resource['slug_prefix'];

                // Buat 2 shift per hari: Shift Pagi (07:00-15:00) & Shift Sore (15:00-23:00)
                $shifts = [
                    [
                        'name' => 'Pagi',
                        'open_hour' => 7,
                        'open_min' => rand(0, 20),
                        'close_hour' => 15,
                        'close_min' => rand(0, 15),
                        'opening_cash' => 300000.00,
                    ],
                    [
                        'name' => 'Sore',
                        'open_hour' => 15,
                        'open_min' => rand(0, 15),
                        'close_hour' => 22,
                        'close_min' => rand(45, 59),
                        'opening_cash' => 500000.00,
                    ],
                ];

                foreach ($shifts as $shiftIndex => $shiftConfig) {
                    $openedAt = (clone $currentDate)->setHour($shiftConfig['open_hour'])->setMinute($shiftConfig['open_min'])->setSecond(0);
                    $closedAt = (clone $currentDate)->setHour($shiftConfig['close_hour'])->setMinute($shiftConfig['close_min'])->setSecond(0);

                    // Jika shift belum tiba waktunya (misal shift sore hari ini saat pagi), skip
                    if ($openedAt->gt($now)) {
                        continue;
                    }

                    $isOngoingSession = $isToday && $closedAt->gt($now);
                    $sessionStatus = $isOngoingSession ? 'OPEN' : 'CLOSED';
                    $cashierUser = $cashiers[$shiftIndex % max(1, $cashiers->count())] ?? $cashiers->first();

                    $posSession = PosSession::create([
                        'id' => (string) Str::uuid(),
                        'workspace_id' => $workspace->id,
                        'branch_id' => $branch->id,
                        'opened_by_user_id' => $cashierUser?->id ?: $workspace->owner_user_id,
                        'closed_by_user_id' => $isOngoingSession ? null : ($cashierUser?->id ?: $workspace->owner_user_id),
                        'opening_cash' => $shiftConfig['opening_cash'],
                        'status' => $sessionStatus,
                        'notes' => "Sesi Shift {$shiftConfig['name']} {$currentDate->toDateString()}",
                        'opened_at' => $openedAt,
                        'closed_at' => $isOngoingSession ? null : $closedAt,
                        'created_at' => $openedAt,
                        'updated_at' => $isOngoingSession ? $openedAt : $closedAt,
                    ]);

                    $totalSessionsCreated++;

                    // Jumlah order per shift (lebih ramai di weekend)
                    $orderCount = $isWeekend ? rand(18, 36) : rand(10, 22);
                    if ($isOngoingSession) {
                        $orderCount = rand(4, 10);
                    }

                    $sessionCashSales = 0.00;
                    $sessionQrisSales = 0.00;
                    $sessionTransferSales = 0.00;

                    $ordersBatch = [];
                    $orderItemsBatch = [];

                    for ($i = 1; $i <= $orderCount; $i++) {
                        $maxMinutes = max(6, (int) ($closedAt->diffInMinutes($openedAt) - 10));
                        $minuteOffset = rand(5, $maxMinutes);
                        $orderTime = (clone $openedAt)->addMinutes($minuteOffset);
                        if ($orderTime->gt($now)) {
                            $orderTime = (clone $now)->subMinutes(rand(1, 10));
                        }

                        $orderId = (string) Str::uuid();
                        $clientOrderId = (string) Str::uuid();
                        $orderNumber = sprintf('ORD-%s-%s-%04d', $slugPrefix, $currentDate->format('Ymd'), ($shiftIndex * 50) + $i);
                        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                        $customerName = $customerNames[array_rand($customerNames)];

                        // Pilih 1 hingga 3 produk secara acak
                        $selectedProducts = $products->random(rand(1, min(3, $products->count())));
                        $orderTotal = 0.00;

                        foreach ($selectedProducts as $prod) {
                            $qty = rand(1, 2);
                            $unitPrice = (float) $prod->base_price;
                            $subtotal = $unitPrice * $qty;
                            $orderTotal += $subtotal;

                            $orderItemsBatch[] = [
                                'id' => (string) Str::uuid(),
                                'order_id' => $orderId,
                                'product_id' => $prod->id,
                                'product_name' => $prod->name,
                                'unit_price' => $unitPrice,
                                'quantity' => $qty,
                                'subtotal' => $subtotal,
                                'notes' => (rand(1, 5) === 1) ? 'Less ice, normal sugar' : null,
                                'created_at' => $orderTime,
                                'updated_at' => $orderTime,
                            ];
                        }

                        // Diskon acak (5% atau 10% pada beberapa transaksi)
                        $discountAmount = 0.00;
                        if (rand(1, 6) === 1) {
                            $discountAmount = round($orderTotal * 0.10);
                        }

                        $finalAmount = max(0.00, $orderTotal - $discountAmount);

                        if ($paymentMethod === 'CASH') {
                            $sessionCashSales += $finalAmount;
                        } elseif ($paymentMethod === 'QRIS') {
                            $sessionQrisSales += $finalAmount;
                        } else {
                            $sessionTransferSales += $finalAmount;
                        }

                        $ordersBatch[] = [
                            'id' => $orderId,
                            'workspace_id' => $workspace->id,
                            'branch_id' => $branch->id,
                            'pos_session_id' => $posSession->id,
                            'pos_terminal_id' => $terminal?->id,
                            'cashier_user_id' => $cashierUser?->id,
                            'client_order_id' => $clientOrderId,
                            'order_number' => $orderNumber,
                            'total_amount' => $orderTotal,
                            'discount_amount' => $discountAmount,
                            'final_amount' => $finalAmount,
                            'payment_method' => $paymentMethod,
                            'payment_status' => 'PAID',
                            'created_at' => $orderTime,
                            'updated_at' => $orderTime,
                        ];

                        $totalOrdersCreated++;
                    }

                    if (! empty($ordersBatch)) {
                        Order::insert($ordersBatch);
                    }
                    if (! empty($orderItemsBatch)) {
                        OrderItem::insert($orderItemsBatch);
                    }

                    // Rekonsiliasi kas untuk sesi yang ditutup
                    if ($sessionStatus === 'CLOSED') {
                        $expectedCash = $shiftConfig['opening_cash'] + $sessionCashSales;
                        // Variasi selisih kas fisik laci (95% seimbang, 5% selisih kecil Rp 1.000 - Rp 2.000)
                        $discrepancy = 0.00;
                        $randomVariance = rand(1, 20);
                        if ($randomVariance === 1) {
                            $discrepancy = (float) (rand(1, 2) * 1000); // lebih
                        } elseif ($randomVariance === 2) {
                            $discrepancy = (float) -(rand(1, 2) * 1000); // kurang
                        }

                        $actualCash = $expectedCash + $discrepancy;

                        $posSession->update([
                            'closing_cash_expected' => $expectedCash,
                            'closing_cash_actual' => $actualCash,
                            'discrepancy_amount' => $discrepancy,
                        ]);
                    }
                }
            }

            $currentDate->addDay();
        }

        $this->command?->info("Sukses men-seed {$totalOrdersCreated} pesanan penjualan dan {$totalSessionsCreated} sesi kasir untuk 3 bulan terakhir.");
    }
}
