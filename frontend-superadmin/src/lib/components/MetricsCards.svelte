<script lang="ts">
  import {
    TrendingUp,
    DollarSign,
    Users,
    Store,
    ShieldCheck,
    AlertTriangle,
    Clock,
    RefreshCw,
    Server,
    Database,
    Zap,
    CheckCircle2,
  } from 'lucide-svelte';
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
  <!-- Top Bar Title & Actions -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 sm:p-6 rounded-[22px] border border-[#d9d9dd] shadow-none">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="text-xl font-medium text-[#212121] tracking-tight">SaaS Performance &amp; Analytics</h1>
        <span class="px-2 py-0.5 text-[10px] font-mono font-medium bg-[#edfce9] text-[#003c33] rounded-full border border-[#edfce9]">
          Live Production
        </span>
      </div>
      <p class="text-xs text-[#616161] mt-0.5 font-normal">
        Pemantauan metrik finansial bulanan (MRR/ARR), lisensi pemilik kedai, dan infrastruktur multi-tenant platform Précis.
      </p>
    </div>

    <div class="flex items-center gap-2.5 self-start sm:self-auto">
      <button
        type="button"
        onclick={onRefresh}
        disabled={isLoading}
        class="inline-flex items-center space-x-1.5 px-4 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7] text-[#212121] text-xs font-medium border border-[#d9d9dd] rounded-full transition-all cursor-pointer disabled:opacity-50"
      >
        <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
        <span>Segarkan Data</span>
      </button>
    </div>
  </div>

  {#if metrics}
    <!-- 4-Column Overview Stats (Template SaaS Style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      <!-- 1. MRR Card -->
      <div class="bg-white p-5 rounded-[22px] border border-[#d9d9dd] flex flex-col justify-between shadow-none hover:border-[#17171c]/30 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium uppercase tracking-wider text-[#75758a]">Monthly Recurring</span>
          <div class="w-8 h-8 rounded-full bg-[#eeece7] text-[#17171c] flex items-center justify-center">
            <TrendingUp class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl font-bold font-mono text-[#212121] tracking-tight">{formatRupiah(metrics.mrr)}</div>
          <div class="mt-1.5 flex items-center text-[11px] text-[#003c33] font-medium">
            <span class="px-1.5 py-0.2 bg-[#edfce9] rounded-md font-mono text-[10px] mr-1.5">+100%</span>
            <span class="text-[#75758a] font-normal">ARR: {formatRupiah(metrics.arr)}</span>
          </div>
        </div>
      </div>

      <!-- 2. Gross Revenue Card -->
      <div class="bg-white p-5 rounded-[22px] border border-[#d9d9dd] flex flex-col justify-between shadow-none hover:border-[#17171c]/30 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium uppercase tracking-wider text-[#75758a]">Total Gross Revenue</span>
          <div class="w-8 h-8 rounded-full bg-[#edfce9] text-[#003c33] flex items-center justify-center">
            <DollarSign class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl font-bold font-mono text-[#003c33] tracking-tight">{formatRupiah(metrics.total_revenue)}</div>
          <div class="mt-1.5 text-[11px] text-[#75758a]">
            <span class="font-mono font-medium text-[#212121]">{metrics.invoices.paid}</span> faktur lunas diverifikasi
          </div>
        </div>
      </div>

      <!-- 3. Active Tenants (Owners) -->
      <div class="bg-white p-5 rounded-[22px] border border-[#d9d9dd] flex flex-col justify-between shadow-none hover:border-[#17171c]/30 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium uppercase tracking-wider text-[#75758a]">Pemilik Tenant Aktif</span>
          <div class="w-8 h-8 rounded-full bg-[#f1f5ff] text-[#1863dc] flex items-center justify-center">
            <Users class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl font-bold font-mono text-[#212121] tracking-tight">{metrics.tenants.active}</div>
          <div class="mt-1.5 text-[11px] text-[#75758a]">
            Dari total <span class="font-mono font-medium text-[#212121]">{metrics.tenants.total}</span> tenant terdaftar
          </div>
        </div>
      </div>

      <!-- 4. Total Active Branches -->
      <div class="bg-white p-5 rounded-[22px] border border-[#d9d9dd] flex flex-col justify-between shadow-none hover:border-[#17171c]/30 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium uppercase tracking-wider text-[#75758a]">Outlet &amp; Verifikasi</span>
          <div class="w-8 h-8 rounded-full bg-[#eeece7] text-[#212121] flex items-center justify-center">
            <Store class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="text-2xl font-bold font-mono text-[#212121] tracking-tight">{metrics.total_branches} <span class="text-xs font-normal text-[#75758a]">Outlet</span></div>
          <div class="mt-1.5 flex items-center gap-1.5 text-[11px]">
            {#if metrics.invoices.pending > 0}
              <span class="px-1.5 py-0.2 bg-[#ffefef] text-[#e5484d] font-mono font-medium rounded-md">
                {metrics.invoices.pending} Verifikasi
              </span>
            {:else}
              <span class="text-[#003c33] font-medium flex items-center gap-1">
                <CheckCircle2 class="w-3 h-3 text-[#003c33]" /> Antrean Bersih
              </span>
            {/if}
          </div>
        </div>
      </div>
    </div>

    <!-- Middle Section: License Breakdown & System Infrastructure Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- License Distribution Card (2 cols on large screen) -->
      <div class="lg:col-span-2 bg-white border border-[#d9d9dd] rounded-[22px] p-5 sm:p-6 shadow-none flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-medium uppercase tracking-wider text-[#212121]">Distribusi Status Lisensi Tenant</h2>
              <p class="text-xs text-[#616161] mt-0.5">Pemantauan siklus langganan SaaS pemilik akun bisnis</p>
            </div>
            <div class="flex items-center space-x-1.5 text-xs text-[#75758a]">
              <Users class="w-3.5 h-3.5" />
              <span class="font-mono font-medium text-[#212121]">{metrics.tenants.total} Total Tenant</span>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Active -->
            <div class="p-3.5 bg-[#edfce9]/50 rounded-2xl border border-[#edfce9]">
              <div class="flex items-center justify-between text-xs font-medium text-[#003c33]">
                <span>ACTIVE</span>
                <ShieldCheck class="w-3.5 h-3.5" />
              </div>
              <div class="text-xl font-bold font-mono text-[#003c33] mt-1">{metrics.tenants.active}</div>
              <div class="text-[10px] text-[#75758a] mt-0.5">Operasional Penuh</div>
            </div>

            <!-- Grace Period -->
            <div class="p-3.5 bg-[#eeece7]/50 rounded-2xl border border-[#d9d9dd]">
              <div class="flex items-center justify-between text-xs font-medium text-[#212121]">
                <span>GRACE PERIOD</span>
                <Clock class="w-3.5 h-3.5 text-[#ff7759]" />
              </div>
              <div class="text-xl font-bold font-mono text-[#212121] mt-1">{metrics.tenants.grace_period}</div>
              <div class="text-[10px] text-[#75758a] mt-0.5">Masa Tenggang 3 Hari</div>
            </div>

            <!-- Suspended -->
            <div class="p-3.5 bg-[#ffefef]/50 rounded-2xl border border-[#ffefef]">
              <div class="flex items-center justify-between text-xs font-medium text-[#e5484d]">
                <span>SUSPENDED</span>
                <AlertTriangle class="w-3.5 h-3.5" />
              </div>
              <div class="text-xl font-bold font-mono text-[#e5484d] mt-1">{metrics.tenants.suspended}</div>
              <div class="text-[10px] text-[#75758a] mt-0.5">Akses Terkunci</div>
            </div>

            <!-- Trial -->
            <div class="p-3.5 bg-[#f1f5ff]/50 rounded-2xl border border-[#f1f5ff]">
              <div class="flex items-center justify-between text-xs font-medium text-[#1863dc]">
                <span>TRIAL</span>
                <Clock class="w-3.5 h-3.5" />
              </div>
              <div class="text-xl font-bold font-mono text-[#1863dc] mt-1">{metrics.tenants.trial}</div>
              <div class="text-[10px] text-[#75758a] mt-0.5">Uji Coba 14 Hari</div>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-[#d9d9dd]/60 flex items-center justify-between text-[11px] text-[#75758a] font-mono">
          <span>Sinkronisasi Terakhir: {new Date(metrics.timestamp).toLocaleTimeString('id-ID')}</span>
          <span class="text-[#003c33] font-medium">100% Tenant Health</span>
        </div>
      </div>

      <!-- System & Integration Status Card -->
      <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-5 sm:p-6 shadow-none flex flex-col justify-between">
        <div>
          <h2 class="text-sm font-medium uppercase tracking-wider text-[#212121]">Infrastruktur &amp; Engine</h2>
          <p class="text-xs text-[#616161] mt-0.5">Status layanan mikro dan engine backend</p>

          <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between p-2.5 bg-[#eeece7]/30 rounded-xl border border-[#d9d9dd]/60 text-xs">
              <div class="flex items-center space-x-2.5">
                <Server class="w-4 h-4 text-[#17171c]" />
                <span class="font-medium text-[#212121]">Laravel Octane + FrankenPHP</span>
              </div>
              <span class="px-2 py-0.5 bg-[#edfce9] text-[#003c33] font-mono text-[10px] rounded-full font-medium">Running</span>
            </div>

            <div class="flex items-center justify-between p-2.5 bg-[#eeece7]/30 rounded-xl border border-[#d9d9dd]/60 text-xs">
              <div class="flex items-center space-x-2.5">
                <Database class="w-4 h-4 text-[#1863dc]" />
                <span class="font-medium text-[#212121]">Multi-Tenant Database</span>
              </div>
              <span class="px-2 py-0.5 bg-[#edfce9] text-[#003c33] font-mono text-[10px] rounded-full font-medium">Connected</span>
            </div>

            <div class="flex items-center justify-between p-2.5 bg-[#eeece7]/30 rounded-xl border border-[#d9d9dd]/60 text-xs">
              <div class="flex items-center space-x-2.5">
                <Zap class="w-4 h-4 text-[#ff7759]" />
                <span class="font-medium text-[#212121]">POS Dexie.js Offline Sync</span>
              </div>
              <span class="px-2 py-0.5 bg-[#edfce9] text-[#003c33] font-mono text-[10px] rounded-full font-medium">Active</span>
            </div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-[#d9d9dd]/60 text-[11px] text-[#75758a] font-mono flex items-center justify-between">
          <span>Précis Engine Core</span>
          <span>v1.0.0</span>
        </div>
      </div>
    </div>
  {/if}
</div>
