<script lang="ts">
  import { TrendingUp, Users, Store, DollarSign, AlertTriangle, ShieldCheck, Clock, RefreshCw } from 'lucide-svelte';
  import type { SaaSMetrics } from '../types/superadmin';

  interface Props {
    metrics: SaaSMetrics | null;
    isLoading: boolean;
    onRefresh: () => void;
  }

  let { metrics, isLoading, onRefresh }: Props = $props();

  function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(amount);
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Top Bar Title & Refresh -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-[22px] border border-[#d9d9dd] shadow-none">
    <div>
      <h1 class="text-xl font-medium text-[#212121] tracking-tight">Dashboard Metrik Global SaaS</h1>
      <p class="text-xs text-[#616161] mt-0.5 font-normal">
        Pemantauan agregasi finansial, lisensi tenant multi-cabang, dan performa platform Précis secara realtime.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center space-x-1.5 px-4 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7] text-[#212121] text-xs font-medium border border-[#d9d9dd] rounded-full transition-all cursor-pointer disabled:opacity-50 self-start sm:self-auto"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  {#if metrics}
    <!-- Financial Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <!-- MRR Card -->
      <div class="bg-white p-6 rounded-[22px] border border-[#d9d9dd] relative overflow-hidden shadow-none">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium uppercase tracking-wider text-[#75758a]">Monthly Recurring Revenue (MRR)</span>
          <div class="w-8 h-8 rounded-full bg-[#eeece7] text-[#17171c] flex items-center justify-center">
            <TrendingUp class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-4">
          <div class="text-2xl font-medium font-mono text-[#212121]">{formatRupiah(metrics.mrr)}</div>
          <div class="mt-1 text-xs text-[#616161] flex items-center space-x-1">
            <span class="text-[#1863dc] font-medium font-mono">ARR:</span>
            <span class="font-mono">{formatRupiah(metrics.arr)}</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-[#d9d9dd]/60 text-[11px] text-[#93939f]">
          Dihitung dari total {metrics.tenants.active} tenant berstatus aktif
        </div>
      </div>

      <!-- Gross Revenue Card -->
      <div class="bg-white p-6 rounded-[22px] border border-[#d9d9dd] shadow-none">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium uppercase tracking-wider text-[#75758a]">Total Terkumpul (Gross Revenue)</span>
          <div class="w-8 h-8 rounded-full bg-[#edfce9] text-[#003c33] flex items-center justify-center">
            <DollarSign class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-4">
          <div class="text-2xl font-medium font-mono text-[#003c33]">{formatRupiah(metrics.total_revenue)}</div>
          <div class="mt-1 text-xs text-[#616161]">
            Total dari seluruh faktur lunas diverifikasi
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-[#d9d9dd]/60 text-[11px] text-[#93939f]">
          {metrics.invoices.paid} faktur terverifikasi
        </div>
      </div>

      <!-- Outlets & Invoices Status -->
      <div class="bg-white p-6 rounded-[22px] border border-[#d9d9dd] shadow-none">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium uppercase tracking-wider text-[#75758a]">Infrastruktur &amp; Verifikasi</span>
          <div class="w-8 h-8 rounded-full bg-[#eeece7] text-[#212121] flex items-center justify-center">
            <Store class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
          <div>
            <div class="text-xs text-[#75758a]">Total Cabang/Outlet</div>
            <div class="text-xl font-medium font-mono text-[#212121] mt-0.5">{metrics.total_branches}</div>
          </div>
          <div>
            <div class="text-xs text-[#75758a]">Antrean Verifikasi</div>
            <div class={`text-xl font-medium font-mono mt-0.5 ${metrics.invoices.pending > 0 ? 'text-[#b30000]' : 'text-[#212121]'}`}>
              {metrics.invoices.pending}
            </div>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-[#d9d9dd]/60 text-[11px] text-[#93939f] flex justify-between">
          <span>{metrics.invoices.unpaid} Belum Bayar</span>
          <span>{metrics.invoices.paid} Terverifikasi</span>
        </div>
      </div>
    </div>

    <!-- Tenant Breakdown Section -->
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 shadow-none">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-sm font-medium uppercase tracking-wider text-[#212121]">Distribusi Lisensi Tenant</h2>
          <p class="text-xs text-[#616161] mt-0.5">Komposisi total {metrics.tenants.total} pemilik akun bisnis terdaftar</p>
        </div>
        <div class="flex items-center space-x-1.5 text-xs text-[#75758a]">
          <Users class="w-3.5 h-3.5" />
          <span class="font-mono font-medium text-[#212121]">{metrics.tenants.total} Total Tenant</span>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <!-- Active -->
        <div class="p-4 bg-[#edfce9]/40 rounded-[16px] border border-[#edfce9]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-[#003c33]">ACTIVE</span>
            <ShieldCheck class="w-3.5 h-3.5 text-[#003c33]" />
          </div>
          <div class="text-xl font-medium font-mono text-[#003c33] mt-1">{metrics.tenants.active}</div>
          <div class="text-[11px] text-[#616161] mt-0.5">Operasional Penuh</div>
        </div>

        <!-- Grace Period -->
        <div class="p-4 bg-[#eeece7]/50 rounded-[16px] border border-[#d9d9dd]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-[#212121]">GRACE PERIOD</span>
            <Clock class="w-3.5 h-3.5 text-[#ff7759]" />
          </div>
          <div class="text-xl font-medium font-mono text-[#212121] mt-1">{metrics.tenants.grace_period}</div>
          <div class="text-[11px] text-[#75758a] mt-0.5">Masa Tenggang 3-5 Hari</div>
        </div>

        <!-- Suspended -->
        <div class="p-4 bg-[#ffad9b]/15 rounded-[16px] border border-[#ffad9b]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-[#b30000]">SUSPENDED</span>
            <AlertTriangle class="w-3.5 h-3.5 text-[#b30000]" />
          </div>
          <div class="text-xl font-medium font-mono text-[#b30000] mt-1">{metrics.tenants.suspended}</div>
          <div class="text-[11px] text-[#b30000] mt-0.5">Akses Terkunci</div>
        </div>

        <!-- Trial -->
        <div class="p-4 bg-[#f1f5ff] rounded-[16px] border border-[#f1f5ff]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-[#1863dc]">TRIAL</span>
            <Users class="w-3.5 h-3.5 text-[#1863dc]" />
          </div>
          <div class="text-xl font-medium font-mono text-[#1863dc] mt-1">{metrics.tenants.trial}</div>
          <div class="text-[11px] text-[#75758a] mt-0.5">Uji Coba Gratis</div>
        </div>
      </div>
    </div>
  {:else if isLoading}
    <div class="bg-white p-12 border border-[#d9d9dd] rounded-[22px] text-center text-[#75758a] text-xs shadow-none">
      <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-[#1863dc]" />
      <span>Memuat data metrik SaaS...</span>
    </div>
  {/if}
</div>
