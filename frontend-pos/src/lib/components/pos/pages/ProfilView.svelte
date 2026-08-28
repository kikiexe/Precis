<script lang="ts">
  import {
    Lock,
    QrCode,
    HardDrive,
    ShoppingBag,
    Check,
    RefreshCw,
    ShieldCheck,
  } from 'lucide-svelte';
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

  // Active Settings Sub-Tab
  let activeSettingsTab = $state<'pesanan' | 'pembayaran' | 'penyimpanan'>('pesanan');

  // Sub-Tab 1: Pesanan & Notifikasi State
  let autoAcceptOnline = $state(true);
  let enableSound = $state(true);
  let autoPrintReceipt = $state(true);
  let receiptPaperSize = $state<'58mm' | '80mm'>('58mm');
  let receiptFooterMessage = $state('Terima kasih atas kunjungan Anda!');

  // Sub-Tab 2: Pembayaran & QRIS State
  let enableCashPayment = $state(true);
  let enableQrisPayment = $state(true);
  let enableTransferPayment = $state(true);
  let bankAccountNumber = $state('BCA 8901238910 - PT PRECIS KREATIF');
  let qrisMerchantName = $state('PRECIS COFFEE OUTLET SETURAN');
  let paymentSaveMsg = $state<string | null>(null);

  // Sub-Tab 3: Sync & Storage State
  let isSyncingNow = $state(false);
  let syncSuccessMsg = $state<string | null>(null);

  function handleSavePaymentSettings() {
    paymentSaveMsg = 'Pengaturan metode pembayaran berhasil disimpan.';
    setTimeout(() => (paymentSaveMsg = null), 3000);
  }

  function handleTriggerSync() {
    isSyncingNow = true;
    syncSuccessMsg = null;
    setTimeout(() => {
      isSyncingNow = false;
      syncSuccessMsg = 'Semua data transaksi kasir berhasil disinkronkan ke server cloud.';
      setTimeout(() => (syncSuccessMsg = null), 4000);
    }, 1200);
  }
</script>

