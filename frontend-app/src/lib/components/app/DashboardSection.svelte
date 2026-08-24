<script lang="ts">
  import {
    ArrowUpRight,
    CreditCard,
    QrCode,
    Banknote,
    Package,
    ShieldCheck,
    BarChart3,
    PieChart,
    ChevronRight,
    ChevronDown,
  } from 'lucide-svelte';
  import type { TimeframePeriod, TimeframeSalesPoint, User } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';

  interface Props {
    currentUser: User;
    onNavigate: (domain: 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings', subTab?: string) => void;
  }

  let { currentUser, onNavigate }: Props = $props();

  let selectedTimeframe = $state<TimeframePeriod>('day');
  let selectedPointId = $state<string | null>(null);
  let activeInsightTab = $state<'mix' | 'payments'>('mix');
  let activeMixSubTab = $state<'menu' | 'kategori'>('menu');

  let timeframeData = $derived(inventoryService.getTimeframeMetrics(selectedTimeframe));

  let activePoint = $derived.by<TimeframeSalesPoint | null>(() => {
    if (!timeframeData.breakdown || timeframeData.breakdown.length === 0) return null;
    if (selectedPointId) {
      const found = timeframeData.breakdown.find((p: TimeframeSalesPoint) => p.id === selectedPointId);
      if (found) return found;
    }
    return timeframeData.breakdown[timeframeData.breakdown.length - 1] || null;
  });

  let highestRevenuePoint = $derived.by(() => {
    if (!timeframeData.breakdown || timeframeData.breakdown.length === 0) return 1;
    return Math.max(...timeframeData.breakdown.map((p: TimeframeSalesPoint) => p.revenue), 1);
  });

  let pointCount = $derived(timeframeData.breakdown.length);
  let candleWidthClass = $derived(
    pointCount > 8
      ? 'max-w-[14px] sm:max-w-[18px]'
      : pointCount > 4
      ? 'max-w-[28px] sm:max-w-[36px]'
      : 'max-w-[48px] sm:max-w-[56px]'
  );
  let candleGapClass = $derived(
    pointCount > 8
      ? 'gap-1 sm:gap-1.5'
      : pointCount > 4
      ? 'gap-2 sm:gap-3'
      : 'gap-4 sm:gap-6'
  );
  let labelTextClass = $derived(
    pointCount > 8
      ? 'text-[9px] sm:text-[10px]'
      : 'text-[10px] sm:text-[11px]'
  );

  function formatCurrency(amount: number) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(amount);
  }
</script>

