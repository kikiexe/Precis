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

<div
  class="flex h-full flex-1 flex-col overflow-hidden bg-[#f4f6f9] font-sans select-none md:flex-row"
>
  <!-- Left Side: Profile & Settings Navigation Menu -->
  <div class="flex h-full w-72 shrink-0 flex-col border-r border-zinc-200 bg-white md:w-80">
    <!-- User Profile Header -->
    <div class="flex items-center gap-3.5 border-b border-zinc-200 bg-zinc-50/50 p-5">
      <div
        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-zinc-900 text-lg font-bold text-white shadow-xs"
      >
        {activeCashier?.name ? activeCashier.name.charAt(0).toUpperCase() : 'P'}
      </div>
      <div class="min-w-0">
        <h2 class="truncate text-sm font-bold text-zinc-900">Précis Coffee Kiosk</h2>
        <p class="truncate text-[11px] text-zinc-500">
          {activeCashier?.name ? `Petugas: ${activeCashier.name}` : 'Terminal Outlet #01'}
        </p>
        <span
          class="mt-1 inline-block rounded-full border border-emerald-200 bg-emerald-100 px-2 py-0.5 font-mono text-[9px] font-bold text-emerald-800"
        >
          TERHUBUNG OUTLET
        </span>
      </div>
    </div>

    <!-- Navigation Sub-Menu Items -->
    <div class="flex-1 space-y-3 overflow-y-auto p-3 text-xs">
      <div>
        <div class="px-3 py-1.5 text-[10px] font-bold tracking-wider text-zinc-400 uppercase">
          Operasional &amp; Struk
        </div>
        <div class="mt-1 space-y-1">
          <button
            type="button"
            onclick={() => (activeSettingsTab = 'pesanan')}
            class={`flex w-full cursor-pointer items-center justify-between rounded-xl px-3.5 py-2.5 font-semibold transition-all ${
              activeSettingsTab === 'pesanan'
                ? 'bg-zinc-900 text-white shadow-2xs'
                : 'text-zinc-700 hover:bg-zinc-100'
            }`}
          >
            <div class="flex items-center gap-2.5">
              <ShoppingBag class="h-4 w-4" />
              <span>Pesanan &amp; Struk</span>
            </div>
            {#if activeSettingsTab === 'pesanan'}
              <span class="h-2 w-2 rounded-full bg-white"></span>
            {/if}
          </button>
        </div>
      </div>

      <div>
        <div class="px-3 py-1.5 text-[10px] font-bold tracking-wider text-zinc-400 uppercase">
          Metode Pembayaran
        </div>
        <div class="mt-1 space-y-1">
          <button
            type="button"
            onclick={() => (activeSettingsTab = 'pembayaran')}
            class={`flex w-full cursor-pointer items-center justify-between rounded-xl px-3.5 py-2.5 font-semibold transition-all ${
              activeSettingsTab === 'pembayaran'
                ? 'bg-zinc-900 text-white shadow-2xs'
                : 'text-zinc-700 hover:bg-zinc-100'
            }`}
          >
            <div class="flex items-center gap-2.5">
              <QrCode class="h-4 w-4" />
              <span>Pembayaran &amp; QRIS</span>
            </div>
            {#if activeSettingsTab === 'pembayaran'}
              <span class="h-2 w-2 rounded-full bg-white"></span>
            {/if}
          </button>
        </div>
      </div>

      <div>
        <div class="px-3 py-1.5 text-[10px] font-bold tracking-wider text-zinc-400 uppercase">
          Penyimpanan &amp; Keamanan
        </div>
        <div class="mt-1 space-y-1">
          <button
            type="button"
            onclick={() => (activeSettingsTab = 'penyimpanan')}
            class={`flex w-full cursor-pointer items-center justify-between rounded-xl px-3.5 py-2.5 font-semibold transition-all ${
              activeSettingsTab === 'penyimpanan'
                ? 'bg-zinc-900 text-white shadow-2xs'
                : 'text-zinc-700 hover:bg-zinc-100'
            }`}
          >
            <div class="flex items-center gap-2.5">
              <HardDrive class="h-4 w-4" />
              <span>Penyimpanan Offline</span>
            </div>
            {#if activeSettingsTab === 'penyimpanan'}
              <span class="h-2 w-2 rounded-full bg-white"></span>
            {/if}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Side: Active Settings Content -->
  <div class="flex-1 overflow-y-auto bg-[#f4f6f9] p-6 sm:p-8">
    <div class="mx-auto max-w-2xl space-y-6">
      <!-- SUB-TAB 1: PESANAN & STRUK -->
      {#if activeSettingsTab === 'pesanan'}
        <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xs">
          <div class="border-b border-zinc-100 pb-3">
            <h3 class="text-base font-bold text-zinc-900">Pengaturan Pesanan &amp; Struk Kasir</h3>
            <p class="mt-0.5 text-xs text-zinc-500">
              Konfigurasi otomatisasi transaksi dan cetak nota kasir
            </p>
          </div>

          <div class="space-y-4">
            <!-- Toggle: Auto Accept -->
            <div class="flex items-center justify-between">
              <div>
                <div class="text-xs font-bold text-zinc-900">Penerimaan Pesanan Otomatis</div>
                <div class="mt-0.5 text-[11px] text-zinc-500">
                  Langsung proses pesanan masuk tanpa konfirmasi manual
                </div>
              </div>
              <button
                type="button"
                aria-label="Toggle auto accept"
                onclick={() => (autoAcceptOnline = !autoAcceptOnline)}
                class={`relative h-6 w-11 cursor-pointer rounded-full transition-colors ${
                  autoAcceptOnline ? 'bg-zinc-900' : 'bg-zinc-300'
                }`}
              >
                <div
                  class={`absolute top-0.5 h-5 w-5 transform rounded-full bg-white shadow-xs transition-transform ${
                    autoAcceptOnline ? 'translate-x-5.5' : 'translate-x-0.5'
                  }`}
                ></div>
              </button>
            </div>

            <!-- Toggle: Sound -->
            <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
              <div>
                <div class="text-xs font-bold text-zinc-900">Bunyi Notifikasi Transaksi</div>
                <div class="mt-0.5 text-[11px] text-zinc-500">
                  Mainkan audio saat transaksi berhasil dibayar
                </div>
              </div>
              <button
                type="button"
                aria-label="Toggle sound"
                onclick={() => (enableSound = !enableSound)}
                class={`relative h-6 w-11 cursor-pointer rounded-full transition-colors ${
                  enableSound ? 'bg-zinc-900' : 'bg-zinc-300'
                }`}
              >
                <div
                  class={`absolute top-0.5 h-5 w-5 transform rounded-full bg-white shadow-xs transition-transform ${
                    enableSound ? 'translate-x-5.5' : 'translate-x-0.5'
                  }`}
                ></div>
              </button>
            </div>

            <!-- Toggle: Auto Print Receipt -->
            <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
              <div>
                <div class="text-xs font-bold text-zinc-900">Cetak Struk Otomatis</div>
                <div class="mt-0.5 text-[11px] text-zinc-500">
                  Kirim perintah ke printer thermal setelah pembayaran selesai
                </div>
              </div>
              <button
                type="button"
                aria-label="Toggle auto print receipt"
                onclick={() => (autoPrintReceipt = !autoPrintReceipt)}
                class={`relative h-6 w-11 cursor-pointer rounded-full transition-colors ${
                  autoPrintReceipt ? 'bg-zinc-900' : 'bg-zinc-300'
                }`}
              >
                <div
                  class={`absolute top-0.5 h-5 w-5 transform rounded-full bg-white shadow-xs transition-transform ${
                    autoPrintReceipt ? 'translate-x-5.5' : 'translate-x-0.5'
                  }`}
                ></div>
              </button>
            </div>

            <!-- Paper Size Selection -->
            <div class="space-y-2 border-t border-zinc-100 pt-3">
              <div class="block text-xs font-bold text-zinc-900">Ukuran Kertas Printer Thermal</div>
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  onclick={() => (receiptPaperSize = '58mm')}
                  class={`cursor-pointer rounded-xl border p-3 text-left transition-all ${
                    receiptPaperSize === '58mm'
                      ? 'border-zinc-900 bg-zinc-50 shadow-2xs'
                      : 'border-zinc-200 bg-white hover:bg-zinc-50'
                  }`}
                >
                  <div class="text-xs font-bold text-zinc-900">58mm (Standar Kiosk)</div>
                  <div class="mt-0.5 text-[10px] text-zinc-500">Lebar 32 karakter per baris</div>
                </button>

                <button
                  type="button"
                  onclick={() => (receiptPaperSize = '80mm')}
                  class={`cursor-pointer rounded-xl border p-3 text-left transition-all ${
                    receiptPaperSize === '80mm'
                      ? 'border-zinc-900 bg-zinc-50 shadow-2xs'
                      : 'border-zinc-200 bg-white hover:bg-zinc-50'
                  }`}
                >
                  <div class="text-xs font-bold text-zinc-900">80mm (Printer Desktop)</div>
                  <div class="mt-0.5 text-[10px] text-zinc-500">Lebar 48 karakter per baris</div>
                </button>
              </div>
            </div>

            <!-- Footer Note -->
            <div class="space-y-1.5 border-t border-zinc-100 pt-3">
              <label for="receipt-footer" class="block text-xs font-bold text-zinc-900"
                >Pesan Kaki Struk (Footer Nota)</label
              >
              <input
                id="receipt-footer"
                type="text"
                bind:value={receiptFooterMessage}
                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs font-medium text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
              />
            </div>
          </div>
        </div>

        <!-- SUB-TAB 2: PEMBAYARAN & QRIS -->
      {:else if activeSettingsTab === 'pembayaran'}
        <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xs">
          <div class="border-b border-zinc-100 pb-3">
            <h3 class="text-base font-bold text-zinc-900">
              Konfigurasi Metode Pembayaran &amp; QRIS
            </h3>
            <p class="mt-0.5 text-xs text-zinc-500">
              Atur channel penerimaan uang tunai, QRIS, dan transfer bank kasir
            </p>
          </div>

          {#if paymentSaveMsg}
            <div
              class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-semibold text-emerald-800"
            >
              <Check class="h-4 w-4 shrink-0" />
              <span>{paymentSaveMsg}</span>
            </div>
          {/if}

          <div class="space-y-4">
            <!-- Channel Toggles -->
            <div class="space-y-3">
              <div class="block text-xs font-bold text-zinc-900">
                Metode Pembayaran Aktif di Kasir
              </div>

              <div
                class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-3"
              >
                <div>
                  <div class="text-xs font-semibold text-zinc-900">Pembayaran Tunai (Cash)</div>
                  <div class="text-[10px] text-zinc-500">
                    Menerima uang tunai dan menghitung kembalian laci
                  </div>
                </div>
                <input
                  type="checkbox"
                  bind:checked={enableCashPayment}
                  class="h-4 w-4 cursor-pointer accent-zinc-900"
                />
              </div>

              <div
                class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-3"
              >
                <div>
                  <div class="text-xs font-semibold text-zinc-900">QRIS Dinamis &amp; Statis</div>
                  <div class="text-[10px] text-zinc-500">
                    Scan QRIS langsung dari layar tablet atau kertas kasir
                  </div>
                </div>
                <input
                  type="checkbox"
                  bind:checked={enableQrisPayment}
                  class="h-4 w-4 cursor-pointer accent-zinc-900"
                />
              </div>

              <div
                class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-3"
              >
                <div>
                  <div class="text-xs font-semibold text-zinc-900">
                    Mesin EDC (Kartu Debit &amp; Kredit)
                  </div>
                  <div class="text-[10px] text-zinc-500">
                    Penerimaan pembayaran via mesin terminal EDC kasir
                  </div>
                </div>
                <input
                  type="checkbox"
                  bind:checked={enableTransferPayment}
                  class="h-4 w-4 cursor-pointer accent-zinc-900"
                />
              </div>
            </div>

            <!-- QRIS Merchant Info -->
            <div class="space-y-1.5 border-t border-zinc-100 pt-3">
              <label for="qris-name" class="block text-xs font-bold text-zinc-900"
                >Nama Merchant QRIS Outlet</label
              >
              <input
                id="qris-name"
                type="text"
                bind:value={qrisMerchantName}
                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 text-xs font-medium text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
              />
            </div>

            <!-- Bank Account Info -->
            <div class="space-y-1.5 pt-1">
              <label for="bank-account" class="block text-xs font-bold text-zinc-900"
                >Rekening Bank Tujuan Setoran</label
              >
              <input
                id="bank-account"
                type="text"
                bind:value={bankAccountNumber}
                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 py-2 font-mono text-xs font-bold text-zinc-900 focus:border-zinc-900 focus:bg-white focus:outline-hidden"
              />
            </div>

            <div class="pt-2">
              <button
                type="button"
                onclick={handleSavePaymentSettings}
                class="cursor-pointer rounded-xl bg-zinc-900 px-4 py-2.5 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black active:scale-[0.99]"
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
          <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xs">
            <div class="border-b border-zinc-100 pb-3">
              <h3 class="text-base font-bold text-zinc-900">Penyimpanan Offline Lokal Terminal</h3>
              <p class="mt-0.5 text-xs text-zinc-500">
                Semua data transaksi dan menu tersimpan aman di perangkat tablet kasir
              </p>
            </div>

            {#if syncSuccessMsg}
              <div
                class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-semibold text-emerald-800"
              >
                <Check class="h-4 w-4 shrink-0 text-emerald-600" />
                <span>{syncSuccessMsg}</span>
              </div>
            {/if}

            <div class="grid grid-cols-1 gap-3 font-mono sm:grid-cols-3">
              <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
                <div class="font-sans text-[11px] text-zinc-500">Riwayat Transaksi</div>
                <div class="mt-1 text-base font-bold text-zinc-900">{totalOrdersCount} Struk</div>
              </div>

              <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
                <div class="font-sans text-[11px] text-zinc-500">Menu Jualan</div>
                <div class="mt-1 text-base font-bold text-zinc-900">
                  {totalProductsCount} Produk
                </div>
              </div>

              <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3.5">
                <div class="font-sans text-[11px] text-zinc-500">Staf Operator</div>
                <div class="mt-1 text-base font-bold text-zinc-900">{cashiers.length} Kasir</div>
              </div>
            </div>

            <div
              class="flex flex-col items-stretch justify-between gap-3 border-t border-zinc-100 pt-3 text-xs sm:flex-row sm:items-center"
            >
              <span class="text-[11px] text-zinc-500">
                Sinkronisasi otomatis berjalan di latar belakang saat terminal online.
              </span>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  onclick={handleTriggerSync}
                  disabled={isSyncingNow}
                  class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-zinc-900 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition-all hover:bg-black disabled:opacity-50"
                >
                  <RefreshCw class={`h-3.5 w-3.5 ${isSyncingNow ? 'animate-spin' : ''}`} />
                  <span>{isSyncingNow ? 'Sinkronisasi...' : 'Sinkronkan Sekarang'}</span>
                </button>

                <button
                  type="button"
                  onclick={onClearLocalCache}
                  class="cursor-pointer rounded-xl bg-zinc-100 px-3.5 py-2 text-xs font-semibold text-zinc-800 transition-colors hover:bg-zinc-200"
                >
                  Muat Ulang Data Standar
                </button>
              </div>
            </div>
          </div>

          <!-- Card: Master Lock Security -->
          <div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xs">
            <div class="flex items-center gap-3 border-b border-zinc-100 pb-3">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-xl bg-zinc-900 text-white"
              >
                <Lock class="h-4 w-4" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-zinc-900">Master Lock &amp; Otorisasi Owner</h3>
                <p class="text-[11px] text-zinc-500">
                  Proteksi PIN khusus untuk diskon besar, void pesanan, dan buka laci
                </p>
              </div>
            </div>

            <p class="text-xs leading-relaxed text-zinc-600">
              Fitur proteksi Master Lock memastikan bahwa kasir staf tidak dapat membatalkan pesanan
              atau memberikan diskon tanpa otorisasi PIN Owner.
            </p>

            <button
              type="button"
              onclick={onOpenMasterLockModal}
              class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-zinc-900 py-2.5 text-xs font-bold text-white shadow-xs transition-all hover:bg-black active:scale-[0.99]"
            >
              <ShieldCheck class="h-4 w-4" />
              <span>Buka Pengaturan Master Owner</span>
            </button>
          </div>
        </div>
      {/if}
    </div>
  </div>
</div>
