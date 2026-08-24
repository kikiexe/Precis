<script lang="ts">
  import { UserCircle, Store, Database, Lock } from 'lucide-svelte';
  import type { CashierUser } from '../../../types/pos';

  interface Props {
    activeCashier?: CashierUser | null;
    cashiers?: CashierUser[];
    totalOrdersCount: number;
    totalProductsCount: number;
    onOpenMasterLockModal: () => void;
    onClearLocalCache: () => void;
  }

  let {
    activeCashier = null,
    cashiers = [],
    totalOrdersCount = 0,
    totalProductsCount = 0,
    onOpenMasterLockModal,
    onClearLocalCache,
  }: Props = $props();
</script>

<div class="flex-1 bg-[#eeece7]/30 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6 font-sans">
  <div>
    <h2 class="text-xl font-medium text-[#212121] tracking-tight">Profil Kasir &amp; Status Terminal Kiosk</h2>
    <p class="text-xs text-[#616161] font-normal mt-0.5">Identitas outlet, status sinkronisasi, dan penyimpanan IndexedDB lokal</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Active Cashier Profile Card -->
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 shadow-none space-y-4">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <UserCircle class="w-4 h-4 text-[#1863dc]" />
          <h3 class="font-medium text-sm text-[#212121]">Operator Terminal Outlet</h3>
        </div>
        <span class="text-[10px] font-mono px-2.5 py-0.5 bg-[#edfce9] text-[#003c33] rounded-full font-medium">
          Multi-Operator
        </span>
      </div>

      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-[16px] bg-[#17171c] text-white flex items-center justify-center font-medium text-xl shadow-none">
          {activeCashier?.name ? activeCashier.name.charAt(0) : 'T'}
        </div>
        <div>
          <div class="font-medium text-base text-[#212121]">{activeCashier?.name || 'Tim Operasional Bar & Kasir'}</div>
          <div class="text-xs font-mono text-[#75758a] mt-0.5">Mode: Multi-Barista Shift</div>
          <div class="text-[11px] text-[#93939f] font-normal">Terminal kasir siap transaksi bersama</div>
        </div>
      </div>
    </div>

    <!-- Outlet & Device Token Info Card -->
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 shadow-none space-y-4">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <Store class="w-4 h-4 text-[#1863dc]" />
          <h3 class="font-medium text-sm text-[#212121]">Identitas Outlet &amp; Token</h3>
        </div>
        <span class="text-[10px] font-mono px-2.5 py-0.5 bg-[#f1f5ff] text-[#1863dc] rounded-full font-medium">
          TERPAIRING
        </span>
      </div>

      <div class="space-y-2.5 text-xs font-mono">
        <div class="flex justify-between text-[#616161]">
          <span>Nama Outlet:</span>
          <span class="font-medium text-[#212121]">Outlet Sleman #01</span>
        </div>
        <div class="flex justify-between text-[#616161]">
          <span>Device Token ID:</span>
          <span class="text-[#1863dc]">dev-tok-sleman-01-a89b</span>
        </div>
        <div class="flex justify-between text-[#616161]">
          <span>Workspace Tenant:</span>
          <span class="text-[#212121]">Amore Group (Multi-Outlet)</span>
        </div>
      </div>

      <div class="pt-3 border-t border-[#d9d9dd]">
        <button
          type="button"
          onclick={onOpenMasterLockModal}
          class="w-full py-2.5 bg-[#eeece7]/40 hover:bg-[#eeece7] text-[#212121] border border-[#d9d9dd] rounded-full text-xs font-medium flex items-center justify-center gap-1.5 cursor-pointer transition-all shadow-none"
        >
          <Lock class="w-3.5 h-3.5 text-[#1863dc]" />
          <span>Buka Pengaturan Master Owner</span>
        </button>
      </div>
    </div>

    <!-- Database Offline Storage (Dexie.js) Card -->
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] p-6 shadow-none space-y-4 md:col-span-2">
      <div class="flex items-center justify-between border-b border-[#d9d9dd] pb-3.5">
        <div class="flex items-center gap-2">
          <Database class="w-4 h-4 text-[#003c33]" />
          <h3 class="font-medium text-sm text-[#212121]">Mesin Penyimpanan Offline (Dexie.js IndexedDB)</h3>
        </div>
        <span class="text-[10px] font-mono px-2.5 py-0.5 bg-[#edfce9] text-[#003c33] rounded-full font-medium">STATUS: HEALTHY</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
        <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px]">
          <div class="text-[11px] text-[#75758a]">Total Menu Tersimpan</div>
          <div class="text-lg font-medium font-mono text-[#212121] mt-1">{totalProductsCount} Produk</div>
        </div>

        <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px]">
          <div class="text-[11px] text-[#75758a]">Riwayat Struk di Database</div>
          <div class="text-lg font-medium font-mono text-[#212121] mt-1">{totalOrdersCount} Transaksi</div>
        </div>

        <div class="p-4 bg-[#eeece7]/30 border border-[#d9d9dd] rounded-[16px]">
          <div class="text-[11px] text-[#75758a]">Karyawan Terdaftar di Kasir</div>
          <div class="text-lg font-medium font-mono text-[#212121] mt-1">{cashiers.length} Akun Kasir</div>
        </div>
      </div>

      <div class="pt-3 border-t border-[#d9d9dd] flex items-center justify-between text-xs">
        <span class="text-[#75758a]">Sinkronisasi otomatis berjalan tiap kali koneksi internet tersedia.</span>
        <button
          type="button"
          onclick={onClearLocalCache}
          class="px-3.5 py-1 bg-white hover:bg-[#ffad9b]/15 text-[#b30000] text-xs font-mono border border-[#d9d9dd] rounded-full cursor-pointer transition-all"
        >
          Reset Master Seed Data
        </button>
      </div>
    </div>
  </div>
</div>
