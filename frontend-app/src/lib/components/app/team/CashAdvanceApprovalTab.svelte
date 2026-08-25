<script lang="ts">
  import { Wallet, ChevronDown, Check } from 'lucide-svelte';
  import type { CashAdvance } from '../../../types/app';
  import { formatRupiah } from '../../../utils/formatters';

  interface Props {
    pendingKasbons: CashAdvance[];
    availableBranches: string[];
    selectedBranchFilter: string;
    onSelectBranchFilter: (branch: string) => void;
    onApproveKasbon: (kasbonId: string) => void;
    onRejectKasbon: (kasbonId: string) => void;
  }

  let {
    pendingKasbons = [],
    availableBranches = [],
    selectedBranchFilter = 'ALL',
    onSelectBranchFilter,
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

<div class="space-y-4 font-sans">
  <div class="flex items-center justify-between">
    <h3 class="text-xs font-bold uppercase tracking-wider text-[#17171c]">
      Permohonan Kasbon Pending ({filteredPendingKasbons.length})
    </h3>

    {#if availableBranches.length > 0}
      <div class="relative shrink-0 max-w-[170px] sm:max-w-xs">
        <select
          value={selectedBranchFilter}
          onchange={(e) => onSelectBranchFilter(e.currentTarget.value)}
          class="appearance-none px-3 pr-7 py-1.5 bg-[#eeece7]/50 hover:bg-[#eeece7] border border-[#d9d9dd] rounded-full text-xs text-[#212121] font-mono focus:outline-hidden cursor-pointer transition-all shadow-2xs truncate"
        >
          <option value="ALL">Semua Cabang</option>
          {#each availableBranches as branch}
            <option value={branch}>{branch}</option>
          {/each}
        </select>
        <ChevronDown class="w-3.5 h-3.5 text-[#75758a] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
      </div>
    {/if}
  </div>

  {#if filteredPendingKasbons.length === 0}
    <div class="bg-white border border-[#d9d9dd] rounded-3xl p-12 text-center text-[#93939f] space-y-2">
      <Wallet class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
      <p class="text-xs font-medium text-[#17171c]">Tidak ada permohonan kasbon pending</p>
      <p class="text-[11px] text-[#75758a]">Permohonan pinjaman kasbon dari staf untuk cabang ini akan muncul di sini.</p>
    </div>
  {:else}
    <div class="space-y-3">
      {#each filteredPendingKasbons as kasbon}
        <div class="bg-white border border-[#d9d9dd] rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-bold text-sm text-[#17171c] font-mono">{formatRupiah(kasbon.amount)}</span>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-[#fff8e6] text-[#b45309] border border-[#fef3c7]">PENDING</span>
            </div>
            <div class="text-xs text-[#616161]">
              Pengajuan oleh <strong>{kasbon.user_name || 'Staf'}</strong> &bull; {kasbon.request_date} &bull; {kasbon.branch_name || 'Cabang'}
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              onclick={() => onApproveKasbon(kasbon.id)}
              class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-full flex items-center gap-1.5 cursor-pointer shadow-xs"
            >
              <Check class="w-3.5 h-3.5" />
              <span>Setujui</span>
            </button>
            <button
              type="button"
              onclick={() => onRejectKasbon(kasbon.id)}
              class="px-4 py-2 bg-transparent hover:bg-[#ffefef] text-[#e5484d] text-xs font-medium rounded-full cursor-pointer"
            >
              Tolak
            </button>
          </div>
        </div>
      {/each}
    </div>
  {/if}
</div>
