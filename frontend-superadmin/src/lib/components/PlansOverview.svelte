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
  <div
    class="flex flex-col gap-4 rounded-[22px] border border-[#d9d9dd] bg-white p-6 shadow-none sm:flex-row sm:items-center sm:justify-between"
  >
    <div>
      <h1 class="text-xl font-medium tracking-tight text-[#212121]">Master Paket Langganan SaaS</h1>
      <p class="mt-0.5 text-xs font-normal text-[#616161]">
        Konfigurasi tier paket bisnis, batasan kuota outlet workspace, dan struktur tarif
        bulanan/tahunan.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex cursor-pointer items-center space-x-1.5 self-start rounded-full border border-[#d9d9dd] bg-[#eeece7]/40 px-4 py-2 text-xs font-medium text-[#212121] transition-all hover:bg-[#eeece7] disabled:opacity-50 sm:self-auto"
    >
      <RefreshCw class={`h-3.5 w-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  <!-- Plans Grid -->
  {#if isLoading}
    <div
      class="rounded-[22px] border border-[#d9d9dd] bg-white p-12 text-center text-xs text-[#75758a] shadow-none"
    >
      <RefreshCw class="mx-auto mb-2 h-6 w-6 animate-spin text-[#1863dc]" />
      <span>Memuat data paket langganan...</span>
    </div>
  {:else if plans.length === 0}
    <div
      class="rounded-[22px] border border-[#d9d9dd] bg-white p-12 text-center text-xs text-[#93939f] shadow-none"
    >
      <Layers class="mx-auto mb-2 h-6 w-6 text-[#93939f] opacity-50" />
      <span>Belum ada paket langganan yang dikonfigurasi.</span>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
      {#each plans as plan (plan.id)}
        <div
          class="relative flex flex-col overflow-hidden rounded-[22px] border border-[#d9d9dd] bg-white shadow-none"
        >
          {#if plan.max_workspaces > 1 && plan.max_workspaces < 10}
            <div
              class="bg-[#17171c] py-1.5 text-center font-mono text-[10px] font-medium tracking-wider text-white uppercase"
            >
              Paling Populer (Growth)
            </div>
          {/if}

          <div class="flex flex-1 flex-col justify-between space-y-6 p-6">
            <div>
              <div class="flex items-center justify-between">
                <h2 class="text-base font-medium text-[#212121]">{plan.name}</h2>
                {#if plan.is_active}
                  <span
                    class="rounded-full bg-[#edfce9] px-2.5 py-0.5 font-mono text-[10px] font-medium text-[#003c33]"
                  >
                    ACTIVE
                  </span>
                {:else}
                  <span
                    class="rounded-full bg-[#eeece7] px-2.5 py-0.5 font-mono text-[10px] font-medium text-[#616161]"
                  >
                    INACTIVE
                  </span>
                {/if}
              </div>

              <!-- Price Box -->
              <div class="mt-4 border-t border-[#d9d9dd]/60 pt-4">
                <div class="font-mono text-2xl font-medium text-[#212121]">
                  {formatRupiah(plan.monthly_price)}
                  <span class="text-xs font-normal text-[#75758a]">/bln</span>
                </div>
                <div class="mt-1 font-mono text-xs text-[#75758a]">
                  Tahunan: <span class="font-medium text-[#212121]"
                    >{formatRupiah(plan.annual_price)}</span
                  >
                </div>
              </div>

              <!-- Features List -->
              <div class="mt-6 space-y-2.5 text-xs text-[#616161]">
                <div class="flex items-center space-x-2 font-medium text-[#212121]">
                  <Store class="h-4 w-4 shrink-0 text-[#1863dc]" />
                  <span
                    >Maksimal <strong class="font-medium">{plan.max_workspaces} Outlet</strong> Cabang</span
                  >
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="h-3.5 w-3.5 shrink-0 text-[#003c33]" />
                  <span>Presensi Selfie WebP &amp; GPS Watermark</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="h-3.5 w-3.5 shrink-0 text-[#003c33]" />
                  <span>Feed Audit Wall of Faces</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="h-3.5 w-3.5 shrink-0 text-[#003c33]" />
                  <span>POS Kiosk Offline-First (Dexie.js)</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="h-3.5 w-3.5 shrink-0 text-[#003c33]" />
                  <span>Kalkulasi Payroll &amp; Ekspor Transfer Bank</span>
                </div>
              </div>
            </div>

            <div class="border-t border-[#d9d9dd]/60 pt-4 font-mono text-[11px] text-[#93939f]">
              ID: {plan.id}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
