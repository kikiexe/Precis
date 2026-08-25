<script lang="ts">
  import {
    ArrowUpRight,
    ArrowDownRight,
    CreditCard,
    QrCode,
    Banknote,
    Package,
    ShieldCheck,
    BarChart3,
    PieChart,
    ChevronRight,
    ChevronDown,
    Sparkles,
    Percent
  } from 'lucide-svelte';
  import type { TimeframePeriod, TimeframeSalesPoint, User, BranchItem } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';

  interface Props {
    currentUser: User;
    branches?: BranchItem[];
    selectedBranchId?: string;
    onSelectBranch?: (branchId: string) => void;
    onNavigate: (domain: 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings', subTab?: string) => void;
  }

  let {
    currentUser,
    branches = [],
    selectedBranchId = 'ALL',
    onSelectBranch,
    onNavigate,
  }: Props = $props();

  let selectedTimeframe = $state<TimeframePeriod>('month');
  let selectedPointId = $state<string | null>(null);
  let activeInsightTab = $state<'mix' | 'payments' | 'discount'>('mix');
  let activeMixSubTab = $state<'menu' | 'kategori'>('menu');
  let chartMode = $state<'net' | 'gross_comparison'>('net');

  let timeframeData = $state(inventoryService.getTimeframeMetrics('month'));
  let isLoading = $state(false);

  $effect(() => {
    const period = selectedTimeframe;
    const branch = selectedBranchId;
    isLoading = true;
    inventoryService.fetchLiveSalesAnalytics(period, branch).then((data) => {
      timeframeData = data;
      isLoading = false;
    });
  });

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

  // Human Readable Business Insight Generator
  let businessInsights = $derived.by(() => {
    const growth = timeframeData.growth_percent;
    const topProd = timeframeData.top_products[0]?.name || 'Menu Utama';
    const topProdShare = timeframeData.top_products[0]?.share_percent || 0;
    const grossTotal = timeframeData.total_revenue + timeframeData.total_discount;
    const discountRatio = ((timeframeData.total_discount / (grossTotal || 1)) * 100).toFixed(1);

    if (growth >= 0) {
      return {
        isPositive: true,
        title: `Penjualan ${timeframeData.period_label} Naik +${growth}% (${timeframeData.growth_label})`,
        summary: `Pendapatan bersih tercatat ${formatCurrency(timeframeData.total_revenue)} dari ${timeframeData.total_orders.toLocaleString('id-ID')} transaksi pesanan.`,
        recommendations: [
          `Rata-rata nilai belanja per pelanggan berada di angka ${formatCurrency(timeframeData.average_order_value)} per transaksi.`,
          `Menu "${topProd}" menyumbang kontribusi terbesar (${topProdShare}% total volume pesanan).`,
          `Total potongan diskon & promosi terhitung sebesar ${formatCurrency(timeframeData.total_discount)} (${discountRatio}% dari nilai kotor).`
        ]
      };
    } else {
      return {
        isPositive: false,
        title: `Penjualan ${timeframeData.period_label} Tercatat ${formatCurrency(timeframeData.total_revenue)} (${growth}% ${timeframeData.growth_label})`,
        summary: `Total ${timeframeData.total_orders.toLocaleString('id-ID')} pesanan berhasil diproses dengan rata-rata tiket ${formatCurrency(timeframeData.average_order_value)}.`,
        recommendations: [
          `Volume transaksi di jam-jam tertentu mengalami penurunan dibanding periode lalu.`,
          `Menu "${topProd}" tetap stabil sebagai produk terfavorit pelanggan (${topProdShare}% porsi).`,
          `Saran: Tingkatkan penjualan dengan paket bundling menu atau diskon khusus di jam sepi.`
        ]
      };
    }
  });

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
        {#if branches.length > 0}
          <div class="relative inline-block">
            <select
              value={selectedBranchId}
              onchange={(e) => onSelectBranch?.((e.target as HTMLSelectElement).value)}
              class="appearance-none px-3 pr-7 py-1 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="ALL">Semua Cabang (Konsolidasi)</option>
              {#each branches as b}
                <option value={b.id}>{b.name}</option>
              {/each}
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        {:else}
          <span class="text-xs text-[#75758a]">{currentUser.branch_name || 'Norde Coffee'}</span>
        {/if}
      </div>
      <h1 class="text-xl sm:text-2xl font-medium text-[#212121] tracking-tight mt-1">
        Ringkasan Penjualan &amp; Bisnis
      </h1>
      <div class="flex items-center gap-2 text-xs text-[#616161] font-normal mt-0.5">
        <span class="w-2 h-2 rounded-full bg-[#16a34a] animate-pulse shrink-0"></span>
        <span>Data Terhubung Realtime &bull; Sinkronisasi Kasir Aktif</span>
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

  <!-- Unified Revenue Headline & Chart Hub -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-4 sm:p-6 space-y-5">
    <!-- Top Row: Timeframe Dropdown & Main Revenue Headline -->
    <div class="space-y-3">
      <div class="flex items-start justify-between gap-2 pb-3 border-b border-[#f2f2f2]">
        <div class="min-w-0">
          <div class="text-[10px] sm:text-[11px] font-mono text-[#75758a] uppercase tracking-wider truncate">
            Total Kas Bersih ({timeframeData.period_label})
          </div>
          <div class="text-xl sm:text-3xl font-medium font-mono text-[#17171c] tracking-tight mt-0.5 truncate">
            {formatCurrency(timeframeData.total_revenue)}
          </div>
        </div>

        <div class="flex flex-col items-end gap-1 shrink-0">
          <!-- Timeframe Dropdown Selector -->
          <div class="relative">
            <select
              bind:value={selectedTimeframe}
              onchange={() => (selectedPointId = null)}
              class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs"
            >
              <option value="day">Harian (Per Jam)</option>
              <option value="week">Pekan Ini</option>
              <option value="month">Bulan Ini</option>
              <option value="year">Tahun Ini</option>
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>

          <div class={`flex items-center gap-1 text-[10px] sm:text-[11px] font-mono font-medium ${
            timeframeData.growth_percent >= 0 ? 'text-[#00875a]' : 'text-[#e5484d]'
          }`}>
            {#if timeframeData.growth_percent >= 0}
              <ArrowUpRight class="w-3 h-3" />
              <span>+{timeframeData.growth_percent}% {timeframeData.growth_label}</span>
            {:else}
              <ArrowDownRight class="w-3 h-3" />
              <span>{timeframeData.growth_percent}% {timeframeData.growth_label}</span>
            {/if}
          </div>
        </div>
      </div>

      <!-- 4 Key Metric Badges in 1 Row: Gross Sales, Order Count, AOV, Discount -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Pesanan Kotor (Gross)</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c] mt-0.5 truncate">
            {formatCurrency(timeframeData.total_revenue + timeframeData.total_discount)}
          </div>
        </div>

        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Volume Pesanan</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c] mt-0.5 truncate">
            {timeframeData.total_orders.toLocaleString('id-ID')}
          </div>
        </div>

        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Rata-rata Belanja (AOV)</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#17171c] mt-0.5 truncate">
            {formatCurrency(timeframeData.average_order_value)}
          </div>
        </div>

        <div class="bg-[#fbfbfb] border border-[#d9d9dd]/60 rounded-xl p-2.5 text-center">
          <div class="text-[9px] sm:text-[10px] text-[#75758a] uppercase font-mono truncate">Potongan Diskon</div>
          <div class="text-xs sm:text-sm font-medium font-mono text-[#e5484d] mt-0.5 truncate">
            -{formatCurrency(timeframeData.total_discount)}
          </div>
        </div>
      </div>
    </div>

    <!-- Chart Section: Multi-series Gross vs Net & Dynamic Track -->
    <div class="pt-2 space-y-3">
      <div class="flex items-center justify-between text-xs text-[#75758a]">
        <div class="flex items-center gap-2">
          <span class="flex items-center gap-1.5 font-medium text-[#212121]">
            <BarChart3 class="w-3.5 h-3.5 text-[#17171c]" />
            <span>Grafik Tren Penjualan</span>
          </span>
          <div class="flex items-center gap-1 bg-[#eeece7]/60 p-0.5 rounded-full text-[10px] font-mono">
            <button
              type="button"
              onclick={() => (chartMode = 'net')}
              class={`px-2 py-0.5 rounded-full transition-all cursor-pointer ${
                chartMode === 'net' ? 'bg-[#17171c] text-white' : 'text-[#616161]'
              }`}
            >
              Net Sales
            </button>
            <button
              type="button"
              onclick={() => (chartMode = 'gross_comparison')}
              class={`px-2 py-0.5 rounded-full transition-all cursor-pointer ${
                chartMode === 'gross_comparison' ? 'bg-[#17171c] text-white' : 'text-[#616161]'
              }`}
            >
              Gross vs Net
            </button>
          </div>
        </div>

        {#if activePoint}
          <div class="font-mono text-xs text-[#17171c] font-medium hidden sm:block">
            {activePoint.label}: {formatCurrency(activePoint.revenue)} ({activePoint.orders_count} pesanan)
          </div>
        {/if}
      </div>

      <!-- Chart Visual Container -->
      <div class="h-44 sm:h-52 w-full pt-4 flex items-end justify-between {candleGapClass} border-b border-[#eeece7] pb-2 relative select-none">
        {#if timeframeData.breakdown.length === 0}
          <div class="w-full h-full flex items-center justify-center text-xs text-[#75758a] font-mono">
            {isLoading ? 'Memuat data analitik...' : 'Belum ada transaksi di periode ini.'}
          </div>
        {:else}
          {#each timeframeData.breakdown as point}
            {@const isSelected = activePoint?.id === point.id}
            {@const heightPercent = Math.max(8, Math.round((point.revenue / highestRevenuePoint) * 100))}

            <button
              type="button"
              onclick={() => (selectedPointId = point.id)}
              class="h-full flex-1 flex flex-col justify-end items-center group cursor-pointer relative focus:outline-hidden"
            >
              <!-- Bar Column -->
              <div class="w-full {candleWidthClass} flex flex-col justify-end items-center h-full">
                {#if chartMode === 'gross_comparison'}
                  <!-- Multi-layer bar: Net (solid) & Discount portion -->
                  <div
                    style="height: {heightPercent}%"
                    class={`w-full rounded-t-sm transition-all duration-300 ${
                      isSelected
                        ? 'bg-[#17171c]'
                        : 'bg-[#17171c]/70 group-hover:bg-[#17171c]'
                    }`}
                  ></div>
                {:else}
                  <!-- Single Net Sales Bar -->
                  <div
                    style="height: {heightPercent}%"
                    class={`w-full rounded-t-sm transition-all duration-300 ${
                      isSelected
                        ? 'bg-[#17171c]'
                        : 'bg-[#1863dc]/60 group-hover:bg-[#1863dc]'
                    }`}
                  ></div>
                {/if}
              </div>

              <!-- Time Point Label -->
              <span class="mt-2 font-mono {labelTextClass} transition-colors truncate max-w-full text-center {
                isSelected ? 'font-bold text-[#17171c]' : 'text-[#75758a] group-hover:text-[#212121]'
              }">
                {point.label}
              </span>
            </button>
          {/each}
        {/if}
      </div>
    </div>
  </div>

  <!-- Analisis & Rekomendasi Bisnis (Human Readable) -->
  <div class="bg-[#17171c] text-white border border-[#17171c] rounded-[24px] p-4 sm:p-6 space-y-3">
    <div class="flex items-center justify-between border-b border-white/10 pb-3">
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-white/10 text-white flex items-center justify-center shrink-0">
          <Sparkles class="w-4 h-4 text-[#00875a]" />
        </div>
        <div>
          <h3 class="text-xs sm:text-sm font-medium text-white">Analisis &amp; Rekomendasi Penjualan</h3>
          <p class="text-[10px] font-mono text-[#93939f]">Insight performa bisnis &amp; perilaku belanja pelanggan</p>
        </div>
      </div>

      <span class="text-[10px] font-mono px-2.5 py-0.5 rounded-full bg-white/10 text-[#00875a] font-medium">
        Insight Pintar
      </span>
    </div>

    <div class="space-y-2 text-xs">
      <div class="font-medium text-white/95 text-xs sm:text-sm">
        {businessInsights.title}
      </div>
      <p class="text-white/80 text-[11px] leading-relaxed">
        {businessInsights.summary}
      </p>
      <ul class="space-y-1.5 text-white/70 text-[11px] list-disc list-inside leading-relaxed pt-1 border-t border-white/10">
        {#each businessInsights.recommendations as item}
          <li>{item}</li>
        {/each}
      </ul>
    </div>
  </div>

  <!-- Deep Dive Analytics Card: Menu Mix, Saluran Pembayaran, Dampak Diskon -->
  <div class="bg-white border border-[#d9d9dd] rounded-[24px] p-4 sm:p-6 space-y-4">
    <!-- Sub-tab switcher with Icon-Only unselected state & Icon+Text selected state -->
    <div class="flex items-center gap-1.5 bg-[#eeece7]/50 p-1 rounded-full w-full">
      <button
        type="button"
        title="Komposisi Menu"
        onclick={() => (activeInsightTab = 'mix')}
        class={`py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
          activeInsightTab === 'mix'
            ? 'flex-1 bg-[#17171c] text-white shadow-xs px-3'
            : 'text-[#616161] hover:text-[#212121] px-3 py-1.5'
        }`}
      >
        <PieChart class="w-3.5 h-3.5 shrink-0" />
        {#if activeInsightTab === 'mix'}
          <span class="whitespace-nowrap">Komposisi Menu</span>
        {/if}
      </button>

      <button
        type="button"
        title="Saluran Pembayaran"
        onclick={() => (activeInsightTab = 'payments')}
        class={`py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
          activeInsightTab === 'payments'
            ? 'flex-1 bg-[#17171c] text-white shadow-xs px-3'
            : 'text-[#616161] hover:text-[#212121] px-3 py-1.5'
        }`}
      >
        <CreditCard class="w-3.5 h-3.5 shrink-0" />
        {#if activeInsightTab === 'payments'}
          <span class="whitespace-nowrap">Saluran Pembayaran</span>
        {/if}
      </button>

      <button
        type="button"
        title="Dampak Diskon"
        onclick={() => (activeInsightTab = 'discount')}
        class={`py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
          activeInsightTab === 'discount'
            ? 'flex-1 bg-[#17171c] text-white shadow-xs px-3'
            : 'text-[#616161] hover:text-[#212121] px-3 py-1.5'
        }`}
      >
        <Percent class="w-3.5 h-3.5 shrink-0" />
        {#if activeInsightTab === 'discount'}
          <span class="whitespace-nowrap">Dampak Diskon</span>
        {/if}
      </button>
    </div>

    <!-- TAB 1: PRODUCT MIX & CATEGORY SHARE (Donut & Ranked Bar) -->
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
              Top 10 Menu
            </button>
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'kategori')}
              class={`px-2.5 py-0.5 rounded-full transition-all cursor-pointer ${
                activeMixSubTab === 'kategori' ? 'bg-[#17171c] text-white' : 'text-[#616161]'
              }`}
            >
              Top 5 Kategori
            </button>
          </div>
        </div>

        {#if timeframeData.top_products.length === 0}
          <div class="py-10 text-center text-[#93939f] space-y-2">
            <Package class="w-7 h-7 mx-auto text-[#93939f] opacity-40" />
            <p class="text-xs font-medium text-[#17171c]">Belum ada data produk terjual</p>
            <p class="text-[11px] text-[#75758a]">Data penjualan produk akan terakumulasi otomatis saat kasir memproses struk.</p>
          </div>
        {:else if activeMixSubTab === 'menu'}
          <!-- Top 10 Horizontal Ranked Bar -->
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
          <!-- Top 5 Categories Share -->
          <div class="space-y-3">
            <div class="w-full h-2 rounded-full bg-[#eeece7] flex overflow-hidden">
              {#each timeframeData.category_breakdown as cat, i}
                <div
                  style="width: {cat.share_percent}%"
                  class={`h-full transition-all duration-500 ${
                    i === 0 ? 'bg-[#17171c]' : i === 1 ? 'bg-[#00875a]' : i === 2 ? 'bg-[#1863dc]' : 'bg-[#75758a]'
                  }`}
                  title={`${cat.name}: ${cat.share_percent}%`}
                ></div>
              {/each}
            </div>

            <div class="space-y-2 pt-2">
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
          </div>
        {/if}

        <div class="pt-2 border-t border-[#f2f2f2] text-right">
          <button
            type="button"
            onclick={() => onNavigate('katalog', 'menu')}
            class="text-xs font-medium text-[#1863dc] hover:underline cursor-pointer inline-flex items-center gap-0.5"
          >
            <span>Buka Katalog Menu Lengkap</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    {:else if activeInsightTab === 'payments'}
      <!-- TAB 2: PAYMENT CHANNELS -->
      <div class="space-y-4 pt-1">
        {#if timeframeData.payment_methods.length === 0}
          <div class="py-10 text-center text-[#93939f] space-y-1.5">
            <CreditCard class="w-7 h-7 mx-auto text-[#93939f] opacity-40" />
            <p class="text-xs font-medium text-[#17171c]">Belum ada riwayat transaksi pembayaran</p>
            <p class="text-[11px] text-[#75758a]">Data metode pembayaran (QRIS, Tunai, EDC) akan muncul setelah kasir memproses pesanan.</p>
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
      </div>
    {:else}
      <!-- TAB 3: DAMPAK DISKON & PROMOSI (Stacked Bar Chart) -->
      {@const gross = timeframeData.total_revenue + timeframeData.total_discount}
      {@const discountPercent = ((timeframeData.total_discount / (gross || 1)) * 100).toFixed(1)}
      {@const netPercent = (100 - Number(discountPercent)).toFixed(1)}

      <div class="space-y-4 pt-1">
        <div>
          <span class="text-xs text-[#75758a]">Visualisasi Nilai Pesanan Kotor vs Potongan Diskon vs Kas Bersih Diterima</span>
        </div>

        <div class="p-4 rounded-2xl bg-[#fbfbfb] border border-[#d9d9dd] space-y-4 text-xs">
          <!-- Stacked Bar Visual -->
          <div class="space-y-1.5">
            <div class="w-full h-3 rounded-full bg-[#eeece7] flex overflow-hidden">
              <div
                style="width: {netPercent}%"
                class="bg-[#17171c] h-full transition-all duration-500"
                title="Kas Bersih Diterima"
              ></div>
              <div
                style="width: {discountPercent}%"
                class="bg-[#e5484d] h-full transition-all duration-500"
                title="Potongan Diskon"
              ></div>
            </div>

            <div class="flex items-center justify-between text-[10px] font-mono">
              <span class="text-[#17171c] font-medium">Kas Bersih: {netPercent}%</span>
              <span class="text-[#e5484d] font-medium">Diskon: {discountPercent}%</span>
            </div>
          </div>

          <!-- Breakdown Numbers -->
          <div class="grid grid-cols-3 gap-2 pt-2 border-t border-[#d9d9dd]/60 font-mono text-center">
            <div>
              <div class="text-[9px] text-[#75758a] uppercase">Nilai Kotor</div>
              <div class="font-medium text-[#17171c] text-xs mt-0.5">{formatCurrency(gross)}</div>
            </div>
            <div>
              <div class="text-[9px] text-[#e5484d] uppercase">Diskon Promo</div>
              <div class="font-medium text-[#e5484d] text-xs mt-0.5">-{formatCurrency(timeframeData.total_discount)}</div>
            </div>
            <div>
              <div class="text-[9px] text-[#00875a] uppercase">Kas Bersih (Net)</div>
              <div class="font-medium text-[#00875a] text-xs mt-0.5">{formatCurrency(timeframeData.total_revenue)}</div>
            </div>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>
