<script lang="ts">
  import { Layers, Check, Store, RefreshCw } from 'lucide-svelte';
  import type { SubscriptionPlanRecord } from '../types/superadmin';

  interface Props {
    plans: SubscriptionPlanRecord[];
    isLoading: boolean;
    onRefresh: () => void;
  }

  let { plans, isLoading, onRefresh }: Props = $props();

  function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(amount);
  }
</script>

<div class="space-y-6 font-sans">
  <!-- Top Bar Title -->
  <div class="bg-white p-6 rounded-[22px] border border-[#d9d9dd] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-none">
    <div>
      <h1 class="text-xl font-medium text-[#212121] tracking-tight">Master Paket Langganan SaaS</h1>
      <p class="text-xs text-[#616161] mt-0.5 font-normal">
        Konfigurasi tier paket bisnis, batasan kuota outlet workspace, dan struktur tarif bulanan/tahunan.
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

  <!-- Plans Grid -->
  {#if isLoading}
    <div class="bg-white p-12 border border-[#d9d9dd] rounded-[22px] text-center text-[#75758a] text-xs shadow-none">
      <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-[#1863dc]" />
      <span>Memuat data paket langganan...</span>
    </div>
  {:else if plans.length === 0}
    <div class="bg-white p-12 border border-[#d9d9dd] rounded-[22px] text-center text-[#93939f] text-xs shadow-none">
      <Layers class="w-6 h-6 mx-auto mb-2 text-[#93939f] opacity-50" />
      <span>Belum ada paket langganan yang dikonfigurasi.</span>
    </div>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {#each plans as plan (plan.id)}
        <div class="bg-white border border-[#d9d9dd] rounded-[22px] flex flex-col relative overflow-hidden shadow-none">
          {#if plan.max_workspaces > 1 && plan.max_workspaces < 10}
            <div class="bg-[#17171c] text-white text-[10px] uppercase font-medium py-1.5 text-center tracking-wider font-mono">
              Paling Populer (Growth)
            </div>
          {/if}

          <div class="p-6 flex-1 flex flex-col justify-between space-y-6">
            <div>
              <div class="flex items-center justify-between">
                <h2 class="text-base font-medium text-[#212121]">{plan.name}</h2>
                {#if plan.is_active}
                  <span class="px-2.5 py-0.5 bg-[#edfce9] text-[#003c33] text-[10px] font-mono font-medium rounded-full">
                    ACTIVE
                  </span>
                {:else}
                  <span class="px-2.5 py-0.5 bg-[#eeece7] text-[#616161] text-[10px] font-mono font-medium rounded-full">
                    INACTIVE
                  </span>
                {/if}
              </div>

              <!-- Price Box -->
              <div class="mt-4 pt-4 border-t border-[#d9d9dd]/60">
                <div class="text-2xl font-medium font-mono text-[#212121]">
                  {formatRupiah(plan.monthly_price)}
                  <span class="text-xs font-normal text-[#75758a]">/bln</span>
                </div>
                <div class="text-xs text-[#75758a] mt-1 font-mono">
                  Tahunan: <span class="font-medium text-[#212121]">{formatRupiah(plan.annual_price)}</span>
                </div>
              </div>

              <!-- Features List -->
              <div class="mt-6 space-y-2.5 text-xs text-[#616161]">
                <div class="flex items-center space-x-2 text-[#212121] font-medium">
                  <Store class="w-4 h-4 text-[#1863dc] shrink-0" />
                  <span>Maksimal <strong class="font-medium">{plan.max_workspaces} Outlet</strong> Cabang</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#003c33] shrink-0" />
                  <span>Presensi Selfie WebP &amp; GPS Watermark</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#003c33] shrink-0" />
                  <span>Feed Audit Wall of Faces</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#003c33] shrink-0" />
                  <span>POS Kiosk Offline-First (Dexie.js)</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#003c33] shrink-0" />
                  <span>Kalkulasi Payroll &amp; Ekspor Transfer Bank</span>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-[#d9d9dd]/60 text-[11px] text-[#93939f] font-mono">
              ID: {plan.id}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
