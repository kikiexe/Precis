<script lang="ts">
  import {
    CreditCard,
    QrCode,
    Banknote,
    Package,
    ShieldCheck,
    PieChart,
    ChevronRight,
    Sparkles,
    Percent,
  } from 'lucide-svelte';
  import type { TimeframePeriod, User, BranchItem } from '../../types/app';
  import { inventoryService } from '../../services/inventory-service';
  import { formatRupiah } from '@precis/shared-utils';
  import SalesLineChart from './dashboard/SalesLineChart.svelte';

  interface Props {
    currentUser: User;
    branches?: BranchItem[];
    selectedBranchId?: string;
    onNavigate: (domain: 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings', subTab?: string) => void;
  }

  let {
    currentUser,
    branches = [],
    selectedBranchId = 'ALL',
    onNavigate,
  }: Props = $props();

  let selectedTimeframe = $state<TimeframePeriod>('month');
  let activeInsightTab = $state<'mix' | 'payments' | 'discount'>('mix');
  let activeMixSubTab = $state<'menu' | 'kategori'>('menu');

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
        summary: `Pendapatan bersih tercatat ${formatRupiah(timeframeData.total_revenue)} dari ${timeframeData.total_orders.toLocaleString('id-ID')} transaksi pesanan.`,
        recommendations: [
          `Rata-rata nilai belanja per pelanggan berada di angka ${formatRupiah(timeframeData.average_order_value)} per transaksi.`,
          `Menu "${topProd}" menyumbang kontribusi terbesar (${topProdShare}% total volume pesanan).`,
          `Total potongan diskon & promosi terhitung sebesar ${formatRupiah(timeframeData.total_discount)} (${discountRatio}% dari nilai kotor).`,
        ],
      };
    } else {
      return {
        isPositive: false,
        title: `Penjualan ${timeframeData.period_label} Tercatat ${formatRupiah(timeframeData.total_revenue)} (${growth}% ${timeframeData.growth_label})`,
        summary: `Total ${timeframeData.total_orders.toLocaleString('id-ID')} pesanan berhasil diproses dengan rata-rata tiket ${formatRupiah(timeframeData.average_order_value)}.`,
        recommendations: [
          `Volume transaksi di jam-jam tertentu mengalami penurunan dibanding periode lalu.`,
          `Menu "${topProd}" tetap stabil sebagai produk terfavorit pelanggan (${topProdShare}% porsi).`,
          `Saran: Tingkatkan penjualan dengan paket bundling menu atau diskon khusus di jam sepi.`,
        ],
      };
    }
  });

  const focusRing =
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#17171c]/50 focus-visible:ring-offset-2 focus-visible:ring-offset-white';
</script>