<div class="space-y-4 sm:space-y-6 font-sans pb-4">
  <!-- Top Operational Banner -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white border border-[#d9d9dd] rounded-[24px] p-4 sm:p-6">
    <div>
      <div class="flex items-center gap-2">
        <span class="text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full bg-[#eeece7] text-[#17171c] font-medium">
          Operasional
        </span>
        <span class="text-xs text-[#75758a]">{currentUser.branch_name || 'Outlet Utama'}</span>
      </div>
      <h1 class="text-xl sm:text-2xl font-medium text-[#212121] tracking-tight mt-1">
        Ringkasan Bisnis
      </h1>
      <div class="flex items-center gap-2 text-xs text-[#616161] font-normal mt-0.5">
        <span class="w-2 h-2 rounded-full bg-[#16a34a] animate-pulse shrink-0"></span>
        <span>Sistem Siap Digunakan</span>
      </div>
    </div>

    <div class="flex items-center gap-2 self-stretch sm:self-center">
      <button
        type="button"
        onclick={() => onNavigate('katalog', 'menu')}
        class="flex-1 sm:flex-initial px-3.5 py-2 text-xs font-medium bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5"
      >
        <Package class="w-3.5 h-3.5" />
        <span>Menu</span>
      </button>

      <button
        type="button"
        onclick={() => onNavigate('tim', 'presensi')}
        class="flex-1 sm:flex-initial px-3.5 py-2 text-xs font-medium bg-white hover:bg-[#eeece7] border border-[#d9d9dd] text-[#212121] rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5"
      >
        <ShieldCheck class="w-3.5 h-3.5" />
        <span>Presensi</span>
      </button>
    </div>
  </div>

  <!-- Unified Executive Omzet & Chart Hub -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-4 sm:p-6 space-y-5">
    <!-- Top Row: Timeframe Dropdown & Main Revenue Headline -->
    <div class="space-y-3">
      <div class="flex items-start justify-between gap-2 pb-3 border-b border-[#f2f2f2]">
        <div class="min-w-0">
          <div class="text-[10px] sm:text-[11px] font-mono text-[#75758a] uppercase tracking-wider truncate">
            Total Omzet ({timeframeData.period_label})
          </div>
          <div class="text-xl sm:text-3xl font-medium font-mono text-[#17171c] tracking-tight mt-0.5 truncate">
            {formatCurrency(timeframeData.total_revenue)}
          </div>
        </div>

        <div class="flex flex-col items-end gap-1 shrink-0">
          <!-- Compact Timeframe Dropdown Selector -->
          <div class="relative">
            <select
              bind:value={selectedTimeframe}
              onchange={() => (selectedPointId = null)}
              class="appearance-none bg-[#eeece7]/80 hover:bg-[#eeece7] text-[#17171c] text-[11px] sm:text-xs font-medium pl-2.5 pr-6 py-1 rounded-full border border-[#d9d9dd] cursor-pointer focus:outline-hidden transition-all shadow-2xs"
            >
              <option value="day">Hari Ini</option>
              <option value="week">Pekan Ini</option>
              <option value="month">Bulan Ini</option>
              <option value="year">Tahun Ini</option>
            </select>
            <ChevronDown class="w-3 h-3 text-[#75758a] absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>

          <div class="flex items-center gap-1 text-[10px] sm:text-[11px] font-mono font-medium text-[#00875a]">
            <ArrowUpRight class="w-3 h-3" />
            <span>+{timeframeData.growth_percent}% {timeframeData.growth_label}</span>
          </div>
        </div>
      </div>

      <!-- 3 Key Metric Badges in 1 Compact Row -->
      <div class="grid grid-cols-3 gap-2 pt-1">
        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Volume Struk</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c] mt-0.5 truncate">
            {timeframeData.total_orders.toLocaleString('id-ID')}
          </div>
        </div>

        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Rata-rata AOV</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c] mt-0.5 truncate">
            {formatCurrency(timeframeData.average_order_value)}
          </div>
        </div>

        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Total Diskon</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#e5484d] mt-0.5 truncate">
            -{formatCurrency(timeframeData.total_discount)}
          </div>
        </div>
      </div>
    </div>

    <!-- Chart Section with Dynamic Slimming Candles -->
    <div class="pt-2 space-y-3">
      <div class="flex items-center justify-between text-xs text-[#75758a]">
        <span class="flex items-center gap-1.5 font-medium text-[#212121]">
          <BarChart3 class="w-3.5 h-3.5 text-[#17171c]" />
          <span>Grafik Tren Penjualan</span>
        </span>
        <span class="text-[11px] font-mono text-[#75758a]">Ketuk batang untuk inspeksi</span>
      </div>

      <!-- Dynamic Candle Track or Empty State -->
      {#if timeframeData.breakdown.length === 0}
        <div class="py-10 text-center text-[#93939f] space-y-1.5 border-b border-[#d9d9dd]/60">
          <BarChart3 class="w-7 h-7 mx-auto text-[#93939f] opacity-40" />
          <p class="text-xs font-medium text-[#17171c]">Belum ada aktivitas penjualan pada periode ini</p>
          <p class="text-[11px] text-[#75758a]">Lakukan transaksi di POS Kasir untuk memantau grafik omzet secara realtime.</p>
        </div>
      {:else}
        <div class="w-full pt-1">
          <div class={`w-full flex items-end justify-between h-44 border-b border-[#d9d9dd] pb-2 px-0.5 ${candleGapClass}`}>
            {#each timeframeData.breakdown as point}
              {@const heightPercent = Math.max(8, Math.round((point.revenue / highestRevenuePoint) * 100))}
              {@const isSelected = activePoint?.id === point.id}
              {@const isHighest = point.revenue === highestRevenuePoint}

              <button
                type="button"
                onclick={() => (selectedPointId = point.id)}
                class="flex-1 flex flex-col items-center gap-1 group h-full justify-end cursor-pointer focus:outline-hidden transition-all min-w-0"
                title={`${point.label}: ${formatCurrency(point.revenue)}`}
              >
                <!-- Selected Value Chip -->
                <div
                  class={`transition-all text-[9px] font-mono px-1.5 py-0.5 rounded whitespace-nowrap -translate-y-0.5 ${
                    isSelected
                      ? 'bg-[#17171c] text-white opacity-100 font-medium scale-105'
                      : 'opacity-0 pointer-events-none'
                  }`}
                >
                  {formatCurrency(point.revenue)}
                </div>

                <!-- Bar Pillar (Dynamic Width based on point count) -->
                <div
                  style="height: {heightPercent}%"
                  class={`w-full ${candleWidthClass} rounded-t-[4px] sm:rounded-t-[6px] transition-all duration-300 ${
                    isSelected
                      ? 'bg-[#17171c] ring-2 ring-[#1863dc]'
                      : isHighest
                      ? 'bg-[#17171c]/75'
                      : 'bg-[#eeece7] hover:bg-[#d9d9dd]'
                  }`}
                ></div>

                <!-- Clean Bar Label -->
                <div class="text-center pt-1 w-full">
                  <div class={`${labelTextClass} font-mono truncate ${
                    isSelected ? 'font-bold text-[#17171c] underline' : 'text-[#616161]'
                  }`}>
                    {point.label}
                  </div>
                </div>
              </button>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Compact Active Point Readout Card (Without Black Avatar Box) -->
      {#if activePoint}
        <div class="bg-[#fbfbfb] border border-[#d9d9dd] rounded-xl p-3 flex items-center justify-between gap-3 text-xs">
          <div class="min-w-0">
            <div class="font-medium text-[#212121] text-xs sm:text-sm truncate">
              {activePoint.label} ({activePoint.subLabel})
            </div>
            <div class="text-[10px] text-[#75758a] font-mono mt-0.5">
              {((activePoint.revenue / (timeframeData.total_revenue || 1)) * 100).toFixed(1)}% dari total omzet
            </div>
          </div>

          <div class="flex items-center gap-3 sm:gap-6 font-mono shrink-0 text-right">
            <div>
              <div class="text-[9px] text-[#75758a] uppercase">Omzet</div>
              <div class="font-medium text-[#17171c] text-xs sm:text-sm">
                {formatCurrency(activePoint.revenue)}
              </div>
            </div>
            <div>
              <div class="text-[9px] text-[#75758a] uppercase">Struk</div>
              <div class="font-medium text-[#17171c] text-xs sm:text-sm">
                {activePoint.orders_count}
              </div>
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>


  <!-- Deep Dive Analytics Card with Segmented Switcher (Menu Mix vs Pembayaran) -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-4 sm:p-6 space-y-4">
    <!-- Sub-tab switcher to prevent mobile stacking overload -->
    <div class="flex items-center gap-1 bg-[#eeece7]/50 p-1 rounded-full w-full">
      <button
        type="button"
        onclick={() => (activeInsightTab = 'mix')}
        class={`flex-1 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
          activeInsightTab === 'mix'
            ? 'bg-[#17171c] text-white shadow-xs'
            : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        <PieChart class="w-3.5 h-3.5" />
        <span>Komposisi Menu</span>
      </button>

      <button
        type="button"
        onclick={() => (activeInsightTab = 'payments')}
        class={`flex-1 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
          activeInsightTab === 'payments'
            ? 'bg-[#17171c] text-white shadow-xs'
            : 'text-[#616161] hover:text-[#212121]'
        }`}
      >
        <CreditCard class="w-3.5 h-3.5" />
        <span>Saluran Pembayaran</span>
      </button>
    </div>

    <!-- TAB 1: PRODUCT MIX & CATEGORY SHARE -->
    {#if activeInsightTab === 'mix'}
      <div class="space-y-4 pt-1">
        <div class="flex items-center justify-between">
          <span class="text-xs text-[#75758a]">Peringkat volume &amp; omzet produk</span>
          <div class="flex items-center bg-[#eeece7]/60 p-0.5 rounded-full text-[11px] font-medium">
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'menu')}
              class={`px-2.5 py-0.5 rounded-full transition-all cursor-pointer ${
                activeMixSubTab === 'menu' ? 'bg-[#17171c] text-white' : 'text-[#616161]'
              }`}
            >
              Top Menu
            </button>
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'kategori')}
              class={`px-2.5 py-0.5 rounded-full transition-all cursor-pointer ${
                activeMixSubTab === 'kategori' ? 'bg-[#17171c] text-white' : 'text-[#616161]'
              }`}
            >
              Kategori
            </button>
          </div>
        </div>

        {#if timeframeData.top_products.length === 0}
          <div class="py-10 text-center text-[#93939f] space-y-2">
            <Package class="w-7 h-7 mx-auto text-[#93939f] opacity-40" />
            <p class="text-xs font-medium text-[#17171c]">Belum ada produk terjual</p>
            <p class="text-[11px] text-[#75758a]">Tambah menu di katalog atau lakukan transaksi kasir pertama.</p>
          </div>
        {:else if activeMixSubTab === 'menu'}
          <div class="space-y-3">
            {#each timeframeData.top_products as prod, idx}
              <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-4 h-4 rounded-full bg-[#eeece7] text-[#17171c] font-mono text-[9px] flex items-center justify-center shrink-0">
                      0{idx + 1}
                    </span>
                    <span class="font-medium text-[#212121] truncate">{prod.name}</span>
                  </div>
                  <div class="flex items-center gap-2 font-mono shrink-0">
                    <span class="text-[#75758a] text-[11px]">{prod.quantity.toLocaleString('id-ID')}x</span>
                    <span class="font-medium text-[#17171c]">{formatCurrency(prod.total_amount)}</span>
                  </div>
                </div>

                <div class="w-full bg-[#eeece7]/60 h-1.5 rounded-full overflow-hidden">
                  <div
                    style="width: {prod.share_percent}%"
                    class="bg-[#17171c] h-full rounded-full transition-all duration-500"
                  ></div>
                </div>
              </div>
            {/each}
          </div>
        {:else}
          <div class="space-y-3">
            {#each timeframeData.category_breakdown as cat}
              <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-medium text-[#212121]">{cat.name}</span>
                  <div class="flex items-center gap-2 font-mono">
                    <span class="text-[#75758a] text-[11px]">{cat.share_percent}%</span>
                    <span class="font-medium text-[#17171c]">{formatCurrency(cat.total_amount)}</span>
                  </div>
                </div>

                <div class="w-full bg-[#eeece7]/60 h-1.5 rounded-full overflow-hidden">
                  <div
                    style="width: {cat.share_percent}%"
                    class="bg-[#1863dc] h-full rounded-full transition-all duration-500"
                  ></div>
                </div>
              </div>
            {/each}
          </div>
        {/if}

        <div class="pt-2 border-t border-[#f2f2f2] text-right">
          <button
            type="button"
            onclick={() => onNavigate('katalog', 'menu')}
            class="text-xs font-medium text-[#1863dc] hover:underline cursor-pointer inline-flex items-center gap-0.5"
          >
            <span>Katalog Menu Lengkap</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    {:else}
      <!-- TAB 2: PAYMENT CHANNELS -->
      <div class="space-y-4 pt-1">
        {#if timeframeData.payment_methods.length === 0}
          <div class="py-10 text-center text-[#93939f] space-y-1.5">
            <CreditCard class="w-7 h-7 mx-auto text-[#93939f] opacity-40" />
            <p class="text-xs font-medium text-[#17171c]">Belum ada riwayat saluran pembayaran</p>
            <p class="text-[11px] text-[#75758a]">Data komposisi pembayaran (QRIS, Tunai, EDC) akan muncul setelah kasir memproses pesanan.</p>
          </div>
        {:else}
          <!-- Visual Stacked Bar -->
          <div class="space-y-1.5">
            <div class="w-full h-2.5 rounded-full bg-[#eeece7] flex overflow-hidden">
              {#each timeframeData.payment_methods as method, i}
                <div
                  style="width: {method.percent}%"
                  class={`h-full transition-all duration-500 ${
                    i === 0 ? 'bg-[#17171c]' : i === 1 ? 'bg-[#00875a]' : 'bg-[#1863dc]'
                  }`}
                  title={`${method.method}: ${method.percent}%`}
                ></div>
              {/each}
            </div>

            <div class="flex items-center justify-between text-[10px] font-mono text-[#75758a]">
              <span>Cashless ({timeframeData.payment_methods[0].percent + (timeframeData.payment_methods[2]?.percent || 0)}%)</span>
              <span>Tunai ({timeframeData.payment_methods[1]?.percent || 0}%)</span>
            </div>
          </div>

          <!-- Payment Channel Items -->
          <div class="space-y-2">
            {#each timeframeData.payment_methods as method, i}
              <div class="p-3 rounded-xl bg-[#fbfbfb] border border-[#d9d9dd] flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5 min-w-0">
                  <span class={`p-1.5 rounded-lg ${
                    i === 0 ? 'bg-[#17171c] text-white' : i === 1 ? 'bg-[#edfce9] text-[#003c33]' : 'bg-[#f1f5ff] text-[#1863dc]'
                  }`}>
                    {#if i === 0}
                      <QrCode class="w-3.5 h-3.5" />
                    {:else if i === 1}
                      <Banknote class="w-3.5 h-3.5" />
                    {:else}
                      <CreditCard class="w-3.5 h-3.5" />
                    {/if}
                  </span>
                  <div class="truncate">
                    <div class="font-medium text-[#212121] truncate">{method.method}</div>
                    <div class="text-[10px] text-[#75758a] font-mono">{method.count.toLocaleString('id-ID')} transaksi</div>
                  </div>
                </div>

                <div class="text-right font-mono shrink-0">
                  <div class="font-medium text-[#17171c]">{formatCurrency(method.amount)}</div>
                  <div class="text-[10px] text-[#75758a]">{method.percent}% porsi</div>
                </div>
              </div>
            {/each}
          </div>
        {/if}

        <div class="pt-2 border-t border-[#f2f2f2] text-right">
          <button
            type="button"
            onclick={() => onNavigate('finance', 'payroll')}
            class="text-xs font-medium text-[#1863dc] hover:underline cursor-pointer inline-flex items-center gap-0.5"
          >
            <span>Buka Laporan Finansial</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    {/if}
  </div>
</div>
