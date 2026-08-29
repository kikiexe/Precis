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
  <div
    class="flex flex-col gap-4 rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none sm:flex-row sm:items-center sm:justify-between sm:p-6"
  >
    <div>
      <div class="flex items-center gap-2">
        <h1 class="text-xl font-medium tracking-tight text-[#212121]">
          SaaS Performance &amp; Analytics
        </h1>
        <span
          class="rounded-full border border-[#edfce9] bg-[#edfce9] px-2 py-0.5 font-mono text-[10px] font-medium text-[#003c33]"
        >
          Live Production
        </span>
      </div>
      <p class="mt-0.5 text-xs font-normal text-[#616161]">
        Pemantauan metrik finansial bulanan (MRR/ARR), lisensi pemilik kedai, dan infrastruktur
        multi-tenant platform Précis.
      </p>
    </div>

    <div class="flex items-center gap-2.5 self-start sm:self-auto">
      <button
        type="button"
        onclick={onRefresh}
        disabled={isLoading}
        class="inline-flex cursor-pointer items-center space-x-1.5 rounded-full border border-[#d9d9dd] bg-[#eeece7]/40 px-4 py-2 text-xs font-medium text-[#212121] transition-all hover:bg-[#eeece7] disabled:opacity-50"
      >
        <RefreshCw class={`size-3.5 ${isLoading ? 'animate-spin' : ''}`} />
        <span>Segarkan Data</span>
      </button>
    </div>
  </div>

  {#if metrics}
    <!-- 4-Column Overview Stats (Template SaaS Style) -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
      <!-- 1. MRR Card -->
      <div
        class="flex flex-col justify-between rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none transition-all hover:border-[#17171c]/30"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium tracking-wider text-[#75758a] uppercase"
            >Monthly Recurring</span
          >
          <div
            class="flex size-8 items-center justify-center rounded-full bg-[#eeece7] text-[#17171c]"
          >
            <TrendingUp class="size-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="font-mono text-2xl font-bold tracking-tight text-[#212121]">
            {formatRupiah(metrics.mrr)}
          </div>
          <div class="mt-1.5 flex items-center text-[11px] font-medium text-[#003c33]">
            <span class="py-0.2 mr-1.5 rounded-md bg-[#edfce9] px-1.5 font-mono text-[10px]"
              >+100%</span
            >
            <span class="font-normal text-[#75758a]">ARR: {formatRupiah(metrics.arr)}</span>
          </div>
        </div>
      </div>

      <!-- 2. Gross Revenue Card -->
      <div
        class="flex flex-col justify-between rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none transition-all hover:border-[#17171c]/30"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium tracking-wider text-[#75758a] uppercase"
            >Total Gross Revenue</span
          >
          <div
            class="flex size-8 items-center justify-center rounded-full bg-[#edfce9] text-[#003c33]"
          >
            <DollarSign class="size-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="font-mono text-2xl font-bold tracking-tight text-[#003c33]">
            {formatRupiah(metrics.total_revenue)}
          </div>
          <div class="mt-1.5 text-[11px] text-[#75758a]">
            <span class="font-mono font-medium text-[#212121]">{metrics.invoices.paid}</span> faktur lunas
            diverifikasi
          </div>
        </div>
      </div>

      <!-- 3. Active Tenants (Owners) -->
      <div
        class="flex flex-col justify-between rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none transition-all hover:border-[#17171c]/30"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium tracking-wider text-[#75758a] uppercase"
            >Pemilik Tenant Aktif</span
          >
          <div
            class="flex size-8 items-center justify-center rounded-full bg-[#f1f5ff] text-[#1863dc]"
          >
            <Users class="size-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="font-mono text-2xl font-bold tracking-tight text-[#212121]">
            {metrics.tenants.active}
          </div>
          <div class="mt-1.5 text-[11px] text-[#75758a]">
            Dari total <span class="font-mono font-medium text-[#212121]"
              >{metrics.tenants.total}</span
            > tenant terdaftar
          </div>
        </div>
      </div>

      <!-- 4. Total Active Branches -->
      <div
        class="flex flex-col justify-between rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none transition-all hover:border-[#17171c]/30"
      >
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-medium tracking-wider text-[#75758a] uppercase"
            >Outlet &amp; Verifikasi</span
          >
          <div
            class="flex size-8 items-center justify-center rounded-full bg-[#eeece7] text-[#212121]"
          >
            <Store class="size-4" />
          </div>
        </div>
        <div class="mt-3">
          <div class="font-mono text-2xl font-bold tracking-tight text-[#212121]">
            {metrics.total_branches} <span class="text-xs font-normal text-[#75758a]">Outlet</span>
          </div>
          <div class="mt-1.5 flex items-center gap-1.5 text-[11px]">
            {#if metrics.invoices.pending > 0}
              <span
                class="py-0.2 rounded-md bg-[#ffefef] px-1.5 font-mono font-medium text-[#e5484d]"
              >
                {metrics.invoices.pending} Verifikasi
              </span>
            {:else}
              <span class="flex items-center gap-1 font-medium text-[#003c33]">
                <CheckCircle2 class="size-3 text-[#003c33]" /> Antrean Bersih
              </span>
            {/if}
          </div>
        </div>
      </div>
    </div>

    <!-- Middle Section: License Breakdown & System Infrastructure Health -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <!-- License Distribution Card (2 cols on large screen) -->
      <div
        class="flex flex-col justify-between rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none sm:p-6 lg:col-span-2"
      >
        <div>
          <div class="mb-4 flex items-center justify-between">
            <div>
              <h2 class="text-sm font-medium tracking-wider text-[#212121] uppercase">
                Distribusi Status Lisensi Tenant
              </h2>
              <p class="mt-0.5 text-xs text-[#616161]">
                Pemantauan siklus langganan SaaS pemilik akun bisnis
              </p>
            </div>
            <div class="flex items-center space-x-1.5 text-xs text-[#75758a]">
              <Users class="size-3.5" />
              <span class="font-mono font-medium text-[#212121]"
                >{metrics.tenants.total} Total Tenant</span
              >
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <!-- Active -->
            <div class="rounded-2xl border border-[#edfce9] bg-[#edfce9]/50 p-3.5">
              <div class="flex items-center justify-between text-xs font-medium text-[#003c33]">
                <span>ACTIVE</span>
                <ShieldCheck class="size-3.5" />
              </div>
              <div class="mt-1 font-mono text-xl font-bold text-[#003c33]">
                {metrics.tenants.active}
              </div>
              <div class="mt-0.5 text-[10px] text-[#75758a]">Operasional Penuh</div>
            </div>

            <!-- Grace Period -->
            <div class="rounded-2xl border border-[#d9d9dd] bg-[#eeece7]/50 p-3.5">
              <div class="flex items-center justify-between text-xs font-medium text-[#212121]">
                <span>GRACE PERIOD</span>
                <Clock class="size-3.5 text-[#ff7759]" />
              </div>
              <div class="mt-1 font-mono text-xl font-bold text-[#212121]">
                {metrics.tenants.grace_period}
              </div>
              <div class="mt-0.5 text-[10px] text-[#75758a]">Masa Tenggang 3 Hari</div>
            </div>

            <!-- Suspended -->
            <div class="rounded-2xl border border-[#ffefef] bg-[#ffefef]/50 p-3.5">
              <div class="flex items-center justify-between text-xs font-medium text-[#e5484d]">
                <span>SUSPENDED</span>
                <AlertTriangle class="size-3.5" />
              </div>
              <div class="mt-1 font-mono text-xl font-bold text-[#e5484d]">
                {metrics.tenants.suspended}
              </div>
              <div class="mt-0.5 text-[10px] text-[#75758a]">Akses Terkunci</div>
            </div>

            <!-- Trial -->
            <div class="rounded-2xl border border-[#f1f5ff] bg-[#f1f5ff]/50 p-3.5">
              <div class="flex items-center justify-between text-xs font-medium text-[#1863dc]">
                <span>TRIAL</span>
                <Clock class="size-3.5" />
              </div>
              <div class="mt-1 font-mono text-xl font-bold text-[#1863dc]">
                {metrics.tenants.trial}
              </div>
              <div class="mt-0.5 text-[10px] text-[#75758a]">Uji Coba 14 Hari</div>
            </div>
          </div>
        </div>

        <div
          class="mt-4 flex items-center justify-between border-t border-[#d9d9dd]/60 pt-3 font-mono text-[11px] text-[#75758a]"
        >
          <span
            >Sinkronisasi Terakhir: {new Date(metrics.timestamp).toLocaleTimeString('id-ID')}</span
          >
          <span class="font-medium text-[#003c33]">100% Tenant Health</span>
        </div>
      </div>

      <!-- System & Integration Status Card -->
      <div
        class="flex flex-col justify-between rounded-[22px] border border-[#d9d9dd] bg-white p-5 shadow-none sm:p-6"
      >
        <div>
          <h2 class="text-sm font-medium tracking-wider text-[#212121] uppercase">
            Infrastruktur &amp; Engine
          </h2>
          <p class="mt-0.5 text-xs text-[#616161]">Status layanan mikro dan engine backend</p>

          <div class="mt-4 space-y-3">
            <div
              class="flex items-center justify-between rounded-xl border border-[#d9d9dd]/60 bg-[#eeece7]/30 p-2.5 text-xs"
            >
              <div class="flex items-center space-x-2.5">
                <Server class="size-4 text-[#17171c]" />
                <span class="font-medium text-[#212121]">Laravel Octane + FrankenPHP</span>
              </div>
              <span
                class="rounded-full bg-[#edfce9] px-2 py-0.5 font-mono text-[10px] font-medium text-[#003c33]"
                >Running</span
              >
            </div>

            <div
              class="flex items-center justify-between rounded-xl border border-[#d9d9dd]/60 bg-[#eeece7]/30 p-2.5 text-xs"
            >
              <div class="flex items-center space-x-2.5">
                <Database class="size-4 text-[#1863dc]" />
                <span class="font-medium text-[#212121]">Multi-Tenant Database</span>
              </div>
              <span
                class="rounded-full bg-[#edfce9] px-2 py-0.5 font-mono text-[10px] font-medium text-[#003c33]"
                >Connected</span
              >
            </div>

            <div
              class="flex items-center justify-between rounded-xl border border-[#d9d9dd]/60 bg-[#eeece7]/30 p-2.5 text-xs"
            >
              <div class="flex items-center space-x-2.5">
                <Zap class="size-4 text-[#ff7759]" />
                <span class="font-medium text-[#212121]">POS Dexie.js Offline Sync</span>
              </div>
              <span
                class="rounded-full bg-[#edfce9] px-2 py-0.5 font-mono text-[10px] font-medium text-[#003c33]"
                >Active</span
              >
            </div>
          </div>
        </div>

        <div
          class="mt-4 flex items-center justify-between border-t border-[#d9d9dd]/60 pt-3 font-mono text-[11px] text-[#75758a]"
        >
          <span>Précis Engine Core</span>
          <span>v1.0.0</span>
        </div>
      </div>
    </div>
  {/if}
</div>
