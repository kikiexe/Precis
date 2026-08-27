<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function getSalesAnalytics(string $workspaceId, string $period = 'day', ?string $branchId = null): array
    {
        $now = Carbon::now();
        $dateRanges = $this->resolveDateRanges($period, $now, $workspaceId);

        $start = $dateRanges['start'];
        $end = $dateRanges['end'];
        $prevStart = $dateRanges['prevStart'];
        $prevEnd = $dateRanges['prevEnd'];
        $periodLabel = $dateRanges['periodLabel'];
        $growthLabel = $dateRanges['growthLabel'];
        $normalizedPeriod = $dateRanges['period'];

        // 1. Single aggregation query for primary period summary
        $summary = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as gross_sales, COALESCE(SUM(discount_amount), 0) as total_discount, COALESCE(SUM(final_amount), 0) as total_revenue')
            ->first();

        $totalOrders = (int) ($summary->total_orders ?? 0);
        $grossSales = (float) ($summary->gross_sales ?? 0.0);
        $totalDiscount = (float) ($summary->total_discount ?? 0.0);
        $totalRevenue = (float) ($summary->total_revenue ?? 0.0);
        $averageOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / $totalOrders) : 0;

        // 2. Query previous period revenue for PoP delta calculation
        $prevRevenue = (float) (DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('final_amount') ?? 0.0);

        $growthPercent = $prevRevenue > 0
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($totalRevenue > 0 ? 100.0 : 0.0);

        // 3. Optimized breakdown retrieval: 1 query + in-memory bucketing
        $ordersInPeriod = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select('created_at', 'final_amount')
            ->get();

        $breakdown = $this->buildBreakdownPoints($normalizedPeriod, $start, $end, $ordersInPeriod);

        // 4. Top 10 products
        $topProducts = $this->buildTopProducts($workspaceId, $start, $end, $branchId, $totalRevenue);

        // 5. Category breakdown
        $categoryBreakdown = $this->buildCategoryBreakdown($workspaceId, $start, $end, $branchId, $totalRevenue);

        // 6. Payment methods breakdown
        $paymentMethods = $this->buildPaymentMethods($workspaceId, $start, $end, $branchId, $totalRevenue);

        return [
            'period' => $normalizedPeriod,
            'period_label' => $periodLabel,
            'total_revenue' => (int) $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'growth_percent' => $growthPercent,
            'growth_label' => $growthLabel,
            'gross_sales' => (int) $grossSales,
            'total_discount' => (int) $totalDiscount,
            'net_revenue' => (int) $totalRevenue,
            'breakdown' => $breakdown,
            'top_products' => $topProducts,
            'category_breakdown' => $categoryBreakdown,
            'payment_methods' => $paymentMethods,
        ];
    }

    /**
     * @return array{period: string, start: Carbon, end: Carbon, prevStart: Carbon, prevEnd: Carbon, periodLabel: string, growthLabel: string}
     */
    private function resolveDateRanges(string $period, Carbon $now, string $workspaceId): array
    {
        switch ($period) {
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                $prevStart = $start->copy()->subWeek();
                $prevEnd = $end->copy()->subWeek();
                $periodLabel = 'Pekan Ini';
                $growthLabel = 'vs pekan lalu';
                $normalizedPeriod = 'week';
                break;

            case 'month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $prevStart = $start->copy()->subMonth();
                $prevEnd = $end->copy()->subMonth();
                $periodLabel = 'Bulan Ini';
                $growthLabel = 'vs bulan lalu';
                $normalizedPeriod = 'month';
                break;

            case 'year':
                $wsCreatedRaw = DB::table('workspaces')->where('id', $workspaceId)->value('created_at');
                $earliestOrderRaw = DB::table('orders')->where('workspace_id', $workspaceId)->min('created_at');

                $wsCreatedAt = $wsCreatedRaw ? Carbon::parse((string) $wsCreatedRaw) : $now->copy()->startOfYear();
                if ($earliestOrderRaw) {
                    $earliestOrderDate = Carbon::parse((string) $earliestOrderRaw);
                    if ($earliestOrderDate->isBefore($wsCreatedAt)) {
                        $wsCreatedAt = $earliestOrderDate;
                    }
                }

                // Mulai dari bulan pembukaan workspace / transaksi pertama, berakhir di hari saat ini
                $earliestAllowed = $now->copy()->subYears(2)->startOfMonth();
                $start = $wsCreatedAt->isAfter($earliestAllowed) ? $wsCreatedAt->copy()->startOfMonth() : $now->copy()->startOfYear();
                $end = $now->copy()->endOfDay();

                $durationDays = $start->diffInDays($end) ?: 1;
                $prevEnd = $start->copy()->subDay()->endOfDay();
                $prevStart = $prevEnd->copy()->subDays((int) $durationDays)->startOfDay();

                $periodLabel = 'Tahun Berjalan';
                $growthLabel = 'vs periode lalu';
                $normalizedPeriod = 'year';
                break;

            case 'all':
                $earliestOrderRaw = DB::table('orders')->where('workspace_id', $workspaceId)->min('created_at');
                $wsCreatedRaw = DB::table('workspaces')->where('id', $workspaceId)->value('created_at');

                $wsCreatedAt = $wsCreatedRaw ? Carbon::parse((string) $wsCreatedRaw) : $now->copy()->subYears(2)->startOfMonth();
                if ($earliestOrderRaw) {
                    $earliestOrderDate = Carbon::parse((string) $earliestOrderRaw);
                    if ($earliestOrderDate->isBefore($wsCreatedAt)) {
                        $wsCreatedAt = $earliestOrderDate;
                    }
                }

                $start = $wsCreatedAt->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();

                $durationDays = $start->diffInDays($end) ?: 1;
                $prevEnd = $start->copy()->subDay()->endOfDay();
                $prevStart = $prevEnd->copy()->subDays((int) $durationDays)->startOfDay();

                $periodLabel = 'Sepanjang Waktu';
                $growthLabel = 'akumulasi total';
                $normalizedPeriod = 'all';
                break;

            case 'day':
            default:
                $normalizedPeriod = 'day';
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDay();
                $prevEnd = $end->copy()->subDay();
                $periodLabel = 'Hari Ini';
                $growthLabel = 'vs kemarin';
                break;
        }

        return [
            'period' => $normalizedPeriod,
            'start' => $start,
            'end' => $end,
            'prevStart' => $prevStart,
            'prevEnd' => $prevEnd,
            'periodLabel' => $periodLabel,
            'growthLabel' => $growthLabel,
        ];
    }

    /**
     * @param Collection<int, object{created_at: string, final_amount: mixed}> $ordersInPeriod
     * @return array<int, array<string, mixed>>
     */
    private function buildBreakdownPoints(string $period, Carbon $start, Carbon $end, Collection $ordersInPeriod): array
    {
        $breakdown = [];

        // In-memory mapping orders with parsed Carbon timestamps
        $mappedOrders = $ordersInPeriod->map(fn ($order) => [
            'timestamp' => Carbon::parse((string) $order->created_at),
            'amount' => (float) $order->final_amount,
        ]);

        if ($period === 'day') {
            $hours = [
                ['h' => 8, 'l' => '08:00', 'sub' => 'Pagi'],
                ['h' => 10, 'l' => '10:00', 'sub' => 'Siang'],
                ['h' => 12, 'l' => '12:00', 'sub' => 'Makan Siang'],
                ['h' => 14, 'l' => '14:00', 'sub' => 'Sore Awal'],
                ['h' => 16, 'l' => '16:00', 'sub' => 'Coffee Break'],
                ['h' => 18, 'l' => '18:00', 'sub' => 'Peak Dinner'],
                ['h' => 20, 'l' => '20:00', 'sub' => 'Malam'],
                ['h' => 22, 'l' => '22:00', 'sub' => 'Closing'],
            ];

            foreach ($hours as $h) {
                $hStart = $start->copy()->setHour($h['h'])->setMinute(0)->setSecond(0);
                $hEnd = $hStart->copy()->addHours(2)->subSecond();

                $matching = $mappedOrders->filter(fn ($o) => $o['timestamp']->betweenIncluded($hStart, $hEnd));
                $count = $matching->count();
                $rev = (float) $matching->sum('amount');

                $breakdown[] = [
                    'id' => 'h-' . str_pad((string) $h['h'], 2, '0', STR_PAD_LEFT),
                    'label' => $h['l'],
                    'subLabel' => $h['sub'],
                    'revenue' => (int) $rev,
                    'orders_count' => $count,
                    'average_ticket' => $count > 0 ? (int) round($rev / $count) : 0,
                ];
            }
        } elseif ($period === 'week') {
            $days = [
                ['d' => 1, 'l' => 'Senin'],
                ['d' => 2, 'l' => 'Selasa'],
                ['d' => 3, 'l' => 'Rabu'],
                ['d' => 4, 'l' => 'Kamis'],
                ['d' => 5, 'l' => 'Jumat'],
                ['d' => 6, 'l' => 'Sabtu'],
                ['d' => 7, 'l' => 'Minggu'],
            ];

            foreach ($days as $d) {
                $dStart = $start->copy()->startOfWeek()->addDays($d['d'] - 1)->startOfDay();
                $dEnd = $dStart->copy()->endOfDay();

                $matching = $mappedOrders->filter(fn ($o) => $o['timestamp']->betweenIncluded($dStart, $dEnd));
                $count = $matching->count();
                $rev = (float) $matching->sum('amount');

                $breakdown[] = [
                    'id' => 'w-' . $d['d'],
                    'label' => $d['l'],
                    'subLabel' => $dStart->format('d M'),
                    'revenue' => (int) $rev,
                    'orders_count' => $count,
                    'average_ticket' => $count > 0 ? (int) round($rev / $count) : 0,
                ];
            }
        } elseif ($period === 'month') {
            for ($w = 1; $w <= 4; $w++) {
                $wStart = $start->copy()->addDays(($w - 1) * 7)->startOfDay();
                $wEnd = $w === 4 ? $end->copy() : $wStart->copy()->addDays(6)->endOfDay();

                $matching = $mappedOrders->filter(fn ($o) => $o['timestamp']->betweenIncluded($wStart, $wEnd));
                $count = $matching->count();
                $rev = (float) $matching->sum('amount');

                $breakdown[] = [
                    'id' => 'm-w' . $w,
                    'label' => 'Minggu ' . $w,
                    'subLabel' => $wStart->format('d M') . ' - ' . $wEnd->format('d M'),
                    'revenue' => (int) $rev,
                    'orders_count' => $count,
                    'average_ticket' => $count > 0 ? (int) round($rev / $count) : 0,
                ];
            }
        } else {
            // Periode Tahunan & Sepanjang Waktu: Dari bulan pembuatan workspace hingga bulan saat ini
            $cursor = $start->copy()->startOfMonth();
            $currentMonth = $end->copy()->startOfMonth();

            while ($cursor->lte($currentMonth)) {
                $mStart = $cursor->copy()->startOfMonth();
                $mEnd = $cursor->isSameMonth($end) ? $end->copy() : $cursor->copy()->endOfMonth();

                $matching = $mappedOrders->filter(fn ($o) => $o['timestamp']->betweenIncluded($mStart, $mEnd));
                $count = $matching->count();
                $rev = (float) $matching->sum('amount');

                $breakdown[] = [
                    'id' => ($period === 'all' ? 'all-' : 'y-') . $cursor->format('Y-m'),
                    'label' => $cursor->format('M Y'),
                    'subLabel' => $cursor->format('Y'),
                    'revenue' => (int) $rev,
                    'orders_count' => $count,
                    'average_ticket' => $count > 0 ? (int) round($rev / $count) : 0,
                ];

                $cursor->addMonth();
            }
        }

        return $breakdown;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTopProducts(string $workspaceId, Carbon $start, Carbon $end, ?string $branchId, float $totalRevenue): array
    {
        $topProductsRaw = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.workspace_id', $workspaceId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
            ->select(
                'order_items.product_name as name',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.subtotal) as total_amount')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $topProducts = [];
        foreach ($topProductsRaw as $p) {
            $amt = (float) $p->total_amount;
            $topProducts[] = [
                'name' => (string) $p->name,
                'quantity' => (int) $p->quantity,
                'total_amount' => (int) $amt,
                'share_percent' => $totalRevenue > 0 ? (int) round(($amt / $totalRevenue) * 100) : 0,
            ];
        }

        return $topProducts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoryBreakdown(string $workspaceId, Carbon $start, Carbon $end, ?string $branchId, float $totalRevenue): array
    {
        $categoriesRaw = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.workspace_id', $workspaceId)
            ->whereBetween('orders.created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
            ->select(
                DB::raw("COALESCE(categories.name, 'Menu & Minuman') as name"),
                DB::raw('SUM(order_items.subtotal) as total_amount')
            )
            ->groupBy(DB::raw("COALESCE(categories.name, 'Menu & Minuman')"))
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $categoryBreakdown = [];
        foreach ($categoriesRaw as $c) {
            $amt = (float) $c->total_amount;
            $categoryBreakdown[] = [
                'name' => (string) $c->name,
                'total_amount' => (int) $amt,
                'share_percent' => $totalRevenue > 0 ? round(($amt / $totalRevenue) * 100, 1) : 0.0,
            ];
        }

        return $categoryBreakdown;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPaymentMethods(string $workspaceId, Carbon $start, Carbon $end, ?string $branchId, float $totalRevenue): array
    {
        $paymentMethodsRaw = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(final_amount) as amount')
            )
            ->groupBy('payment_method')
            ->orderByDesc('amount')
            ->get();

        $paymentMethods = [];
        foreach ($paymentMethodsRaw as $pm) {
            $amt = (float) $pm->amount;
            $methodLabel = match (strtoupper((string) $pm->payment_method)) {
                'QRIS' => 'QRIS Dinamis (GoPay/Shopee/OVO)',
                'CASH' => 'Tunai / Cash Register',
                'EDC', 'CARD', 'DEBIT' => 'Kartu Debit EDC (BCA/Mandiri)',
                default => (string) $pm->payment_method,
            };

            $paymentMethods[] = [
                'method' => $methodLabel,
                'count' => (int) $pm->count,
                'amount' => (int) $amt,
                'percent' => $totalRevenue > 0 ? (int) round(($amt / $totalRevenue) * 100) : 0,
            ];
        }

        return $paymentMethods;
    }
}
