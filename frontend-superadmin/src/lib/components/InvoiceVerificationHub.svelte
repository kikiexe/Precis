<script lang="ts">
  import {
    CheckCircle,
    Eye,
    AlertCircle,
    RefreshCw,
    Search,
    ShieldCheck,
    X,
    Receipt,
  } from 'lucide-svelte';
  import type { InvoiceRecord } from '../types/superadmin';

  interface Props {
    invoices: InvoiceRecord[];
    isLoading: boolean;
    onVerifyInvoice: (invoiceId: string) => Promise<void>;
    onRefresh: () => void;
  }

  let { invoices, isLoading, onVerifyInvoice, onRefresh }: Props = $props();

  let selectedStatus = $state<'ALL' | 'PENDING_VERIFICATION' | 'PAID' | 'UNPAID'>('PENDING_VERIFICATION');
  let searchQuery = $state('');
  let inspectingInvoice = $state<InvoiceRecord | null>(null);
  let isVerifying = $state(false);
  let verificationSuccessMessage = $state<string | null>(null);
  let verificationErrorMessage = $state<string | null>(null);

  // Derived filtered invoices
  let filteredInvoices = $derived(
    invoices.filter((inv) => {
      const matchStatus = selectedStatus === 'ALL' || inv.status === selectedStatus;
      const q = searchQuery.toLowerCase();
      const matchQuery =
        !searchQuery ||
        inv.invoice_number.toLowerCase().includes(q) ||
        (inv.user?.name && inv.user.name.toLowerCase().includes(q)) ||
        (inv.user?.email && inv.user.email.toLowerCase().includes(q)) ||
        (inv.confirmation?.bank_account_name && inv.confirmation.bank_account_name.toLowerCase().includes(q));

      return matchStatus && matchQuery;
    })
  );

  let pendingCount = $derived(invoices.filter((inv) => inv.status === 'PENDING_VERIFICATION').length);

  function formatRupiah(amount: number): string {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0,
    }).format(amount);
  }

  async function handleVerify(invoiceId: string) {
    isVerifying = true;
    verificationSuccessMessage = null;
    verificationErrorMessage = null;

    try {
      await onVerifyInvoice(invoiceId);
      verificationSuccessMessage = 'Faktur berhasil diverifikasi! Masa aktif tenant telah diperpanjang 30 hari.';
      if (inspectingInvoice && inspectingInvoice.id === invoiceId) {
        inspectingInvoice = {
          ...inspectingInvoice,
          status: 'PAID',
        };
      }
    } catch (err: unknown) {
      if (err instanceof Error) {
        verificationErrorMessage = err.message;
      } else {
        verificationErrorMessage = 'Gagal memverifikasi faktur tagihan.';
      }
    } finally {
      isVerifying = false;
    }
  }

  function openInspection(invoice: InvoiceRecord) {
    inspectingInvoice = invoice;
    verificationSuccessMessage = null;
    verificationErrorMessage = null;
  }

  function closeInspection() {
    inspectingInvoice = null;
    verificationSuccessMessage = null;
    verificationErrorMessage = null;
  }
</script>