<div class="flex-1 flex flex-col md:flex-row h-full bg-[#f4f6f9] overflow-hidden font-sans select-none">
  <!-- Left Side: Profile & Settings Navigation Menu -->
  <div class="w-72 md:w-80 bg-white border-r border-zinc-200 flex flex-col h-full shrink-0">
    <!-- User Profile Header -->
    <div class="p-5 border-b border-zinc-200 flex items-center gap-3.5 bg-zinc-50/50">
      <div class="w-12 h-12 rounded-2xl bg-zinc-900 text-white font-bold text-lg flex items-center justify-center shadow-xs shrink-0">
        {activeCashier?.name ? activeCashier.name.charAt(0).toUpperCase() : 'P'}
      </div>
      <div class="min-w-0">
        <h2 class="font-bold text-sm text-zinc-900 truncate">Précis Coffee Kiosk</h2>
        <p class="text-[11px] text-zinc-500 truncate">
          {activeCashier?.name ? `Petugas: ${activeCashier.name}` : 'Terminal Outlet #01'}
        </p>
        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
          TERHUBUNG OUTLET
        </span>
      </div>
    </div>

    <!-- Navigation Sub-Menu Items -->
    <div class="p-3 space-y-3 overflow-y-auto flex-1 text-xs">
      <div>
        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-wider">
          Operasional &amp; Struk
        </div>
        <div class="mt-1 space-y-1">
          <button
            type="button"
            onclick={() => (activeSettingsTab = 'pesanan')}
            class={`w-full px-3.5 py-2.5 rounded-xl font-semibold flex items-center justify-between transition-all cursor-pointer ${
              activeSettingsTab === 'pesanan'
                ? 'bg-zinc-900 text-white shadow-2xs'
                : 'text-zinc-700 hover:bg-zinc-100'
            }`}
          >
            <div class="flex items-center gap-2.5">
              <ShoppingBag class="w-4 h-4" />
              <span>Pesanan &amp; Struk</span>
            </div>
            {#if activeSettingsTab === 'pesanan'}
              <span class="w-2 h-2 rounded-full bg-white"></span>
            {/if}
          </button>
        </div>
      </div>

      <div>
        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-wider">
          Metode Pembayaran
        </div>
        <div class="mt-1 space-y-1">
          <button
            type="button"
            onclick={() => (activeSettingsTab = 'pembayaran')}
            class={`w-full px-3.5 py-2.5 rounded-xl font-semibold flex items-center justify-between transition-all cursor-pointer ${
              activeSettingsTab === 'pembayaran'
                ? 'bg-zinc-900 text-white shadow-2xs'
                : 'text-zinc-700 hover:bg-zinc-100'
            }`}
          >
            <div class="flex items-center gap-2.5">
              <QrCode class="w-4 h-4" />
              <span>Pembayaran &amp; QRIS</span>
            </div>
            {#if activeSettingsTab === 'pembayaran'}
              <span class="w-2 h-2 rounded-full bg-white"></span>
            {/if}
          </button>
        </div>
      </div>

      <div>
        <div class="px-3 py-1.5 text-[10px] font-bold text-zinc-400 uppercase tracking-wider">
          Penyimpanan &amp; Keamanan
        </div>
        <div class="mt-1 space-y-1">
          <button
            type="button"
            onclick={() => (activeSettingsTab = 'penyimpanan')}
            class={`w-full px-3.5 py-2.5 rounded-xl font-semibold flex items-center justify-between transition-all cursor-pointer ${
              activeSettingsTab === 'penyimpanan'
                ? 'bg-zinc-900 text-white shadow-2xs'
                : 'text-zinc-700 hover:bg-zinc-100'
            }`}
          >
            <div class="flex items-center gap-2.5">
              <HardDrive class="w-4 h-4" />
              <span>Penyimpanan Offline</span>
            </div>
            {#if activeSettingsTab === 'penyimpanan'}
              <span class="w-2 h-2 rounded-full bg-white"></span>
            {/if}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side: Active Settings Content -->
  <div class="flex-1 bg-[#f4f6f9] p-6 sm:p-8 overflow-y-auto">
    <div class="max-w-2xl mx-auto space-y-6">
      
      <!-- SUB-TAB 1: PESANAN & STRUK -->
      {#if activeSettingsTab === 'pesanan'}
        <div class="bg-white rounded-2xl border border-zinc-200 p-6 shadow-2xs space-y-5">
          <div class="border-b border-zinc-100 pb-3">
            <h3 class="text-base font-bold text-zinc-900">Pengaturan Pesanan &amp; Struk Kasir</h3>
            <p class="text-xs text-zinc-500 mt-0.5">Konfigurasi otomatisasi transaksi dan cetak nota kasir</p>
          </div>

          <div class="space-y-4">
            <!-- Toggle: Auto Accept -->
            <div class="flex items-center justify-between">
              <div>
                <div class="text-xs font-bold text-zinc-900">Penerimaan Pesanan Otomatis</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Langsung proses pesanan masuk tanpa konfirmasi manual</div>
              </div>
              <button
                type="button"
                aria-label="Toggle auto accept"
                onclick={() => (autoAcceptOnline = !autoAcceptOnline)}
                class={`w-11 h-6 rounded-full transition-colors relative cursor-pointer ${
                  autoAcceptOnline ? 'bg-zinc-900' : 'bg-zinc-300'
                }`}
              >
                <div
                  class={`w-5 h-5 rounded-full bg-white transition-transform transform shadow-xs absolute top-0.5 ${
                    autoAcceptOnline ? 'translate-x-5.5' : 'translate-x-0.5'
                  }`}
                ></div>
              </button>
            </div>

            <!-- Toggle: Sound -->
            <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
              <div>
                <div class="text-xs font-bold text-zinc-900">Bunyi Notifikasi Transaksi</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Mainkan audio saat transaksi berhasil dibayar</div>
              </div>
              <button
                type="button"
                aria-label="Toggle sound"
                onclick={() => (enableSound = !enableSound)}
                class={`w-11 h-6 rounded-full transition-colors relative cursor-pointer ${
                  enableSound ? 'bg-zinc-900' : 'bg-zinc-300'
                }`}
              >
                <div
                  class={`w-5 h-5 rounded-full bg-white transition-transform transform shadow-xs absolute top-0.5 ${
                    enableSound ? 'translate-x-5.5' : 'translate-x-0.5'
                  }`}
                ></div>
              </button>
            </div>

            <!-- Toggle: Auto Print Receipt -->
            <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
              <div>
                <div class="text-xs font-bold text-zinc-900">Cetak Struk Otomatis</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Kirim perintah ke printer thermal setelah pembayaran selesai</div>
              </div>
              <button
                type="button"
                aria-label="Toggle auto print receipt"
                onclick={() => (autoPrintReceipt = !autoPrintReceipt)}
                class={`w-11 h-6 rounded-full transition-colors relative cursor-pointer ${
                  autoPrintReceipt ? 'bg-zinc-900' : 'bg-zinc-300'
                }`}
              >
                <div
                  class={`w-5 h-5 rounded-full bg-white transition-transform transform shadow-xs absolute top-0.5 ${
                    autoPrintReceipt ? 'translate-x-5.5' : 'translate-x-0.5'
                  }`}
                ></div>
              </button>
            </div>

            <!-- Paper Size Selection -->
            <div class="pt-3 border-t border-zinc-100 space-y-2">
              <div class="text-xs font-bold text-zinc-900 block">Ukuran Kertas Printer Thermal</div>
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  onclick={() => (receiptPaperSize = '58mm')}
                  class={`p-3 rounded-xl border text-left transition-all cursor-pointer ${
                    receiptPaperSize === '58mm'
                      ? 'border-zinc-900 bg-zinc-50 shadow-2xs'
                      : 'border-zinc-200 bg-white hover:bg-zinc-50'
                  }`}
                >
                  <div class="text-xs font-bold text-zinc-900">58mm (Standar Kiosk)</div>
                  <div class="text-[10px] text-zinc-500 mt-0.5">Lebar 32 karakter per baris</div>
                </button>

                <button
                  type="button"
                  onclick={() => (receiptPaperSize = '80mm')}
                  class={`p-3 rounded-xl border text-left transition-all cursor-pointer ${
                    receiptPaperSize === '80mm'
                      ? 'border-zinc-900 bg-zinc-50 shadow-2xs'
                      : 'border-zinc-200 bg-white hover:bg-zinc-50'
                  }`}
                >
                  <div class="text-xs font-bold text-zinc-900">80mm (Printer Desktop)</div>
                  <div class="text-[10px] text-zinc-500 mt-0.5">Lebar 48 karakter per baris</div>
                </button>
              </div>
            </div>

            <!-- Footer Note -->
            <div class="pt-3 border-t border-zinc-100 space-y-1.5">
              <label for="receipt-footer" class="text-xs font-bold text-zinc-900 block">Pesan Kaki Struk (Footer Nota)</label>
              <input
                id="receipt-footer"
                type="text"
                bind:value={receiptFooterMessage}
                class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden font-medium"
              />
            </div>
          </div>
        </div>

      <!-- SUB-TAB 2: PEMBAYARAN & QRIS -->
      {:else if activeSettingsTab === 'pembayaran'}
        <div class="bg-white rounded-2xl border border-zinc-200 p-6 shadow-2xs space-y-5">
          <div class="border-b border-zinc-100 pb-3">
            <h3 class="text-base font-bold text-zinc-900">Konfigurasi Metode Pembayaran &amp; QRIS</h3>
            <p class="text-xs text-zinc-500 mt-0.5">Atur channel penerimaan uang tunai, QRIS, dan transfer bank kasir</p>
          </div>

          {#if paymentSaveMsg}
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
              <Check class="w-4 h-4 shrink-0" />
              <span>{paymentSaveMsg}</span>
            </div>
          {/if}

          <div class="space-y-4">
            <!-- Channel Toggles -->
            <div class="space-y-3">
              <div class="text-xs font-bold text-zinc-900 block">Metode Pembayaran Aktif di Kasir</div>
              
              <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-xl flex items-center justify-between">
                <div>
                  <div class="text-xs font-semibold text-zinc-900">Pembayaran Tunai (Cash)</div>
                  <div class="text-[10px] text-zinc-500">Menerima uang tunai dan menghitung kembalian laci</div>
                </div>
                <input
                  type="checkbox"
                  bind:checked={enableCashPayment}
                  class="w-4 h-4 accent-zinc-900 cursor-pointer"
                />
              </div>

              <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-xl flex items-center justify-between">
                <div>
                  <div class="text-xs font-semibold text-zinc-900">QRIS Dinamis &amp; Statis</div>
                  <div class="text-[10px] text-zinc-500">Scan QRIS langsung dari layar tablet atau kertas kasir</div>
                </div>
                <input
                  type="checkbox"
                  bind:checked={enableQrisPayment}
                  class="w-4 h-4 accent-zinc-900 cursor-pointer"
                />
              </div>

              <div class="p-3 bg-zinc-50 border border-zinc-200 rounded-xl flex items-center justify-between">
                <div>
                  <div class="text-xs font-semibold text-zinc-900">Mesin EDC (Kartu Debit &amp; Kredit)</div>
                  <div class="text-[10px] text-zinc-500">Penerimaan pembayaran via mesin terminal EDC kasir</div>
                </div>
                <input
                  type="checkbox"
                  bind:checked={enableTransferPayment}
                  class="w-4 h-4 accent-zinc-900 cursor-pointer"
                />
              </div>
            </div>

            <!-- QRIS Merchant Info -->
            <div class="pt-3 border-t border-zinc-100 space-y-1.5">
              <label for="qris-name" class="text-xs font-bold text-zinc-900 block">Nama Merchant QRIS Outlet</label>
              <input
                id="qris-name"
                type="text"
                bind:value={qrisMerchantName}
                class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden font-medium"
              />
            </div>

            <!-- Bank Account Info -->
            <div class="pt-1 space-y-1.5">
              <label for="bank-account" class="text-xs font-bold text-zinc-900 block">Rekening Bank Tujuan Setoran</label>
              <input
                id="bank-account"
                type="text"
                bind:value={bankAccountNumber}
                class="w-full px-3.5 py-2 bg-zinc-50 border border-zinc-200 rounded-xl text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:outline-hidden font-mono font-bold"
              />
            </div>

            <div class="pt-2">
              <button
                type="button"
                onclick={handleSavePaymentSettings}
                class="px-4 py-2.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-xl cursor-pointer shadow-2xs transition-all active:scale-[0.99]"
              >
                Simpan Pengaturan Pembayaran
              </button>
            </div>
          </div>
        </div>

      <!-- SUB-TAB 3: PENYIMPANAN OFFLINE & KEAMANAN -->
      {:else if activeSettingsTab === 'penyimpanan'}
        <div class="space-y-5">
          <!-- Card: Offline Data Status -->
          <div class="bg-white rounded-2xl border border-zinc-200 p-6 shadow-2xs space-y-5">
            <div class="border-b border-zinc-100 pb-3">
              <h3 class="text-base font-bold text-zinc-900">Penyimpanan Offline Lokal Terminal</h3>
              <p class="text-xs text-zinc-500 mt-0.5">Semua data transaksi dan menu tersimpan aman di perangkat tablet kasir</p>
            </div>

            {#if syncSuccessMsg}
              <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
                <Check class="w-4 h-4 shrink-0 text-emerald-600" />
                <span>{syncSuccessMsg}</span>
              </div>
            {/if}

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 font-mono">
              <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-xl">
                <div class="text-[11px] font-sans text-zinc-500">Riwayat Transaksi</div>
                <div class="text-base font-bold text-zinc-900 mt-1">{totalOrdersCount} Struk</div>
              </div>

              <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-xl">
                <div class="text-[11px] font-sans text-zinc-500">Menu Jualan</div>
                <div class="text-base font-bold text-zinc-900 mt-1">{totalProductsCount} Produk</div>
              </div>

              <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-xl">
                <div class="text-[11px] font-sans text-zinc-500">Staf Operator</div>
                <div class="text-base font-bold text-zinc-900 mt-1">{cashiers.length} Kasir</div>
              </div>
            </div>

            <div class="pt-3 border-t border-zinc-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
              <span class="text-zinc-500 text-[11px]">
                Sinkronisasi otomatis berjalan di latar belakang saat terminal online.
              </span>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  onclick={handleTriggerSync}
                  disabled={isSyncingNow}
                  class="px-3.5 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-xl cursor-pointer shadow-2xs transition-all flex items-center justify-center gap-1.5 disabled:opacity-50"
                >
                  <RefreshCw class={`w-3.5 h-3.5 ${isSyncingNow ? 'animate-spin' : ''}`} />
                  <span>{isSyncingNow ? 'Sinkronisasi...' : 'Sinkronkan Sekarang'}</span>
                </button>

                <button
                  type="button"
                  onclick={onClearLocalCache}
                  class="px-3.5 py-2 bg-zinc-100 hover:bg-zinc-200 text-zinc-800 text-xs font-semibold rounded-xl cursor-pointer transition-colors"
                >
                  Muat Ulang Data Standar
                </button>
              </div>
            </div>
          </div>

          <!-- Card: Master Lock Security -->
          <div class="bg-white rounded-2xl border border-zinc-200 p-6 shadow-2xs space-y-4">
            <div class="flex items-center gap-3 border-b border-zinc-100 pb-3">
              <div class="w-8 h-8 rounded-xl bg-zinc-900 text-white flex items-center justify-center">
                <Lock class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-zinc-900">Master Lock &amp; Otorisasi Owner</h3>
                <p class="text-[11px] text-zinc-500">Proteksi PIN khusus untuk diskon besar, void pesanan, dan buka laci</p>
              </div>
            </div>

            <p class="text-xs text-zinc-600 leading-relaxed">
              Fitur proteksi Master Lock memastikan bahwa kasir staf tidak dapat membatalkan pesanan atau memberikan diskon tanpa otorisasi PIN Owner.
            </p>

            <button
              type="button"
              onclick={onOpenMasterLockModal}
              class="w-full py-2.5 bg-zinc-900 hover:bg-black text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow-xs transition-all active:scale-[0.99]"
            >
              <ShieldCheck class="w-4 h-4" />
              <span>Buka Pengaturan Master Owner</span>
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>
