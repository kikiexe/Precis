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

<div class="space-y-6">
  <!-- Top Bar Title & Refresh -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-4 border border-[#e0e0e0]">
    <div>
      <h1 class="text-xl font-bold text-[#161616] tracking-tight">Dashboard Metrik Global SaaS</h1>
      <p class="text-xs text-[#525252] mt-0.5">
        Pemantauan agregasi finansial, lisensi tenant multi-cabang, dan performa platform Précis secara realtime.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#f4f4f4] hover:bg-[#e0e0e0] text-[#161616] text-xs font-medium border border-[#e0e0e0] transition-colors disabled:opacity-50"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  {#if metrics}
    <!-- Financial Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- MRR Card -->
      <div class="bg-white p-5 border border-[#e0e0e0] relative overflow-hidden">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-[#525252]">Monthly Recurring Revenue (MRR)</span>
          <div class="w-8 h-8 bg-[#edf5ff] text-[#0f62fe] flex items-center justify-center">
            <TrendingUp class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold font-mono text-[#161616]">{formatRupiah(metrics.mrr)}</div>
          <div class="mt-1 text-xs text-[#525252] flex items-center space-x-1">
            <span class="text-[#0f62fe] font-medium font-mono">ARR:</span>
            <span class="font-mono">{formatRupiah(metrics.arr)}</span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-[#f4f4f4] text-[11px] text-[#8c8c8c]">
          Dihitung dari total {metrics.tenants.active} tenant berstatus aktif
        </div>
      </div>

      <!-- Gross Revenue Card -->
      <div class="bg-white p-5 border border-[#e0e0e0]">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-[#525252]">Total Terkumpul (Gross Revenue)</span>
          <div class="w-8 h-8 bg-[#defbe6] text-[#24a148] flex items-center justify-center">
            <DollarSign class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-4">
          <div class="text-2xl font-bold font-mono text-[#161616]">{formatRupiah(metrics.total_revenue)}</div>
          <div class="mt-1 text-xs text-[#525252]">
            Total dari seluruh faktur lunas diverifikasi
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-[#f4f4f4] text-[11px] text-[#8c8c8c]">
          {metrics.invoices.paid} faktur terverifikasi
        </div>
      </div>

      <!-- Outlets & Invoices Status -->
      <div class="bg-white p-5 border border-[#e0e0e0]">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold uppercase tracking-wider text-[#525252]">Infrastruktur & Verifikasi</span>
          <div class="w-8 h-8 bg-[#f4f4f4] text-[#161616] flex items-center justify-center">
            <Store class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
          <div>
            <div class="text-xs text-[#525252]">Total Cabang/Outlet</div>
            <div class="text-xl font-bold font-mono text-[#161616] mt-0.5">{metrics.total_branches}</div>
          </div>
          <div>
            <div class="text-xs text-[#525252]">Antrean Verifikasi</div>
            <div class={`text-xl font-bold font-mono mt-0.5 ${metrics.invoices.pending > 0 ? 'text-[#da1e28]' : 'text-[#161616]'}`}>
              {metrics.invoices.pending}
            </div>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-[#f4f4f4] text-[11px] text-[#8c8c8c] flex justify-between">
          <span>{metrics.invoices.unpaid} Belum Bayar</span>
          <span>{metrics.invoices.paid} Terverifikasi</span>
        </div>
      </div>
    </div>

    <!-- Tenant Breakdown Section -->
    <div class="bg-white border border-[#e0e0e0] p-5">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-sm font-bold uppercase tracking-wider text-[#161616]">Distribusi Lisensi Tenant</h2>
          <p class="text-xs text-[#525252] mt-0.5">Komposisi total {metrics.tenants.total} pemilik akun bisnis terdaftar</p>
        </div>
        <div class="flex items-center space-x-1 text-xs text-[#525252]">
          <Users class="w-3.5 h-3.5" />
          <span class="font-mono font-medium">{metrics.tenants.total} Total Tenant</span>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Active -->
        <div class="p-3 bg-[#f4f4f4] border-l-4 border-[#24a148]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-[#161616]">ACTIVE</span>
            <ShieldCheck class="w-3.5 h-3.5 text-[#24a148]" />
          </div>
          <div class="text-xl font-bold font-mono text-[#161616] mt-1">{metrics.tenants.active}</div>
          <div class="text-[11px] text-[#525252] mt-0.5">Operasional Penuh</div>
        </div>

        <!-- Grace Period -->
        <div class="p-3 bg-[#f4f4f4] border-l-4 border-[#f1c21b]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-[#161616]">GRACE PERIOD</span>
            <Clock class="w-3.5 h-3.5 text-[#b28600]" />
          </div>
          <div class="text-xl font-bold font-mono text-[#161616] mt-1">{metrics.tenants.grace_period}</div>
          <div class="text-[11px] text-[#525252] mt-0.5">Masa Tenggang 3-5 Hari</div>
        </div>

        <!-- Suspended -->
        <div class="p-3 bg-[#f4f4f4] border-l-4 border-[#da1e28]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-[#161616]">SUSPENDED</span>
            <AlertTriangle class="w-3.5 h-3.5 text-[#da1e28]" />
          </div>
          <div class="text-xl font-bold font-mono text-[#161616] mt-1">{metrics.tenants.suspended}</div>
          <div class="text-[11px] text-[#525252] mt-0.5">Akses Terkunci</div>
        </div>

        <!-- Trial -->
        <div class="p-3 bg-[#f4f4f4] border-l-4 border-[#0f62fe]">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-[#161616]">TRIAL</span>
            <Users class="w-3.5 h-3.5 text-[#0f62fe]" />
          </div>
          <div class="text-xl font-bold font-mono text-[#161616] mt-1">{metrics.tenants.trial}</div>
          <div class="text-[11px] text-[#525252] mt-0.5">Uji Coba Gratis</div>
        </div>
      </div>
    </div>
  {:else if isLoading}
    <div class="bg-white p-12 border border-[#e0e0e0] text-center text-[#525252] text-xs">
      <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-[#0f62fe]" />
      <span>Memuat data metrik SaaS...</span>
    </div>
  {/if}
</div>
