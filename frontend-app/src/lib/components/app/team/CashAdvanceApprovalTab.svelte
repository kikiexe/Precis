<script lang="ts">
  import { Check, CheckCircle2 } from 'lucide-svelte';
  import type { CashAdvance } from '../../../types/app';
  import { formatRupiah } from '@precis/shared-utils';

  interface Props {
    pendingKasbons: CashAdvance[];
    selectedBranchFilter?: string;
    onApproveKasbon: (kasbonId: string) => void;
    onRejectKasbon: (kasbonId: string) => void;
  }

  let {
    pendingKasbons = [],
    selectedBranchFilter = 'ALL',
    onApproveKasbon,
    onRejectKasbon,
  }: Props = $props();

  let filteredPendingKasbons = $derived(
    pendingKasbons.filter((k) => {
      if (selectedBranchFilter === 'ALL') return true;
      return (
        k.branch_id === selectedBranchFilter ||
        (k.branch_name && k.branch_name.toLowerCase().includes(selectedBranchFilter.toLowerCase()))
      );
    })
  );
</script>

<div class="space-y-6 font-sans">
  <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
    <div class="space-y-1">
      <div class="flex items-center gap-2">
        <h2 class="text-base sm:text-lg font-bold text-[#17171c]">Persetujuan Kasbon Staf</h2>
        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-mono font-semibold bg-[#fffbeb] text-[#d97706] border border-[#fef3c7]">
          {filteredPendingKasbons.length} Menunggu
        </span>
      </div>
      <p class="text-xs text-[#8e8e93]">
        Tinjau dan setujui permohonan pinjaman kasbon sebelum dipotong pada kalkulasi penggajian bulanan.
      </p>
    </div>
  </div>

  {#if filteredPendingKasbons.length === 0}
    <div class="bg-white border border-[#e5e5ea] rounded-2xl sm:rounded-3xl p-12 text-center space-y-3 shadow-2xs">
      <div class="w-12 h-12 rounded-2xl bg-[#f4f4f6] text-[#8e8e93] flex items-center justify-center mx-auto">
        <CheckCircle2 class="w-6 h-6 text-[#10b981]" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Semua Kasbon Sudah Diproses</h3>
        <p class="text-xs text-[#8e8e93] mt-1 max-w-sm mx-auto">
          Tidak ada permohonan pinjaman kasbon baru yang memerlukan persetujuan saat ini.
        </p>
      </div>
    </div>
  {:else}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      {#each filteredPendingKasbons as kasbon}
        <div class="bg-white border border-[#e5e5ea] hover:border-[#17171c]/40 rounded-2xl p-5 sm:p-6 flex flex-col justify-between space-y-4 shadow-2xs hover:shadow-xs transition-all">
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[11px] font-medium uppercase text-[#8e8e93] tracking-wider">Nominal Pengajuan</span>
                <div class="text-xl sm:text-2xl font-bold font-mono text-[#17171c] mt-0.5">
                  {formatRupiah(kasbon.amount)}
                </div>
              </div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-[#fffbeb] text-[#d97706] border border-[#fef3c7] font-semibold">
                PENDING
              </span>
            </div>

            <div class="space-y-1 text-xs text-[#686873]">
              <div class="flex items-center gap-1.5">
                <span class="text-[#8e8e93]">Pemohon:</span>
                <strong class="text-[#17171c] font-semibold">{kasbon.user?.name || kasbon.user_name || 'Staf'}</strong>
              </div>
              <div class="flex items-center gap-1.5 text-[11px] text-[#8e8e93] font-mono">
                <span>{kasbon.branch_name || 'Outlet Utama'}</span>
                <span>&bull;</span>
                <span>{kasbon.request_date || kasbon.created_at || 'Baru saja'}</span>
              </div>
            </div>

            {#if kasbon.purpose}
              <div class="text-xs text-[#4b5563] bg-[#f8f8fa] p-3 rounded-xl border border-[#ececee] leading-relaxed">
                <span class="text-[#8e8e93] font-semibold block text-[10.5px] uppercase font-mono mb-0.5">Keperluan:</span>
                "{kasbon.purpose}"
              </div>
            {/if}
          </div>

          <div class="pt-3 border-t border-[#f2f2f4] flex items-center justify-end gap-2.5">
            <button
              type="button"
              onclick={() => onRejectKasbon(kasbon.id)}
              class="px-4 py-2 text-xs font-semibold text-[#e5484d] hover:bg-[#fef2f2] rounded-xl transition-colors cursor-pointer border border-[#e5e5ea] hover:border-[#fecaca]"
            >
              Tolak
            </button>

            <button
              type="button"
              onclick={() => onApproveKasbon(kasbon.id)}
              class="px-5 py-2 bg-[#17171c] hover:bg-black text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 cursor-pointer transition-all shadow-xs"
            >
              <Check class="w-3.5 h-3.5" />
              <span>Setujui Kasbon</span>
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