<div class="space-y-6 font-sans pb-8">
  <!-- Top Operational Banner -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-2xs">
    <div class="min-w-0 space-y-1">
      <div class="flex items-center gap-2">
        <span class="text-[10px] font-mono uppercase px-2.5 py-0.5 rounded-full bg-[#f4f4f6] text-[#17171c] font-semibold shrink-0">
          Operasional
        </span>
        <span class="text-xs text-[#8e8e93] font-medium truncate">
          {currentUser.branch_name || (branches.length > 0 ? branches[0].name : 'Outlet Utama')}
        </span>
      </div>
      <div class="flex items-center gap-2 mt-1">
        <h1 class="text-lg sm:text-2xl font-bold text-[#17171c] tracking-tight truncate">
          Ringkasan Penjualan &amp; Bisnis
        </h1>
        <span class="w-2 h-2 rounded-full bg-[#10b981] motion-safe:animate-pulse shrink-0"></span>
      </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <button
        type="button"
        onclick={() => onNavigate('katalog', 'menu')}
        class="px-4 py-2 text-xs font-semibold bg-[#17171c] hover:bg-black text-white rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-xs {focusRing}"
      >
        <Package class="w-4 h-4" />
        <span>Menu</span>
      </button>

      <button
        type="button"
        onclick={() => onNavigate('tim', 'presensi')}
        class="px-4 py-2 text-xs font-semibold bg-white hover:bg-[#f8f8fa] border border-[#e5e5ea] text-[#17171c] rounded-full transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-2xs {focusRing}"
      >
        <ShieldCheck class="w-4 h-4" />
        <span>Presensi</span>
      </button>
    </div>
  </div>

  <!-- Unified Liveline Sales Trend -->
  <SalesLineChart
    breakdown={timeframeData.breakdown}
    totalRevenue={timeframeData.total_revenue}
    totalDiscount={timeframeData.total_discount}
    totalOrders={timeframeData.total_orders}
    averageOrderValue={timeframeData.average_order_value}
    growthPercent={timeframeData.growth_percent}
    growthLabel={timeframeData.growth_label}
    periodLabel={timeframeData.period_label}
    {selectedTimeframe}
    onSelectTimeframe={(tf) => (selectedTimeframe = tf)}
    {isLoading}
  />

  <!-- Analisis & Rekomendasi Bisnis -->
  <div class="bg-[#17171c] text-white border border-[#27272a] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-4 shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-4">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-8 h-8 rounded-xl bg-white/10 text-white flex items-center justify-center shrink-0">
          <Sparkles class="w-4 h-4 text-[#34d399]" />
        </div>
        <div class="min-w-0">
          <h3 class="text-sm sm:text-base font-bold text-white truncate">Analisis &amp; Rekomendasi Penjualan</h3>
          <p class="text-xs text-[#a1a1aa] truncate">Insight performa bisnis &amp; tren transaksi</p>
        </div>
      </div>

      <span class="text-[10px] font-mono px-3 py-1 rounded-full bg-white/10 text-[#34d399] font-semibold shrink-0">
        Insight Pintar
      </span>
    </div>

    <div class="space-y-2.5 text-xs">
      <div class="font-bold text-white text-sm">
        {businessInsights.title}
      </div>
      <p class="text-white/80 text-xs leading-relaxed">
        {businessInsights.summary}
      </p>
      <ul class="space-y-1.5 text-white/70 text-xs list-disc list-inside leading-relaxed pt-2 border-t border-white/10">
        {#each businessInsights.recommendations as item}
          <li>{item}</li>
        {/each}
      </ul>
    </div>
  </div>

  <!-- Deep Dive Analytics Card -->
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xs">
    <div class="flex items-center gap-1.5 bg-[#f4f4f6] p-1.5 rounded-2xl w-full">
      <button
        type="button"
        title="Komposisi Menu"
        aria-label="Komposisi Menu"
        aria-pressed={activeInsightTab === 'mix'}
        onclick={() => (activeInsightTab = 'mix')}
        class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2 ${focusRing} ${
          activeInsightTab === 'mix'
            ? 'flex-1 bg-white text-[#17171c] shadow-xs px-3'
            : 'text-[#686873] hover:text-[#17171c] px-3'
        }`}
      >
        <PieChart class="w-4 h-4 shrink-0" />
        {#if activeInsightTab === 'mix'}
          <span class="whitespace-nowrap">Komposisi Menu</span>
        {/if}
      </button>

      <button
        type="button"
        title="Saluran Pembayaran"
        aria-label="Saluran Pembayaran"
        aria-pressed={activeInsightTab === 'payments'}
        onclick={() => (activeInsightTab = 'payments')}
        class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2 ${focusRing} ${
          activeInsightTab === 'payments'
            ? 'flex-1 bg-white text-[#17171c] shadow-xs px-3'
            : 'text-[#686873] hover:text-[#17171c] px-3'
        }`}
      >
        <CreditCard class="w-4 h-4 shrink-0" />
        {#if activeInsightTab === 'payments'}
          <span class="whitespace-nowrap">Saluran Pembayaran</span>
        {/if}
      </button>

      <button
        type="button"
        title="Dampak Diskon"
        aria-label="Dampak Diskon"
        aria-pressed={activeInsightTab === 'discount'}
        onclick={() => (activeInsightTab = 'discount')}
        class={`py-2 text-xs font-semibold rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2 ${focusRing} ${
          activeInsightTab === 'discount'
            ? 'flex-1 bg-white text-[#17171c] shadow-xs px-3'
            : 'text-[#686873] hover:text-[#17171c] px-3'
        }`}
      >
        <Percent class="w-4 h-4 shrink-0" />
        {#if activeInsightTab === 'discount'}
          <span class="whitespace-nowrap">Dampak Diskon</span>
        {/if}
      </button>
    </div>

    {#if activeInsightTab === 'mix'}
      <div class="space-y-4 pt-1">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-xs text-[#8e8e93]">Peringkat volume &amp; omzet produk</span>
          <div class="flex items-center bg-[#f4f4f6] p-1 rounded-full text-xs font-semibold">
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'menu')}
              class={`px-3 py-1 rounded-full transition-all cursor-pointer ${focusRing} ${
                activeMixSubTab === 'menu' ? 'bg-[#17171c] text-white shadow-2xs' : 'text-[#686873]'
              }`}
            >
              Top 10 Menu
            </button>
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'kategori')}
              class={`px-3 py-1 rounded-full transition-all cursor-pointer ${focusRing} ${
                activeMixSubTab === 'kategori' ? 'bg-[#17171c] text-white shadow-2xs' : 'text-[#686873]'
              }`}
            >
              Top 5 Kategori
            </button>
          </div>
        </div>

        {#if timeframeData.top_products.length === 0}
          <div class="py-12 text-center text-[#8e8e93] space-y-2">
            <Package class="w-8 h-8 mx-auto opacity-40" />
            <p class="text-xs font-semibold text-[#17171c]">Belum ada data produk terjual</p>
            <p class="text-[11px] text-[#8e8e93] px-4">Data penjualan produk akan terakumulasi otomatis saat kasir memproses pesanan.</p>
          </div>
        {:else if activeMixSubTab === 'menu'}
          <div class="space-y-3.5">
            {#each timeframeData.top_products as prod, idx}
              <div class="space-y-1.5">
                <div class="flex items-center justify-between gap-2 text-xs">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-5 h-5 rounded-full bg-[#f4f4f6] text-[#17171c] font-mono text-[10px] font-bold flex items-center justify-center shrink-0 border border-[#e5e5ea]">
                      {idx + 1}
                    </span>
                    <span class="font-bold text-[#17171c] truncate">{prod.name}</span>
                  </div>
                  <div class="flex items-center gap-2.5 font-mono shrink-0">
                    <span class="text-[#8e8e93] text-xs">{prod.quantity.toLocaleString('id-ID')}x</span>
                    <span class="font-bold text-[#17171c]">{formatRupiah(prod.total_amount)}</span>
                  </div>
                </div>

                <div class="w-full bg-[#f4f4f6] h-2 rounded-full overflow-hidden">
                  <div
                    style="width: {prod.share_percent}%"
                    class="bg-[#17171c] h-full rounded-full transition-all duration-500"
                  ></div>
                </div>
              </div>
            {/each}
          </div>
        {:else}
          <div class="space-y-4">
            <div class="w-full h-2.5 rounded-full bg-[#f4f4f6] flex overflow-hidden">
              {#each timeframeData.category_breakdown as cat, i}
                <div
                  style="width: {cat.share_percent}%"
                  class={`h-full transition-all duration-500 ${
                    i === 0 ? 'bg-[#17171c]' : i === 1 ? 'bg-[#059669]' : i === 2 ? 'bg-[#2563eb]' : 'bg-[#8e8e93]'
                  }`}
                  title={`${cat.name}: ${cat.share_percent}%`}
                ></div>
              {/each}
            </div>

            <div class="space-y-2.5 pt-2">
              {#each timeframeData.category_breakdown as cat}
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="font-bold text-[#17171c] truncate">{cat.name}</span>
                    <div class="flex items-center gap-2.5 font-mono shrink-0">
                      <span class="text-[#8e8e93] text-xs">{cat.share_percent}%</span>
                      <span class="font-bold text-[#17171c]">{formatRupiah(cat.total_amount)}</span>
                    </div>
                  </div>

                  <div class="w-full bg-[#f4f4f6] h-1.5 rounded-full overflow-hidden">
                    <div
                      style="width: {cat.share_percent}%"
                      class="bg-[#2563eb] h-full rounded-full transition-all duration-500"
                    ></div>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/if}

        <div class="pt-3 border-t border-[#f2f2f4] text-right">
          <button
            type="button"
            onclick={() => onNavigate('katalog', 'menu')}
            class="text-xs font-semibold text-[#2563eb] hover:underline cursor-pointer inline-flex items-center gap-1 py-1 {focusRing}"
          >
            <span>Buka Katalog Menu Lengkap</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    {:else if activeInsightTab === 'payments'}
      <div class="space-y-4 pt-1">
        {#if timeframeData.payment_methods.length === 0}
          <div class="py-12 text-center text-[#8e8e93] space-y-2">
            <CreditCard class="w-8 h-8 mx-auto opacity-40" />
            <p class="text-xs font-semibold text-[#17171c]">Belum ada riwayat pembayaran</p>
            <p class="text-[11px] text-[#8e8e93] px-4">Metode pembayaran (QRIS, Tunai, EDC) akan otomatis terdata.</p>
          </div>
        {:else}
          <div class="space-y-2">
            <div class="w-full h-3 rounded-full bg-[#f4f4f6] flex overflow-hidden">
              {#each timeframeData.payment_methods as method, i}
                <div
                  style="width: {method.percent}%"
                  class={`h-full transition-all duration-500 ${
                    i === 0 ? 'bg-[#17171c]' : i === 1 ? 'bg-[#059669]' : 'bg-[#2563eb]'
                  }`}
                  title={`${method.method}: ${method.percent}%`}
                ></div>
              {/each}
            </div>

            <div class="flex items-center justify-between text-xs font-mono text-[#8e8e93]">
              <span>Cashless ({timeframeData.payment_methods[0].percent + (timeframeData.payment_methods[2]?.percent || 0)}%)</span>
              <span>Tunai ({timeframeData.payment_methods[1]?.percent || 0}%)</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {#each timeframeData.payment_methods as method, i}
              <div class="p-4 rounded-2xl bg-[#fafafc] border border-[#e5e5ea] flex items-center justify-between gap-3 text-xs shadow-2xs">
                <div class="flex items-center gap-3 min-w-0">
                  <div class={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${
                    i === 0 ? 'bg-[#17171c] text-white' : i === 1 ? 'bg-[#ecfdf5] text-[#059669]' : 'bg-[#eff6ff] text-[#2563eb]'
                  }`}>
                    {#if i === 0}
                      <QrCode class="w-4 h-4" />
                    {:else if i === 1}
                      <Banknote class="w-4 h-4" />
                    {:else}
                      <CreditCard class="w-4 h-4" />
                    {/if}
                  </div>
                  <div class="truncate">
                    <div class="font-bold text-[#17171c] truncate">{method.method}</div>
                    <div class="text-[11px] text-[#8e8e93] font-mono">{method.count.toLocaleString('id-ID')} transaksi</div>
                  </div>
                </div>

                <div class="text-right font-mono shrink-0">
                  <div class="font-bold text-[#17171c]">{formatRupiah(method.amount)}</div>
                  <div class="text-[10.5px] text-[#8e8e93]">{method.percent}% porsi</div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {:else}
      {@const gross = timeframeData.total_revenue + timeframeData.total_discount}
      {@const discountPercent = ((timeframeData.total_discount / (gross || 1)) * 100).toFixed(1)}
      {@const netPercent = (100 - Number(discountPercent)).toFixed(1)}

      <div class="space-y-4 pt-1">
        <div class="p-5 rounded-2xl bg-[#fafafc] border border-[#e5e5ea] space-y-5 text-xs shadow-2xs">
          <div class="space-y-2">
            <div class="w-full h-3 rounded-full bg-[#f4f4f6] flex overflow-hidden">
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

            <div class="flex items-center justify-between text-xs font-mono">
              <span class="text-[#17171c] font-bold">Kas Bersih: {netPercent}%</span>
              <span class="text-[#e5484d] font-bold">Diskon: {discountPercent}%</span>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3 pt-3 border-t border-[#e5e5ea] font-mono text-center">
            <div>
              <span class="text-[10px] text-[#8e8e93] uppercase font-sans font-semibold">Nilai Kotor</span>
              <div class="font-bold text-[#17171c] text-sm mt-1">{formatRupiah(gross)}</div>
            </div>
            <div>
              <span class="text-[10px] text-[#e5484d] uppercase font-sans font-semibold">Diskon Promo</span>
              <div class="font-bold text-[#e5484d] text-sm mt-1">-{formatRupiah(timeframeData.total_discount)}</div>
            </div>
            <div>
              <span class="text-[10px] text-[#059669] uppercase font-sans font-semibold">Kas Bersih (Net)</span>
              <div class="font-bold text-[#059669] text-sm mt-1">{formatRupiah(timeframeData.total_revenue)}</div>
            </div>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>