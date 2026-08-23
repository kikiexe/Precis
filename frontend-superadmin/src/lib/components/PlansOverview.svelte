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

<div class="space-y-6">
  <!-- Top Bar Title -->
  <div class="bg-white p-4 border border-[#e0e0e0] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-[#161616] tracking-tight">Master Paket Langganan SaaS</h1>
      <p class="text-xs text-[#525252] mt-0.5">
        Konfigurasi tier paket bisnis, batasan kuota outlet workspace, dan struktur tarif bulanan/tahunan.
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

  <!-- Plans Grid -->
  {#if isLoading}
    <div class="bg-white p-12 border border-[#e0e0e0] text-center text-[#525252] text-xs">
      <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-[#0f62fe]" />
      <span>Memuat data paket langganan...</span>
    </div>
  {:else if plans.length === 0}
    <div class="bg-white p-12 border border-[#e0e0e0] text-center text-[#8c8c8c] text-xs">
      <Layers class="w-6 h-6 mx-auto mb-2 text-[#c6c6c6]" />
      <span>Belum ada paket langganan yang dikonfigurasi.</span>
    </div>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      {#each plans as plan (plan.id)}
        <div class="bg-white border border-[#e0e0e0] flex flex-col relative overflow-hidden">
          {#if plan.max_workspaces > 1 && plan.max_workspaces < 10}
            <div class="bg-[#0f62fe] text-white text-[10px] uppercase font-bold py-1 text-center tracking-wider font-mono">
              Paling Populer (Growth)
            </div>
          {/if}

          <div class="p-6 flex-1 flex flex-col justify-between space-y-6">
            <div>
              <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-[#161616]">{plan.name}</h2>
                {#if plan.is_active}
                  <span class="px-2 py-0.5 bg-[#defbe6] text-[#24a148] text-[10px] font-mono font-bold border border-[#6fdc8c]">
                    ACTIVE
                  </span>
                {:else}
                  <span class="px-2 py-0.5 bg-[#f4f4f4] text-[#525252] text-[10px] font-mono font-bold">
                    INACTIVE
                  </span>
                {/if}
              </div>

              <!-- Price Box -->
              <div class="mt-4 pt-4 border-t border-[#f4f4f4]">
                <div class="text-2xl font-bold font-mono text-[#161616]">
                  {formatRupiah(plan.monthly_price)}
                  <span class="text-xs font-normal text-[#525252]">/bln</span>
                </div>
                <div class="text-xs text-[#525252] mt-1 font-mono">
                  Tahunan: <span class="font-semibold text-[#161616]">{formatRupiah(plan.annual_price)}</span>
                </div>
              </div>

              <!-- Features List -->
              <div class="mt-6 space-y-2.5 text-xs text-[#525252]">
                <div class="flex items-center space-x-2 text-[#161616] font-medium">
                  <Store class="w-4 h-4 text-[#0f62fe] shrink-0" />
                  <span>Maksimal <strong>{plan.max_workspaces} Outlet</strong> Cabang</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#24a148] shrink-0" />
                  <span>Presensi Selfie WebP & GPS Watermark</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#24a148] shrink-0" />
                  <span>Feed Audit Wall of Faces</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#24a148] shrink-0" />
                  <span>POS Kiosk Offline-First (Dexie.js)</span>
                </div>
                <div class="flex items-center space-x-2">
                  <Check class="w-3.5 h-3.5 text-[#24a148] shrink-0" />
                  <span>Kalkulasi Payroll & Ekspor Transfer Bank</span>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-[#f4f4f4] text-[11px] text-[#8c8c8c] font-mono">
              ID: {plan.id}
            </div>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