<div class="space-y-5 font-sans">
  <!-- Clean Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h1 class="text-xl font-semibold text-[#17171c] tracking-tight">Verifikasi Faktur</h1>
      <p class="text-xs text-[#75758a] mt-0.5">
        Validasi transfer manual, periksa foto struk pembayaran, dan lakukan persetujuan 1-klik.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-[#f4f4f4] text-[#17171c] text-xs font-medium border border-[#d9d9dd] rounded-lg transition-all cursor-pointer disabled:opacity-50 self-start sm:self-auto shadow-xs"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan</span>
    </button>
  </div>

  <!-- Unified Main Container Card -->
  <div class="bg-white border border-[#d9d9dd] rounded-xl shadow-xs overflow-hidden">
    <!-- Toolbar: Filter Pills & Search -->
    <div class="p-3.5 sm:p-4 border-b border-[#e5e5e5] bg-[#fafafa] flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Status Tabs -->
      <div class="flex flex-wrap items-center gap-1">
        <button
          type="button"
          onclick={() => (selectedStatus = 'PENDING_VERIFICATION')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all flex items-center gap-1.5 cursor-pointer ${
            selectedStatus === 'PENDING_VERIFICATION'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          <span>Menunggu Verifikasi</span>
          {#if pendingCount > 0}
            <span class="px-1.5 py-0.2 bg-[#e5484d] text-white text-[10px] font-mono font-bold rounded-md">
              {pendingCount}
            </span>
          {/if}
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'ALL'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Semua ({invoices.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'PAID')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'PAID'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Lunas
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'UNPAID')}
          class={`px-3 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
            selectedStatus === 'UNPAID'
              ? 'bg-[#17171c] text-white'
              : 'text-[#616161] hover:text-[#17171c] hover:bg-[#eaeaea]'
          }`}
        >
          Belum Bayar
        </button>
      </div>

      <!-- Search Box -->
      <div class="relative w-full md:w-64">
        <Search class="w-3.5 h-3.5 text-[#93939f] absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari no. faktur / tenant..."
          class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-[#d9d9dd] rounded-lg focus:outline-hidden focus:border-[#17171c] text-[#17171c] transition-all"
        />
      </div>
    </div>

    <!-- Content State: Loading / Empty / Data -->
    {#if isLoading}
      <div class="py-16 text-center text-[#93939f] space-y-2">
        <RefreshCw class="w-5 h-5 animate-spin mx-auto text-[#17171c]" />
        <p class="text-xs">Memuat data faktur tagihan...</p>
      </div>
    {:else if filteredInvoices.length === 0}
      <div class="py-16 text-center text-[#93939f] space-y-2">
        <Receipt class="w-8 h-8 mx-auto text-[#93939f] opacity-40" />
        <p class="text-xs font-medium text-[#17171c]">Tidak ada faktur yang sesuai filter</p>
        <p class="text-[11px] text-[#75758a]">Faktur tagihan baru akan otomatis muncul di sini saat tenant membuat pesanan.</p>
      </div>
    {:else}
      <!-- Desktop Table View -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead class="bg-[#fafafa] border-b border-[#e5e5e5] text-[#75758a] font-medium text-[11px]">
            <tr>
              <th class="py-3 px-4">No. Faktur &amp; Tanggal</th>
              <th class="py-3 px-4">Tenant / Pemilik</th>
              <th class="py-3 px-4 text-right">Nominal Pokok</th>
              <th class="py-3 px-4 text-center">Kode Unik</th>
              <th class="py-3 px-4 text-right">Total Tagihan</th>
              <th class="py-3 px-4">Bukti Bayar</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#e5e5e5]">
            {#each filteredInvoices as invoice (invoice.id)}
              <tr class="hover:bg-[#f9fafb] transition-colors">
                <!-- No. Faktur & Tanggal -->
                <td class="py-3.5 px-4">
                  <div class="font-mono font-medium text-[#17171c]">{invoice.invoice_number}</div>
                  <div class="text-[10px] text-[#75758a] font-mono mt-0.5">
                    {new Date(invoice.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                    })}
                  </div>
                </td>

                <!-- Tenant / Pemilik -->
                <td class="py-3.5 px-4">
                  <div class="font-medium text-[#17171c]">{invoice.user?.name || 'Tanpa Nama'}</div>
                  <div class="text-[10px] text-[#75758a] font-mono">{invoice.user?.email}</div>
                  {#if invoice.user?.workspaces && invoice.user.workspaces.length > 0}
                    <div class="text-[10px] text-[#1863dc] mt-0.5 font-medium">
                      {invoice.user.workspaces.map((w) => w.name).join(', ')}
                    </div>
                  {/if}
                </td>

                <!-- Nominal Pokok -->
                <td class="py-3.5 px-4 text-right font-mono text-[#616161]">
                  {formatRupiah(invoice.amount_base)}
                </td>

                <!-- 3-Digit Unique Code -->
                <td class="py-3.5 px-4 text-center">
                  <span class="inline-block px-1.5 py-0.2 bg-[#f1f5ff] text-[#1863dc] font-mono font-medium text-[11px] rounded-md">
                    +{invoice.unique_code}
                  </span>
                </td>

                <!-- Total Amount -->
                <td class="py-3.5 px-4 text-right font-mono font-semibold text-[#17171c]">
                  {formatRupiah(invoice.total_amount)}
                </td>

                <!-- Bukti Bayar Info -->
                <td class="py-3.5 px-4">
                  {#if invoice.confirmation}
                    <div>
                      <div class="font-medium text-[#17171c] text-[11px] truncate max-w-35">
                        {invoice.confirmation.bank_account_name}
                      </div>
                      <div class="text-[10px] text-[#16a34a] font-mono font-medium">
                        TF: {formatRupiah(invoice.confirmation.transfer_amount)}
                      </div>
                    </div>
                  {:else}
                    <span class="text-[10px] text-[#93939f] italic">Belum diunggah</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4 text-center">
                  {#if invoice.status === 'PENDING_VERIFICATION'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#fee2e2] text-[#991b1b] text-[10px] font-semibold rounded-md">
                      Menunggu Review
                    </span>
                  {:else if invoice.status === 'PAID'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#edfce9] text-[#003c33] text-[10px] font-semibold rounded-md">
                      Lunas
                    </span>
                  {:else if invoice.status === 'UNPAID'}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#f1f5ff] text-[#1863dc] text-[10px] font-semibold rounded-md">
                      Belum Bayar
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-0.5 bg-[#f4f4f5] text-[#71717a] text-[10px] font-semibold rounded-md">
                      Kedaluwarsa
                    </span>
                  {/if}
                </td>

                <!-- Action Button -->
                <td class="py-3.5 px-4 text-right">
                  <button
                    type="button"
                    onclick={() => openInspection(invoice)}
                    class={`inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md transition-all cursor-pointer ${
                      invoice.status === 'PENDING_VERIFICATION'
                        ? 'bg-[#17171c] hover:bg-black text-white'
                        : 'bg-white hover:bg-[#f4f4f4] text-[#17171c] border border-[#d9d9dd]'
                    }`}
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>{invoice.status === 'PENDING_VERIFICATION' ? 'Verifikasi' : 'Detail'}</span>
                  </button>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      <!-- Mobile Clean Card View -->
      <div class="md:hidden divide-y divide-[#e5e5e5]">
        {#each filteredInvoices as invoice (invoice.id)}
          <div class="p-4 space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="font-mono font-medium text-xs text-[#17171c]">{invoice.invoice_number}</div>
                <div class="text-xs text-[#75758a]">{invoice.user?.name || 'Tanpa Nama'} ({invoice.user?.email})</div>
              </div>

              {#if invoice.status === 'PENDING_VERIFICATION'}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#fee2e2] text-[#991b1b] text-[10px] font-semibold rounded-md shrink-0">
                  Menunggu Review
                </span>
              {:else if invoice.status === 'PAID'}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#edfce9] text-[#003c33] text-[10px] font-semibold rounded-md shrink-0">
                  Lunas
                </span>
              {:else if invoice.status === 'UNPAID'}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#f1f5ff] text-[#1863dc] text-[10px] font-semibold rounded-md shrink-0">
                  Belum Bayar
                </span>
              {:else}
                <span class="inline-flex items-center px-2 py-0.5 bg-[#f4f4f5] text-[#71717a] text-[10px] font-semibold rounded-md shrink-0">
                  Kedaluwarsa
                </span>
              {/if}
            </div>

            <!-- Total Amount & Meta Grid -->
            <div class="grid grid-cols-2 gap-2 bg-[#fafafa] p-2.5 rounded-lg border border-[#e5e5e5] text-xs">
              <div>
                <div class="text-[10px] text-[#75758a]">Total Tagihan</div>
                <div class="font-mono font-bold text-[#17171c] text-sm mt-0.5">{formatRupiah(invoice.total_amount)}</div>
                <div class="text-[10px] text-[#75758a] font-mono">Kode Unik: +{invoice.unique_code}</div>
              </div>

              <div>
                <div class="text-[10px] text-[#75758a]">Pengirim Rekening</div>
                {#if invoice.confirmation}
                  <div class="font-medium text-[#17171c] mt-0.5 truncate">{invoice.confirmation.bank_account_name}</div>
                  <div class="text-[10px] text-[#16a34a] font-mono">TF: {formatRupiah(invoice.confirmation.transfer_amount)}</div>
                {:else}
                  <div class="text-[#93939f] italic mt-0.5">Belum diunggah</div>
                {/if}
              </div>
            </div>

            <button
              type="button"
              onclick={() => openInspection(invoice)}
              class={`w-full py-2 text-xs font-medium rounded-lg flex items-center justify-center gap-1.5 transition-all ${
                invoice.status === 'PENDING_VERIFICATION'
                  ? 'bg-[#17171c] text-white'
                  : 'bg-white border border-[#d9d9dd] text-[#17171c] hover:bg-[#f4f4f4]'
              }`}
            >
              <Eye class="w-3.5 h-3.5" />
              <span>{invoice.status === 'PENDING_VERIFICATION' ? 'Inspeksi & Verifikasi' : 'Lihat Detail'}</span>
            </button>
          </div>
        {/each}
      </div>
    {/if}
  </div>
</div>

<!-- Modal Inspeksi Bukti Transfer -->
{#if inspectingInvoice}
  <div class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-xl overflow-hidden">
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-[#e5e5e5] bg-[#17171c] text-white">
        <div class="flex items-center gap-2.5">
          <ShieldCheck class="w-5 h-5 text-[#edfce9]" />
          <div>
            <h2 class="text-sm font-semibold tracking-tight">Inspeksi Bukti Pembayaran</h2>
            <p class="text-[10px] text-[#93939f] font-mono">{inspectingInvoice.invoice_number}</p>
          </div>
        </div>
        <button
          type="button"
          onclick={closeInspection}
          class="text-[#93939f] hover:text-white transition-colors cursor-pointer p-1"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-4 text-xs">
        {#if verificationSuccessMessage}
          <div class="p-3 bg-[#edfce9] border border-[#bbf7d0] text-[#003c33] font-medium flex items-center gap-2 rounded-xl">
            <CheckCircle class="w-4 h-4 shrink-0 text-[#16a34a]" />
            <span>{verificationSuccessMessage}</span>
          </div>
        {/if}

        {#if verificationErrorMessage}
          <div class="p-3 bg-[#ffefef] border border-[#fecaca] text-[#e5484d] font-medium flex items-center gap-2 rounded-xl">
            <AlertCircle class="w-4 h-4 shrink-0 text-[#dc2626]" />
            <span>{verificationErrorMessage}</span>
          </div>
        {/if}

        <!-- Invoice Summary Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-[#fafafa] p-4 rounded-xl border border-[#e5e5e5]">
          <div class="space-y-2">
            <div>
              <span class="text-[10px] font-mono uppercase text-[#75758a]">Pemilik Tenant:</span>
              <div class="font-medium text-[#17171c] mt-0.5">{inspectingInvoice.user?.name || 'Tanpa Nama'}</div>
              <div class="text-[11px] text-[#75758a] font-mono">{inspectingInvoice.user?.email}</div>
            </div>

            <div>
              <span class="text-[10px] font-mono uppercase text-[#75758a]">Nominal Tagihan:</span>
              <div class="font-bold text-sm text-[#17171c] font-mono mt-0.5">
                {formatRupiah(inspectingInvoice.total_amount)}
              </div>
              <div class="text-[10px] text-[#75758a] font-mono">
                Pokok: {formatRupiah(inspectingInvoice.amount_base)} | Kode: +{inspectingInvoice.unique_code}
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <div>
              <span class="text-[10px] font-mono uppercase text-[#75758a]">Status Faktur:</span>
              <div class="mt-0.5">
                {#if inspectingInvoice.status === 'PAID'}
                  <span class="px-2 py-0.5 bg-[#edfce9] text-[#003c33] font-mono text-[10px] font-semibold rounded-md">
                    LUNAS TERVERIFIKASI
                  </span>
                {:else if inspectingInvoice.status === 'PENDING_VERIFICATION'}
                  <span class="px-2 py-0.5 bg-[#fee2e2] text-[#991b1b] font-mono text-[10px] font-semibold rounded-md">
                    MENUNGGU VERIFIKASI
                  </span>
                {:else}
                  <span class="px-2 py-0.5 bg-[#f1f5ff] text-[#1863dc] font-mono text-[10px] font-semibold rounded-md">
                    {inspectingInvoice.status}
                  </span>
                {/if}
              </div>
            </div>

            <div>
              <span class="text-[10px] font-mono uppercase text-[#75758a]">Pengirim Rekening:</span>
              {#if inspectingInvoice.confirmation}
                <div class="font-medium text-[#17171c] mt-0.5">
                  {inspectingInvoice.confirmation.bank_account_name}
                </div>
                <div class="text-[11px] text-[#16a34a] font-mono font-medium">
                  Jumlah: {formatRupiah(inspectingInvoice.confirmation.transfer_amount)}
                </div>
              {:else}
                <div class="text-[#93939f] italic mt-0.5">Belum ada konfirmasi bayar</div>
              {/if}
            </div>
          </div>
        </div>

        <!-- Proof Image Preview -->
        {#if inspectingInvoice.confirmation && inspectingInvoice.confirmation.proof_image_url}
          <div class="space-y-1.5">
            <span class="text-[10px] font-mono uppercase text-[#75758a]">Foto Bukti Struk Transfer:</span>
            <div class="border border-[#e5e5e5] rounded-xl overflow-hidden bg-[#fafafa] p-2 text-center">
              <img
                src={inspectingInvoice.confirmation.proof_image_url}
                alt="Bukti Transfer"
                class="max-h-72 mx-auto rounded-lg object-contain border border-[#e5e5e5]"
              />
            </div>
          </div>
        {/if}
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3.5 border-t border-[#e5e5e5] bg-[#fafafa] flex items-center justify-between gap-3">
        <button
          type="button"
          onclick={closeInspection}
          class="px-4 py-2 bg-white hover:bg-[#f4f4f4] text-[#616161] text-xs font-medium border border-[#d9d9dd] rounded-lg transition-all cursor-pointer"
        >
          Tutup
        </button>

        {#if inspectingInvoice.status === 'PENDING_VERIFICATION'}
          <button
            type="button"
            onclick={() => handleVerify(inspectingInvoice!.id)}
            disabled={isVerifying}
            class="px-4 py-2 bg-[#17171c] hover:bg-black text-white text-xs font-medium rounded-lg transition-all cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
          >
            {#if isVerifying}
              <RefreshCw class="w-3.5 h-3.5 animate-spin" />
              <span>Memproses...</span>
            {:else}
              <CheckCircle class="w-3.5 h-3.5 text-[#bbf7d0]" />
              <span>Setujui Pembayaran &amp; Aktifkan</span>
            {/if}
          </button>
        {/if}
      </div>
    </div>
  </div>
{/if}
