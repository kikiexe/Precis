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

<header class="bg-[#17171c] text-white border-b border-[#262626] px-4 py-2.5 flex items-center justify-between select-none shrink-0 h-14 font-sans">
  <!-- merek dan nama cabang -->
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2.5">
      <div class="w-7 h-7 bg-white text-[#17171c] flex items-center justify-center font-medium text-xs rounded-[8px]">
        P
      </div>
      <div>
        <div class="text-xs font-medium tracking-tight text-white leading-none">PRÉCIS POS</div>
        <div class="flex items-center gap-1 text-[10px] text-[#93939f] font-mono mt-0.5">
          <Store class="w-3 h-3 text-[#1863dc]" />
          <span>{branchName}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- jam live wib dan status sesi kasir -->
  <div class="hidden md:flex items-center gap-3">
    <!-- jam live wib -->
    <div class="flex items-center gap-1.5 font-mono text-xs text-[#d9d9dd] bg-white/10 px-3 py-1 rounded-full border border-white/10">
      <Clock class="w-3.5 h-3.5 text-[#edfce9]" />
      <span>{currentTime} WIB</span>
    </div>

    <!-- badge sesi kasir aktif -->
    <button
      type="button"
      onclick={onOpenSessionModal}
      class={`flex items-center gap-1.5 text-xs font-mono px-3 py-1 rounded-full border transition-all cursor-pointer ${
        activeSession && activeSession.status === 'OPEN'
          ? 'bg-[#edfce9]/20 text-[#edfce9] border-[#edfce9]/30 hover:bg-[#edfce9]/30'
          : 'bg-[#ffad9b]/20 text-[#ffad9b] border-[#ffad9b]/30 hover:bg-[#ffad9b]/30'
      }`}
    >
      <DollarSign class="w-3.5 h-3.5" />
      <span>{activeSession && activeSession.status === 'OPEN' ? 'Kasir Buka' : 'Kasir Tutup'}</span>
    </button>
  </div>

  <!-- status koneksi internet, sinkronisasi, dan pin kasir -->
  <div class="flex items-center gap-2 sm:gap-2.5">
    <!-- indikator online atau offline -->
    <div
      class={`flex items-center gap-1.5 text-xs font-mono px-2.5 py-1 rounded-full border ${
        isOnline
          ? 'bg-[#edfce9]/20 text-[#edfce9] border-[#edfce9]/30'
          : 'bg-[#ffad9b]/20 text-[#ffad9b] border-[#ffad9b]/30 animate-pulse'
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
      class="flex items-center gap-1.5 text-xs font-mono bg-white/10 hover:bg-white/15 text-[#d9d9dd] px-3 py-1 rounded-full border border-white/10 transition-all cursor-pointer disabled:opacity-50"
    >
      <RefreshCw class={`w-3.5 h-3.5 text-[#1863dc] ${isSyncing ? 'animate-spin' : ''}`} />
      <span class="font-medium text-white">{pendingSyncCount}</span>
      <span class="hidden lg:inline text-[10px] text-[#93939f]">
        {isSyncing ? 'Menyinkronkan...' : 'Antrean'}
      </span>
    </button>

    <!-- pemilih profil kasir -->
    <button
      type="button"
      onclick={onOpenPinModal}
      class="flex items-center gap-2 bg-white/10 hover:bg-white/15 text-white px-3 py-1 rounded-full border border-white/10 transition-all cursor-pointer"
    >
      <div class="w-5 h-5 rounded-full bg-[#1863dc] text-white flex items-center justify-center text-[10px] font-medium">
        {activeCashier ? activeCashier.name.charAt(0) : '?'}
      </div>
      <div class="text-left leading-tight hidden sm:block">
        <div class="text-xs font-medium truncate max-w-25">{activeCashier ? activeCashier.name : 'Pilih Kasir'}</div>
        <div class="text-[9px] font-mono text-[#93939f]">{activeCashier ? activeCashier.role : 'PIN'}</div>
      </div>
    </button>

    <!-- tombol kunci kiosk master lock -->
    <button
      type="button"
      onclick={onOpenMasterLockModal}
      title="Kunci Kiosk / Otorisasi Owner"
      class="p-1.5 bg-white/10 hover:bg-white/15 text-[#93939f] hover:text-white rounded-full border border-white/10 transition-all cursor-pointer"
      aria-label="Master Lock"
    >
      <Lock class="w-3.5 h-3.5" />
    </button>
  </div>
</header>
