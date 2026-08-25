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
     * Generate 3-month dynamic sales history for Norde Coffee (Seturan & Kaliurang).
     * Monthly revenue capped under 20jt/month total with realistic daily variance.
     */
    public function run(): void
    {
        if (app()->environment('testing') || app()->runningUnitTests()) {
            return;
        }

        $branches = Branch::withoutGlobalScopes()->get();
        if ($branches->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $startDate = (clone $now)->subMonths(3)->startOfDay();
        $endDate = clone $now;

        $paymentMethods = ['QRIS', 'QRIS', 'QRIS', 'CASH', 'CASH', 'TRANSFER'];

        // Ambil mapping kasir, terminal, dan produk per cabang
        $branchResources = [];
        foreach ($branches as $branch) {
            $terminal = PosTerminal::withoutGlobalScopes()->where('branch_id', $branch->id)->first();
            $cashierMemberships = WorkspaceMember::withoutGlobalScopes()
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->pluck('user_id');

            $cashiers = User::whereIn('id', $cashierMemberships)->get();
            if ($cashiers->isEmpty()) {
                $cashiers = User::where('email', 'like', '%@gmail.com')->get();
            }

            $products = Product::withoutGlobalScopes()->where('workspace_id', $branch->workspace_id)->where('is_active', true)->get();
            if ($products->isEmpty()) {
                $products = Product::withoutGlobalScopes()->where('is_active', true)->get();
            }

            $isSeturan = str_contains(strtolower($branch->name), 'seturan');

            $branchResources[$branch->id] = [
                'branch' => $branch,
                'terminal' => $terminal,
                'cashiers' => $cashiers,
                'products' => $products,
                'slug_prefix' => $isSeturan ? 'STR' : 'KLR',
                'is_seturan' => $isSeturan,
            ];
        }

        $currentDate = clone $startDate;

        while ($currentDate->lte($endDate)) {
            $isToday = $currentDate->isSameDay($now);
            $isWeekend = $currentDate->isWeekend();

            // Random daily factor (randomize some slow days, some peak days)
            // 15% chance of slow day, 65% normal day, 20% peak day
            $dayProfileRoll = rand(1, 100);
            $dayMultiplier = 1.0;
            if ($dayProfileRoll <= 15) {
                $dayMultiplier = 0.55; // slow rainy day
            } elseif ($dayProfileRoll > 80 || $isWeekend) {
                $dayMultiplier = 1.35; // busy day
            }

            foreach ($branches as $branch) {
                $resource = $branchResources[$branch->id];
                $terminal = $resource['terminal'];
                $cashiers = $resource['cashiers'];
                $slugPrefix = $resource['slug_prefix'];
                $isSeturan = $resource['is_seturan'];

                // 2 Shift per hari
                $shifts = [
                    [
                        'name' => 'Pagi',
                        'open_hour' => $isSeturan ? 7 : 8,
                        'open_min' => rand(0, 15),
                        'close_hour' => $isSeturan ? 15 : 16,
                        'close_min' => rand(0, 10),
                        'opening_cash' => 250000.00,
                    ],
                    [
                        'name' => 'Sore',
                        'open_hour' => $isSeturan ? 15 : 16,
                        'open_min' => rand(0, 15),
                        'close_hour' => $isSeturan ? 23 : 23,
                        'close_min' => rand(30, 59),
                        'opening_cash' => 350000.00,
                    ],
                ];

                foreach ($shifts as $shiftIndex => $shiftConfig) {
                    $openedAt = (clone $currentDate)->setHour($shiftConfig['open_hour'])->setMinute($shiftConfig['open_min'])->setSecond(0);
                    $closedAt = (clone $currentDate)->setHour($shiftConfig['close_hour'])->setMinute($shiftConfig['close_min'])->setSecond(0);

                    if ($openedAt->gt($now)) {
                        continue;
                    }

                    $isOngoingSession = $isToday && $closedAt->gt($now);
                    $sessionStatus = $isOngoingSession ? 'OPEN' : 'CLOSED';
                    $cashierUser = $cashiers[$shiftIndex % max(1, $cashiers->count())] ?? $cashiers->first();
                    $branchWorkspaceId = $branch->workspace_id;

                    $posSession = PosSession::create([
                        'id' => (string) Str::uuid(),
                        'workspace_id' => $branchWorkspaceId,
                        'branch_id' => $branch->id,
                        'opened_by_user_id' => $cashierUser?->id,
                        'closed_by_user_id' => $isOngoingSession ? null : $cashierUser?->id,
                        'opening_cash' => $shiftConfig['opening_cash'],
                        'status' => $sessionStatus,
                        'notes' => "Sesi Shift {$shiftConfig['name']} {$currentDate->toDateString()}",
                        'opened_at' => $openedAt,
                        'closed_at' => $isOngoingSession ? null : $closedAt,
                        'created_at' => $openedAt,
                        'updated_at' => $isOngoingSession ? $openedAt : $closedAt,
                    ]);

                    // Target 2 s/d 4 pesanan per shift agar omzet cabang ~100k-250k/shift (Total 2 cabang = ~400k-600k/hari = ~15-19jt/bulan)
                    $baseOrders = $isSeturan ? rand(2, 4) : rand(2, 3);
                    $orderCount = max(1, (int) round($baseOrders * $dayMultiplier));
                    if ($isOngoingSession) {
                        $orderCount = rand(1, 2);
                    }

                    $sessionCashSales = 0.00;
                    $ordersBatch = [];
                    $orderItemsBatch = [];
                    $products = $resource['products'];

                    for ($i = 1; $i <= $orderCount; $i++) {
                        $maxMinutes = max(6, (int) ($closedAt->diffInMinutes($openedAt) - 10));
                        $minuteOffset = rand(5, $maxMinutes);
                        $orderTime = (clone $openedAt)->addMinutes($minuteOffset);
                        if ($orderTime->gt($now)) {
                            $orderTime = (clone $now)->subMinutes(rand(1, 5));
                        }

                        $orderId = (string) Str::uuid();
                        $clientOrderId = (string) Str::uuid();
                        $orderNumber = sprintf('ORD-%s-%s-%04d', $slugPrefix, $currentDate->format('Ymd'), ($shiftIndex * 50) + $i);
                        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                        // 75% 1 produk, 25% 2 produk
                        $itemCount = (rand(1, 100) <= 75) ? 1 : 2;
                        $selectedProducts = $products->random(min($itemCount, $products->count()));
                        $orderTotal = 0.00;

                        foreach ($selectedProducts as $prod) {
                            $qty = (rand(1, 100) <= 85) ? 1 : 2;
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
                                'notes' => (rand(1, 5) === 1) ? 'Less sugar, oatmilk' : null,
                                'created_at' => $orderTime,
                                'updated_at' => $orderTime,
                            ];
                        }

                        // Diskon 10% pada beberapa transaksi
                        $discountAmount = 0.00;
                        if (rand(1, 8) === 1) {
                            $discountAmount = round($orderTotal * 0.10);
                        }

                        $finalAmount = max(0.00, $orderTotal - $discountAmount);

                        if ($paymentMethod === 'CASH') {
                            $sessionCashSales += $finalAmount;
                        }

                        $ordersBatch[] = [
                            'id' => $orderId,
                            'workspace_id' => $branchWorkspaceId,
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
                    }

                    if (! empty($ordersBatch)) {
                        DB::table('orders')->insert($ordersBatch);
                    }
                    if (! empty($orderItemsBatch)) {
                        DB::table('order_items')->insert($orderItemsBatch);
                    }

                    if ($sessionStatus === 'CLOSED') {
                        $expectedCash = $shiftConfig['opening_cash'] + $sessionCashSales;
                        $posSession->update([
                            'closing_cash_expected' => $expectedCash,
                            'closing_cash_actual' => $expectedCash,
                            'discrepancy_amount' => 0.00,
                        ]);
                    }
                }
            }

            $currentDate->addDay();
        }
    }
}
