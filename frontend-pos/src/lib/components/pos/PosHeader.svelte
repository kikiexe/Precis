<script lang="ts">
  import { Wifi, WifiOff, RefreshCw, Lock, Store, Clock, DollarSign } from 'lucide-svelte';
  import type { PosSession, CashierUser } from '../../types/pos';

  interface Props {
    branchName: string;
    isOnline: boolean;
    isSyncing?: boolean;
    pendingSyncCount: number;
    activeCashier: CashierUser | null;
    activeSession: PosSession | null;
    onOpenPinModal: () => void;
    onOpenSessionModal: () => void;
    onOpenMasterLockModal: () => void;
    onSyncNow: () => void;
  }

  let {
    branchName = 'Outlet Sleman #01',
    isOnline = true,
    isSyncing = false,
    pendingSyncCount = 0,
    activeCashier,
    activeSession,
    onOpenPinModal,
    onOpenSessionModal,
    onOpenMasterLockModal,
    onSyncNow,
  }: Props = $props();

  let currentTime = $state('');

  $effect(() => {
    const updateTime = () => {
      currentTime = new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);
    return () => clearInterval(interval);
  });
</script>

<header class="bg-[#161616] text-white border-b border-[#262626] px-4 py-2.5 flex items-center justify-between select-none shrink-0 h-14">
  <!-- merek dan nama cabang -->
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 bg-[#0f62fe] text-white flex items-center justify-center font-bold text-base font-display">
        P
      </div>
      <div>
        <div class="text-sm font-bold tracking-tight text-white leading-none">PRÉCIS POS</div>
        <div class="flex items-center gap-1 text-[11px] text-[#8c8c8c] font-mono mt-0.5">
          <Store class="w-3 h-3 text-[#0f62fe]" />
          <span>{branchName}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- jam live wib dan status sesi kasir -->
  <div class="hidden md:flex items-center gap-4">
    <!-- jam live wib -->
    <div class="flex items-center gap-1.5 font-mono text-xs text-[#c6c6c6] bg-[#262626] px-3 py-1.5 border border-[#393939]">
      <Clock class="w-3.5 h-3.5 text-[#0f62fe]" />
      <span>{currentTime} WIB</span>
    </div>

    <!-- badge sesi kasir aktif -->
    <button
      type="button"
      onclick={onOpenSessionModal}
      class={`flex items-center gap-1.5 text-xs font-mono px-3 py-1.5 border transition-colors cursor-pointer ${
        activeSession && activeSession.status === 'OPEN'
          ? 'bg-[#24a148]/10 text-[#42be65] border-[#24a148]/30 hover:bg-[#24a148]/20'
          : 'bg-[#da1e28]/10 text-[#ff8389] border-[#da1e28]/30 hover:bg-[#da1e28]/20'
      }`}
    >
      <DollarSign class="w-3.5 h-3.5" />
      <span>{activeSession && activeSession.status === 'OPEN' ? 'Kasir Buka' : 'Kasir Tutup'}</span>
    </button>
  </div>

  <!-- status koneksi internet, sinkronisasi, dan pin kasir -->
  <div class="flex items-center gap-2 sm:gap-3">
    <!-- indikator online atau offline -->
    <div
      class={`flex items-center gap-1.5 text-xs font-mono px-2.5 py-1.5 border ${
        isOnline
          ? 'bg-[#24a148]/10 text-[#42be65] border-[#24a148]/30'
          : 'bg-[#f1c21b]/10 text-[#f1c21b] border-[#f1c21b]/30 animate-pulse'
      }`}
    >
      {#if isOnline}
        <Wifi class="w-3.5 h-3.5" />
        <span class="hidden sm:inline">Online</span>
      {:else}
        <WifiOff class="w-3.5 h-3.5" />
        <span class="hidden sm:inline">Offline Mode</span>
      {/if}
    </div>

    <!-- tombol sinkronisasi antrean offline -->
    <button
      type="button"
      onclick={onSyncNow}
      disabled={isSyncing || (!isOnline && pendingSyncCount === 0)}
      title="Sinkronisasi Antrean Offline ke Server"
      class="flex items-center gap-1.5 text-xs font-mono bg-[#262626] hover:bg-[#393939] text-[#c6c6c6] px-2.5 py-1.5 border border-[#393939] transition-colors cursor-pointer disabled:opacity-50"
    >
      <RefreshCw class={`w-3.5 h-3.5 text-[#0f62fe] ${isSyncing ? 'animate-spin' : ''}`} />
      <span class="font-bold text-white">{pendingSyncCount}</span>
      <span class="hidden lg:inline text-[11px] text-[#8c8c8c]">
        {isSyncing ? 'Menyinkronkan...' : 'Antrean'}
      </span>
    </button>

    <!-- pemilih profil kasir -->
    <button
      type="button"
      onclick={onOpenPinModal}
      class="flex items-center gap-2 bg-[#262626] hover:bg-[#393939] text-white px-3 py-1.5 border border-[#393939] transition-colors cursor-pointer"
    >
      <div class="w-5 h-5 rounded-full bg-[#0f62fe] text-white flex items-center justify-center text-[10px] font-bold">
        {activeCashier ? activeCashier.name.charAt(0) : '?'}
      </div>
      <div class="text-left leading-tight hidden sm:block">
        <div class="text-xs font-medium truncate max-w-25">{activeCashier ? activeCashier.name : 'Pilih Kasir'}</div>
        <div class="text-[10px] font-mono text-[#8c8c8c]">{activeCashier ? activeCashier.role : 'PIN'}</div>
      </div>
    </button>

    <!-- tombol kunci kiosk master lock -->
    <button
      type="button"
      onclick={onOpenMasterLockModal}
      title="Kunci Kiosk / Otorisasi Owner"
      class="p-2 bg-[#262626] hover:bg-[#393939] text-[#8c8c8c] hover:text-white border border-[#393939] transition-colors cursor-pointer"
      aria-label="Master Lock"
    >
      <Lock class="w-3.5 h-3.5" />
    </button>
  </div>
</header>
