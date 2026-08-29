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
  <div
    class="flex flex-col justify-between gap-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs sm:flex-row sm:items-center sm:rounded-3xl sm:p-6"
  >
    <div class="space-y-1">
      <div class="flex items-center gap-2">
        <h2 class="text-base font-bold text-[#17171c] sm:text-lg">Persetujuan Kasbon Staf</h2>
        <span
          class="rounded-full border border-[#fef3c7] bg-[#fffbeb] px-2.5 py-0.5 font-mono text-[10.5px] font-semibold text-[#d97706]"
        >
          {filteredPendingKasbons.length} Menunggu
        </span>
      </div>
      <p class="text-xs text-[#8e8e93]">
        Tinjau dan setujui permohonan pinjaman kasbon sebelum dipotong pada kalkulasi penggajian
        bulanan.
      </p>
    </div>
  </div>

  {#if filteredPendingKasbons.length === 0}
    <div
      class="space-y-3 rounded-2xl border border-[#e5e5ea] bg-white p-12 text-center shadow-2xs sm:rounded-3xl"
    >
      <div
        class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-[#f4f4f6] text-[#8e8e93]"
      >
        <CheckCircle2 class="size-6 text-[#10b981]" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#17171c]">Semua Kasbon Sudah Diproses</h3>
        <p class="mx-auto mt-1 max-w-sm text-xs text-[#8e8e93]">
          Tidak ada permohonan pinjaman kasbon baru yang memerlukan persetujuan saat ini.
        </p>
      </div>
    </div>
  {:else}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      {#each filteredPendingKasbons as kasbon}
        <div
          class="flex flex-col justify-between space-y-4 rounded-2xl border border-[#e5e5ea] bg-white p-5 shadow-2xs transition-all hover:border-[#17171c]/40 hover:shadow-xs sm:p-6"
        >
          <div class="space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[11px] font-medium tracking-wider text-[#8e8e93] uppercase"
                  >Nominal Pengajuan</span
                >
                <div class="mt-0.5 font-mono text-xl font-bold text-[#17171c] sm:text-2xl">
                  {formatRupiah(kasbon.amount)}
                </div>
              </div>
              <span
                class="rounded-full border border-[#fef3c7] bg-[#fffbeb] px-2.5 py-1 font-mono text-[10px] font-semibold text-[#d97706]"
              >
                PENDING
              </span>
            </div>

            <div class="space-y-1 text-xs text-[#686873]">
              <div class="flex items-center gap-1.5">
                <span class="text-[#8e8e93]">Pemohon:</span>
                <strong class="font-semibold text-[#17171c]"
                  >{kasbon.user?.name || kasbon.user_name || 'Staf'}</strong
                >
              </div>
              <div class="flex items-center gap-1.5 font-mono text-[11px] text-[#8e8e93]">
                <span>{kasbon.branch_name || 'Outlet Utama'}</span>
                <span>&bull;</span>
                <span>{kasbon.request_date || kasbon.created_at || 'Baru saja'}</span>
              </div>
            </div>

            {#if kasbon.purpose}
              <div
                class="rounded-xl border border-[#ececee] bg-[#f8f8fa] p-3 text-xs leading-relaxed text-[#4b5563]"
              >
                <span
                  class="mb-0.5 block font-mono text-[10.5px] font-semibold text-[#8e8e93] uppercase"
                  >Keperluan:</span
                >
                "{kasbon.purpose}"
              </div>
            {/if}
          </div>

          <div class="flex items-center justify-end gap-2.5 border-t border-[#f2f2f4] pt-3">
            <button
              type="button"
              onclick={() => onRejectKasbon(kasbon.id)}
              class="cursor-pointer rounded-xl border border-[#e5e5ea] px-4 py-2 text-xs font-semibold text-[#e5484d] transition-colors hover:border-[#fecaca] hover:bg-[#fef2f2]"
            >
              Tolak
            </button>

            <button
              type="button"
              onclick={() => onApproveKasbon(kasbon.id)}
              class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-[#17171c] px-5 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:bg-black"
            >
              <Check class="size-3.5" />
              <span>Setujui Kasbon</span>
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
