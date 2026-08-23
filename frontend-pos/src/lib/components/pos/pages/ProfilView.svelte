<script lang="ts">
  import { UserCircle, Store, Database, Lock, Key } from 'lucide-svelte';
  import type { CashierUser } from '../../../types/pos';

  interface Props {
    activeCashier: CashierUser | null;
    cashiers: CashierUser[];
    totalOrdersCount: number;
    totalProductsCount: number;
    onOpenPinModal: () => void;
    onOpenMasterLockModal: () => void;
    onClearLocalCache: () => void;
  }

  let {
    activeCashier,
    cashiers = [],
    totalOrdersCount = 0,
    totalProductsCount = 0,
    onOpenPinModal,
    onOpenMasterLockModal,
    onClearLocalCache,
  }: Props = $props();
</script>

<div class="flex-1 bg-[#f4f4f4] p-6 overflow-y-auto space-y-6">
  <div>
    <h2 class="text-xl font-bold text-[#161616] font-display">Profil Kasir &amp; Status Terminal Kiosk</h2>
    <p class="text-xs text-[#525252] font-mono">Pengaturan otentikasi kasir, identitas outlet, dan penyimpanan IndexedDB lokal</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Active Cashier Profile Card -->
    <div class="bg-white border border-[#e0e0e0] p-5 shadow-xs space-y-4">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <UserCircle class="w-5 h-5 text-[#0f62fe]" />
          <h3 class="font-bold text-sm text-[#161616]">Kasir yang Sedang Login</h3>
        </div>
        <span class="text-[10px] font-mono px-2 py-0.5 bg-[#24a148]/10 text-[#24a148] border border-[#24a148]/30">
          Aktif Bertugas
        </span>
      </div>

      <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-[#161616] text-white flex items-center justify-center font-bold text-xl">
          {activeCashier ? activeCashier.name.charAt(0) : '?'}
        </div>
        <div>
          <div class="font-bold text-base text-[#161616]">{activeCashier ? activeCashier.name : 'Belum Login'}</div>
          <div class="text-xs font-mono text-[#525252] mt-0.5">Peran: {activeCashier ? activeCashier.role : '-'}</div>
          <div class="text-[11px] text-[#8c8c8c]">Otorisasi 4-digit PIN aktif</div>
        </div>
      </div>

      <div class="pt-2 border-t border-[#e0e0e0] flex gap-2">
        <button
          type="button"
          onclick={onOpenPinModal}
          class="flex-1 py-2 bg-[#0f62fe] hover:bg-[#0050e6] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-colors"
        >
          <Key class="w-3.5 h-3.5" />
          <span>Ganti Kasir (Input PIN)</span>
        </button>
      </div>
    </div>

    <!-- Outlet & Device Token Info Card -->
    <div class="bg-white border border-[#e0e0e0] p-5 shadow-xs space-y-4">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <Store class="w-5 h-5 text-[#0f62fe]" />
          <h3 class="font-bold text-sm text-[#161616]">Identitas Outlet &amp; Token</h3>
        </div>
        <span class="text-[10px] font-mono px-2 py-0.5 bg-[#0f62fe]/10 text-[#0f62fe] border border-[#0f62fe]/30">
          TERPAIRING
        </span>
      </div>

      <div class="space-y-2 text-xs font-mono">
        <div class="flex justify-between text-[#525252]">
          <span>Nama Outlet:</span>
          <span class="font-bold text-[#161616]">Outlet Sleman #01</span>
        </div>
        <div class="flex justify-between text-[#525252]">
          <span>Device Token ID:</span>
          <span class="text-[#0f62fe]">dev-tok-sleman-01-a89b</span>
        </div>
        <div class="flex justify-between text-[#525252]">
          <span>Workspace Tenant:</span>
          <span class="text-[#161616]">Amore Group (Multi-Outlet)</span>
        </div>
      </div>

      <div class="pt-2 border-t border-[#e0e0e0]">
        <button
          type="button"
          onclick={onOpenMasterLockModal}
          class="w-full py-2 bg-[#161616] hover:bg-[#262626] text-white text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer transition-colors"
        >
          <Lock class="w-3.5 h-3.5 text-[#0f62fe]" />
          <span>Buka Pengaturan Master Owner</span>
        </button>
      </div>
    </div>

    <!-- Database Offline Storage (Dexie.js) Card -->
    <div class="bg-white border border-[#e0e0e0] p-5 shadow-xs space-y-4 md:col-span-2">
      <div class="flex items-center justify-between border-b border-[#e0e0e0] pb-3">
        <div class="flex items-center gap-2">
          <Database class="w-5 h-5 text-[#24a148]" />
          <h3 class="font-bold text-sm text-[#161616]">Mesin Penyimpanan Offline (Dexie.js IndexedDB)</h3>
        </div>
        <span class="text-[10px] font-mono text-[#24a148]">STATUS: HEALTHY</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0]">
          <div class="text-[11px] font-mono text-[#525252]">Total Menu Tersimpan</div>
          <div class="text-lg font-bold font-mono text-[#161616] mt-0.5">{totalProductsCount} Produk</div>
        </div>

        <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0]">
          <div class="text-[11px] font-mono text-[#525252]">Riwayat Struk di Database</div>
          <div class="text-lg font-bold font-mono text-[#161616] mt-0.5">{totalOrdersCount} Transaksi</div>
        </div>

        <div class="p-3 bg-[#f4f4f4] border border-[#e0e0e0]">
          <div class="text-[11px] font-mono text-[#525252]">Karyawan Terdaftar di Kasir</div>
          <div class="text-lg font-bold font-mono text-[#161616] mt-0.5">{cashiers.length} Akun Kasir</div>
        </div>
      </div>

      <div class="pt-2 border-t border-[#e0e0e0] flex items-center justify-between text-xs">
        <span class="text-[#525252]">Sinkronisasi otomatis berjalan tiap kali koneksi internet tersedia.</span>
        <button
          type="button"
          onclick={onClearLocalCache}
          class="px-3 py-1.5 bg-[#f4f4f4] hover:bg-[#da1e28]/10 text-[#da1e28] text-xs font-mono border border-[#e0e0e0] cursor-pointer"
        >
          Reset Master Seed Data
        </button>
      </div>
    </div>
  </div>
</div>
