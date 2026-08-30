<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesHistorySeeder extends Seeder
{
    /**
     * Generate 5-year realistic dynamic sales history for Norde Coffee (Seturan & Kaliurang).
     * Features:
     * - Yearly macro growth progression over 5 years.
     * - Seasonal peak months and trough months.
     * - Promotional festival weeks and bad weather slowdowns.
     * - Annual Store Anniversary mega peak days and rare blackout days.
     * - Streamed batch database insertion with single transaction for maximum speed and minimal memory footprint.
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
        $startDate = (clone $now)->subYears(5)->startOfMonth();
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
        $orderCounter = 1;

        $posSessionRows = [];
        $orderRows = [];
        $orderItemRows = [];

        // Hapus data transaksi lama jika ada untuk idempotency
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('pos_sessions')->delete();

        $flushBatch = function () use (&$posSessionRows, &$orderRows, &$orderItemRows): void {
            if (! empty($posSessionRows)) {
                DB::table('pos_sessions')->insert($posSessionRows);
                $posSessionRows = [];
            }
            if (! empty($orderRows)) {
                DB::table('orders')->insert($orderRows);
                $orderRows = [];
            }
            if (! empty($orderItemRows)) {
                DB::table('order_items')->insert($orderItemRows);
                $orderItemRows = [];
            }
        };

        DB::beginTransaction();

        try {
            while ($currentDate->lte($endDate)) {
                $dateStr = $currentDate->toDateString();
                $monthNum = (int) $currentDate->format('n');
                $yearDiff = (int) $now->diffInYears($currentDate); // 5 down to 0
                $isToday = $currentDate->isSameDay($now);
                $isWeekend = $currentDate->isWeekend();

                // 1. Macro Yearly Growth Multiplier
                $yearlyMultiplier = match (min(5, max(0, $yearDiff))) {
                    5 => 0.65,
                    4 => 0.78,
                    3 => 0.90,
                    2 => 1.05,
                    1, 0 => 1.20,
                };

                // 2. Monthly Seasonality
                $monthlyMultiplier = match ($monthNum) {
                    12 => 1.70, // Liburan Akhir Tahun / Natal
                    7 => 1.45,  // Liburan Sekolah / Musim Panas
                    2 => 0.60,  // Pasca Liburan / Musim Hujan
                    9 => 0.75,  // Low Season
                    default => 0.90 + (rand(0, 20) / 100.0),
                };

                // 3. Daily Events & Shocks
                $isAnniversaryDay = ($currentDate->format('m-d') === '10-15');
                $isBlackoutDay = ($currentDate->format('m-d') === '02-20');

                $dailyMultiplier = 1.0;
                if ($isAnniversaryDay) {
                    $dailyMultiplier = 3.20;
                } elseif ($isBlackoutDay) {
                    $dailyMultiplier = 0.20;
                } else {
                    $randomNoise = rand(85, 115) / 100.0;
                    $dailyMultiplier = ($isWeekend ? 1.30 : 0.95) * $randomNoise;
                }

                $combinedMultiplier = $yearlyMultiplier * $monthlyMultiplier * $dailyMultiplier;

                foreach ($branches as $branch) {
                    $resource = $branchResources[$branch->id];
                    $terminal = $resource['terminal'];
                    $cashiers = $resource['cashiers'];
                    $products = $resource['products'];
                    $slugPrefix = $resource['slug_prefix'];
                    $isSeturan = $resource['is_seturan'];

                    if ($products->isEmpty()) {
                        continue;
                    }

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
                            'close_hour' => 23,
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

                        $sessionId = (string) Str::uuid();

                        $posSessionRows[] = [
                            'id' => $sessionId,
                            'workspace_id' => $branchWorkspaceId,
                            'branch_id' => $branch->id,
                            'opened_by_user_id' => $cashierUser?->id,
                            'closed_by_user_id' => $isOngoingSession ? null : $cashierUser?->id,
                            'opening_cash' => $shiftConfig['opening_cash'],
                            'status' => $sessionStatus,
                            'notes' => "Sesi Shift {$shiftConfig['name']} {$currentDate->toDateString()}",
                            'opened_at' => $openedAt->toDateTimeString(),
                            'closed_at' => $isOngoingSession ? null : $closedAt->toDateTimeString(),
                            'created_at' => $openedAt->toDateTimeString(),
                            'updated_at' => ($isOngoingSession ? $openedAt : $closedAt)->toDateTimeString(),
                        ];

                        $baseOrders = $isSeturan ? 3 : 2;
                        $orderCount = max(1, (int) round($baseOrders * $combinedMultiplier));

                        if ($isOngoingSession) {
                            $orderCount = min(2, $orderCount);
                        }

                        for ($o = 0; $o < $orderCount; $o++) {
                            $orderId = (string) Str::uuid();
                            $minuteOffset = rand(15, 420);
                            $orderTime = (clone $openedAt)->addMinutes($minuteOffset);
                            if ($orderTime->gt($now)) {
                                $orderTime = (clone $now)->subMinutes(rand(1, 10));
                            }

                            $clientOrderId = (string) Str::uuid();
                            $orderNumber = sprintf('ORD/%s/%s/%05d', $slugPrefix, $currentDate->format('ymd'), $orderCounter++);
                            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                            $itemCount = rand(1, 3);
                            $selectedProducts = $products->random(min($itemCount, $products->count()));

                            $subtotal = 0.00;

                            foreach ($selectedProducts as $prod) {
                                $qty = rand(1, 2);
                                $unitPrice = (float) ($prod->base_price ?? $prod->price ?? 25000.00);
                                $itemSubtotal = $unitPrice * $qty;
                                $subtotal += $itemSubtotal;

                                $orderItemRows[] = [
                                    'id' => (string) Str::uuid(),
                                    'order_id' => $orderId,
                                    'product_id' => $prod->id,
                                    'product_name' => $prod->name,
                                    'unit_price' => $unitPrice,
                                    'quantity' => $qty,
                                    'subtotal' => $itemSubtotal,
                                    'notes' => null,
                                    'created_at' => $orderTime->toDateTimeString(),
                                    'updated_at' => $orderTime->toDateTimeString(),
                                ];
                            }

                            $discountAmount = 0.00;
                            $totalAmount = $subtotal;
                            $finalAmount = $totalAmount - $discountAmount;
                            $taxRate = 10.00;
                            $taxAmount = round($finalAmount - ($finalAmount / (1 + ($taxRate / 100))), 2);

                            $orderRows[] = [
                                'id' => $orderId,
                                'workspace_id' => $branchWorkspaceId,
                                'branch_id' => $branch->id,
                                'pos_session_id' => $sessionId,
                                'pos_terminal_id' => $terminal?->id,
                                'cashier_user_id' => $cashierUser?->id,
                                'client_order_id' => $clientOrderId,
                                'order_number' => $orderNumber,
                                'total_amount' => $totalAmount,
                                'discount_amount' => $discountAmount,
                                'final_amount' => $finalAmount,
                                'tax_name' => 'PB1',
                                'tax_rate' => $taxRate,
                                'tax_type' => 'INCLUSIVE',
                                'tax_amount' => $taxAmount,
                                'payment_method' => $paymentMethod,
                                'payment_status' => 'PAID',
                                'created_at' => $orderTime->toDateTimeString(),
                                'updated_at' => $orderTime->toDateTimeString(),
                            ];
                        }
                    }
                }

                // Flush ke DB setiap 500 item untuk menjaga konsumsi RAM < 10MB
                if (count($orderItemRows) >= 500) {
                    $flushBatch();
                }

                $currentDate->addDay();
            }

            // Flush sisa baris terakhir
            $flushBatch();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
