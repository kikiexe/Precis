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
    onNavigate: (
      domain: 'dashboard' | 'katalog' | 'tim' | 'finance' | 'settings',
      subTab?: string
    ) => void;
  }

  let { currentUser, branches = [], selectedBranchId = 'ALL', onNavigate }: Props = $props();

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

<div class="space-y-6 pb-8 font-sans">
  <!-- Top Operational Banner -->
  <div
    class="flex flex-col gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:justify-between sm:rounded-3xl sm:p-6"
  >
    <div class="min-w-0 space-y-1">
      <div class="flex items-center gap-2">
        <span
          class="shrink-0 rounded-full bg-[#f4f4f6] px-2.5 py-0.5 font-mono text-[10px] font-semibold text-[#17171c] uppercase"
        >
          Operasional
        </span>
        <span class="truncate text-xs font-medium text-[#8e8e93]">
          {currentUser.branch_name || (branches.length > 0 ? branches[0].name : 'Outlet Utama')}
        </span>
      </div>
      <div class="mt-1 flex items-center gap-2">
        <h1 class="truncate text-lg font-bold tracking-tight text-[#17171c] sm:text-2xl">
          Ringkasan Penjualan &amp; Bisnis
        </h1>
        <span class="h-2 w-2 shrink-0 rounded-full bg-[#10b981] motion-safe:animate-pulse"></span>
      </div>
    </div>

    <div class="flex shrink-0 items-center gap-2">
      <button
        type="button"
        onclick={() => onNavigate('katalog', 'menu')}
        class="flex cursor-pointer items-center justify-center gap-1.5 rounded-full bg-[#17171c] px-4 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black {focusRing}"
      >
        <Package class="h-4 w-4" />
        <span>Menu</span>
      </button>

      <button
        type="button"
        onclick={() => onNavigate('tim', 'presensi')}
        class="flex cursor-pointer items-center justify-center gap-1.5 rounded-full border border-[#e5e5ea] bg-white px-4 py-2 text-xs font-semibold text-[#17171c] shadow-2xs transition-all hover:bg-[#f8f8fa] {focusRing}"
      >
        <ShieldCheck class="h-4 w-4" />
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
  <div
    class="space-y-4 rounded-2xl border border-[#27272a] bg-[#17171c] p-5 text-white shadow-sm sm:rounded-3xl sm:p-7"
  >
    <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-4">
      <div class="flex min-w-0 items-center gap-3">
        <div
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white"
        >
          <Sparkles class="h-4 w-4 text-[#34d399]" />
        </div>
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-white sm:text-base">
            Analisis &amp; Rekomendasi Penjualan
          </h3>
          <p class="truncate text-xs text-[#a1a1aa]">
            Insight performa bisnis &amp; tren transaksi
          </p>
        </div>
      </div>

      <span
        class="shrink-0 rounded-full bg-white/10 px-3 py-1 font-mono text-[10px] font-semibold text-[#34d399]"
      >
        Insight Pintar
      </span>
    </div>

    <div class="space-y-2.5 text-xs">
      <div class="text-sm font-bold text-white">
        {businessInsights.title}
      </div>
      <p class="text-xs leading-relaxed text-white/80">
        {businessInsights.summary}
      </p>
      <ul
        class="list-inside list-disc space-y-1.5 border-t border-white/10 pt-2 text-xs leading-relaxed text-white/70"
      >
        {#each businessInsights.recommendations as item}
          <li>{item}</li>
        {/each}
      </ul>
    </div>
  </div>

  <!-- Deep Dive Analytics Card -->
  <div
    class="space-y-5 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:rounded-3xl sm:p-7"
  >
    <div class="flex w-full items-center gap-1.5 rounded-2xl bg-[#f4f4f6] p-1.5">
      <button
        type="button"
        title="Komposisi Menu"
        aria-label="Komposisi Menu"
        aria-pressed={activeInsightTab === 'mix'}
        onclick={() => (activeInsightTab = 'mix')}
        class={`flex cursor-pointer items-center justify-center gap-2 rounded-xl py-2 text-xs font-semibold transition-all ${focusRing} ${
          activeInsightTab === 'mix'
            ? 'flex-1 bg-white px-3 text-[#17171c] shadow-xs'
            : 'px-3 text-[#686873] hover:text-[#17171c]'
        }`}
      >
        <PieChart class="h-4 w-4 shrink-0" />
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
        class={`flex cursor-pointer items-center justify-center gap-2 rounded-xl py-2 text-xs font-semibold transition-all ${focusRing} ${
          activeInsightTab === 'payments'
            ? 'flex-1 bg-white px-3 text-[#17171c] shadow-xs'
            : 'px-3 text-[#686873] hover:text-[#17171c]'
        }`}
      >
        <CreditCard class="h-4 w-4 shrink-0" />
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
        class={`flex cursor-pointer items-center justify-center gap-2 rounded-xl py-2 text-xs font-semibold transition-all ${focusRing} ${
          activeInsightTab === 'discount'
            ? 'flex-1 bg-white px-3 text-[#17171c] shadow-xs'
            : 'px-3 text-[#686873] hover:text-[#17171c]'
        }`}
      >
        <Percent class="h-4 w-4 shrink-0" />
        {#if activeInsightTab === 'discount'}
          <span class="whitespace-nowrap">Dampak Diskon</span>
        {/if}
      </button>
    </div>

    {#if activeInsightTab === 'mix'}
      <div class="space-y-4 pt-1">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-xs text-[#8e8e93]">Peringkat volume &amp; omzet produk</span>
          <div class="flex items-center rounded-full bg-[#f4f4f6] p-1 text-xs font-semibold">
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'menu')}
              class={`cursor-pointer rounded-full px-3 py-1 transition-all ${focusRing} ${
                activeMixSubTab === 'menu' ? 'bg-[#17171c] text-white shadow-2xs' : 'text-[#686873]'
              }`}
            >
              Top 10 Menu
            </button>
            <button
              type="button"
              onclick={() => (activeMixSubTab = 'kategori')}
              class={`cursor-pointer rounded-full px-3 py-1 transition-all ${focusRing} ${
                activeMixSubTab === 'kategori'
                  ? 'bg-[#17171c] text-white shadow-2xs'
                  : 'text-[#686873]'
              }`}
            >
              Top 5 Kategori
            </button>
          </div>
        </div>

        {#if timeframeData.top_products.length === 0}
          <div class="space-y-2 py-12 text-center text-[#8e8e93]">
            <Package class="mx-auto h-8 w-8 opacity-40" />
            <p class="text-xs font-semibold text-[#17171c]">Belum ada data produk terjual</p>
            <p class="px-4 text-[11px] text-[#8e8e93]">
              Data penjualan produk akan terakumulasi otomatis saat kasir memproses pesanan.
            </p>
          </div>
        {:else if activeMixSubTab === 'menu'}
          <div class="space-y-3.5">
            {#each timeframeData.top_products as prod, idx}
              <div class="space-y-1.5">
                <div class="flex items-center justify-between gap-2 text-xs">
                  <div class="flex min-w-0 items-center gap-2.5">
                    <span
                      class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-[#e5e5ea] bg-[#f4f4f6] font-mono text-[10px] font-bold text-[#17171c]"
                    >
                      {idx + 1}
                    </span>
                    <span class="truncate font-bold text-[#17171c]">{prod.name}</span>
                  </div>
                  <div class="flex shrink-0 items-center gap-2.5 font-mono">
                    <span class="text-xs text-[#8e8e93]"
                      >{prod.quantity.toLocaleString('id-ID')}x</span
                    >
                    <span class="font-bold text-[#17171c]">{formatRupiah(prod.total_amount)}</span>
                  </div>
                </div>

                <div class="h-2 w-full overflow-hidden rounded-full bg-[#f4f4f6]">
                  <div
                    style="width: {prod.share_percent}%"
                    class="h-full rounded-full bg-[#17171c] transition-all duration-500"
                  ></div>
                </div>
              </div>
            {/each}
          </div>
        {:else}
          <div class="space-y-4">
            <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-[#f4f4f6]">
              {#each timeframeData.category_breakdown as cat, i}
                <div
                  style="width: {cat.share_percent}%"
                  class={`h-full transition-all duration-500 ${
                    i === 0
                      ? 'bg-[#17171c]'
                      : i === 1
                        ? 'bg-[#059669]'
                        : i === 2
                          ? 'bg-[#2563eb]'
                          : 'bg-[#8e8e93]'
                  }`}
                  title={`${cat.name}: ${cat.share_percent}%`}
                ></div>
              {/each}
            </div>

            <div class="space-y-2.5 pt-2">
              {#each timeframeData.category_breakdown as cat}
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="truncate font-bold text-[#17171c]">{cat.name}</span>
                    <div class="flex shrink-0 items-center gap-2.5 font-mono">
                      <span class="text-xs text-[#8e8e93]">{cat.share_percent}%</span>
                      <span class="font-bold text-[#17171c]">{formatRupiah(cat.total_amount)}</span>
                    </div>
                  </div>

                  <div class="h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f6]">
                    <div
                      style="width: {cat.share_percent}%"
                      class="h-full rounded-full bg-[#2563eb] transition-all duration-500"
                    ></div>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/if}

        <div class="border-t border-[#f2f2f4] pt-3 text-right">
          <button
            type="button"
            onclick={() => onNavigate('katalog', 'menu')}
            class="inline-flex cursor-pointer items-center gap-1 py-1 text-xs font-semibold text-[#2563eb] hover:underline {focusRing}"
          >
            <span>Buka Katalog Menu Lengkap</span>
            <ChevronRight class="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
    {:else if activeInsightTab === 'payments'}
      <div class="space-y-4 pt-1">
        {#if timeframeData.payment_methods.length === 0}
          <div class="space-y-2 py-12 text-center text-[#8e8e93]">
            <CreditCard class="mx-auto h-8 w-8 opacity-40" />
            <p class="text-xs font-semibold text-[#17171c]">Belum ada riwayat pembayaran</p>
            <p class="px-4 text-[11px] text-[#8e8e93]">
              Metode pembayaran (QRIS, Tunai, EDC) akan otomatis terdata.
            </p>
          </div>
        {:else}
          <div class="space-y-2">
            <div class="flex h-3 w-full overflow-hidden rounded-full bg-[#f4f4f6]">
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

            <div class="flex items-center justify-between font-mono text-xs text-[#8e8e93]">
              <span
                >Cashless ({timeframeData.payment_methods[0].percent +
                  (timeframeData.payment_methods[2]?.percent || 0)}%)</span
              >
              <span>Tunai ({timeframeData.payment_methods[1]?.percent || 0}%)</span>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            {#each timeframeData.payment_methods as method, i}
              <div
                class="flex items-center justify-between gap-3 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-4 text-xs shadow-2xs"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
                      i === 0
                        ? 'bg-[#17171c] text-white'
                        : i === 1
                          ? 'bg-[#ecfdf5] text-[#059669]'
                          : 'bg-[#eff6ff] text-[#2563eb]'
                    }`}
                  >
                    {#if i === 0}
                      <QrCode class="h-4 w-4" />
                    {:else if i === 1}
                      <Banknote class="h-4 w-4" />
                    {:else}
                      <CreditCard class="h-4 w-4" />
                    {/if}
                  </div>
                  <div class="truncate">
                    <div class="truncate font-bold text-[#17171c]">{method.method}</div>
                    <div class="font-mono text-[11px] text-[#8e8e93]">
                      {method.count.toLocaleString('id-ID')} transaksi
                    </div>
                  </div>
                </div>

                <div class="shrink-0 text-right font-mono">
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
        <div
          class="space-y-5 rounded-2xl border border-[#e5e5ea] bg-[#fafafc] p-5 text-xs shadow-2xs"
        >
          <div class="space-y-2">
            <div class="flex h-3 w-full overflow-hidden rounded-full bg-[#f4f4f6]">
              <div
                style="width: {netPercent}%"
                class="h-full bg-[#17171c] transition-all duration-500"
                title="Kas Bersih Diterima"
              ></div>
              <div
                style="width: {discountPercent}%"
                class="h-full bg-[#e5484d] transition-all duration-500"
                title="Potongan Diskon"
              ></div>
            </div>

            <div class="flex items-center justify-between font-mono text-xs">
              <span class="font-bold text-[#17171c]">Kas Bersih: {netPercent}%</span>
              <span class="font-bold text-[#e5484d]">Diskon: {discountPercent}%</span>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3 border-t border-[#e5e5ea] pt-3 text-center font-mono">
            <div>
              <span class="font-sans text-[10px] font-semibold text-[#8e8e93] uppercase"
                >Nilai Kotor</span
              >
              <div class="mt-1 text-sm font-bold text-[#17171c]">{formatRupiah(gross)}</div>
            </div>
            <div>
              <span class="font-sans text-[10px] font-semibold text-[#e5484d] uppercase"
                >Diskon Promo</span
              >
              <div class="mt-1 text-sm font-bold text-[#e5484d]">
                -{formatRupiah(timeframeData.total_discount)}
              </div>
            </div>
            <div>
              <span class="font-sans text-[10px] font-semibold text-[#059669] uppercase"
                >Kas Bersih (Net)</span
              >
              <div class="mt-1 text-sm font-bold text-[#059669]">
                {formatRupiah(timeframeData.total_revenue)}
              </div>
            </div>
          </div>
        </div>
      </div>
    {/if}
  </div>
</div>
