<script lang="ts">
  import {
    CheckCircle,
    Eye,
    AlertCircle,
    FileText,
    ExternalLink,
    RefreshCw,
    Search,
    ShieldCheck,
    X,
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
        inv.user?.name.toLowerCase().includes(q) ||
        inv.user?.email.toLowerCase().includes(q) ||
        inv.confirmation?.bank_account_name.toLowerCase().includes(q);

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

<div class="space-y-6 font-sans">
  <!-- Top Bar Title -->
  <div class="bg-white p-6 rounded-[22px] border border-[#d9d9dd] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-none">
    <div>
      <h1 class="text-xl font-medium text-[#212121] tracking-tight">Pusat Verifikasi Faktur &amp; Pembayaran</h1>
      <p class="text-xs text-[#616161] mt-0.5 font-normal">
        Validasi transfer manual 3-digit kode unik, periksa foto bukti transfer, dan lakukan persetujuan 1-klik.
      </p>
    </div>

    <button
      type="button"
      onclick={onRefresh}
      disabled={isLoading}
      class="inline-flex items-center space-x-1.5 px-4 py-2 bg-[#eeece7]/40 hover:bg-[#eeece7] text-[#212121] text-xs font-medium border border-[#d9d9dd] rounded-full transition-all cursor-pointer disabled:opacity-50 self-start sm:self-auto"
    >
      <RefreshCw class={`w-3.5 h-3.5 ${isLoading ? 'animate-spin' : ''}`} />
      <span>Segarkan Data</span>
    </button>
  </div>

  <!-- Filter & Search Tabs Bar -->
  <div class="bg-white p-4 sm:p-5 rounded-[22px] border border-[#d9d9dd] space-y-4 shadow-none">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Status Tabs -->
      <div class="flex flex-wrap gap-1 bg-[#eeece7]/60 p-1 rounded-full border border-[#d9d9dd]">
        <button
          type="button"
          onclick={() => (selectedStatus = 'PENDING_VERIFICATION')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all flex items-center space-x-1.5 cursor-pointer ${
            selectedStatus === 'PENDING_VERIFICATION'
              ? 'bg-[#17171c] text-white shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          <span>Menunggu Verifikasi</span>
          {#if pendingCount > 0}
            <span class="px-1.5 py-0.2 bg-[#ff7759] text-white text-[10px] font-mono font-medium rounded-full">
              {pendingCount}
            </span>
          {/if}
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'ALL')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'ALL'
              ? 'bg-[#17171c] text-white shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Semua ({invoices.length})
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'PAID')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'PAID'
              ? 'bg-[#17171c] text-white shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Lunas
        </button>

        <button
          type="button"
          onclick={() => (selectedStatus = 'UNPAID')}
          class={`px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
            selectedStatus === 'UNPAID'
              ? 'bg-[#17171c] text-white shadow-none'
              : 'text-[#616161] hover:text-[#212121]'
          }`}
        >
          Belum Bayar
        </button>
      </div>

      <!-- Search Box -->
      <div class="relative w-full md:w-72">
        <Search class="w-3.5 h-3.5 text-[#93939f] absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          type="text"
          bind:value={searchQuery}
          placeholder="Cari no. faktur / tenant / pengirim..."
          class="w-full pl-10 pr-3.5 py-2 text-xs bg-[#eeece7]/40 border border-[#d9d9dd] rounded-full focus:outline-hidden focus:border-[#17171c] focus:ring-2 focus:ring-[#4c6ee6]/20 text-[#212121] transition-all"
        />
      </div>
    </div>
  </div>

  <!-- Invoices Table -->
  <div class="bg-white border border-[#d9d9dd] rounded-[22px] overflow-hidden shadow-none">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="bg-[#eeece7]/50 border-b border-[#d9d9dd] text-[#616161] font-mono text-[11px]">
          <tr>
            <th class="py-3.5 px-4 font-medium">No. Faktur &amp; Tanggal</th>
            <th class="py-3.5 px-4 font-medium">Tenant / Pemilik</th>
            <th class="py-3.5 px-4 font-medium">Nominal Pokok</th>
            <th class="py-3.5 px-4 font-medium">Kode Unik</th>
            <th class="py-3.5 px-4 font-medium">Total Tagihan</th>
            <th class="py-3.5 px-4 font-medium">Bukti Bayar</th>
            <th class="py-3.5 px-4 font-medium">Status</th>
            <th class="py-3.5 px-4 text-right font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#d9d9dd]/60">
          {#if isLoading}
            <tr>
              <td colspan="8" class="py-12 text-center text-[#93939f]">
                <RefreshCw class="w-5 h-5 animate-spin mx-auto mb-2 text-[#1863dc]" />
                <span>Memuat data faktur...</span>
              </td>
            </tr>
          {:else if filteredInvoices.length === 0}
            <tr>
              <td colspan="8" class="py-12 text-center text-[#93939f]">
                <FileText class="w-6 h-6 mx-auto mb-2 text-[#93939f] opacity-50" />
                <span>Tidak ada faktur tagihan yang sesuai kriteria.</span>
              </td>
            </tr>
          {:else}
            {#each filteredInvoices as invoice (invoice.id)}
              <tr class="hover:bg-[#eeece7]/20 transition-colors">
                <!-- No. Faktur & Tanggal -->
                <td class="py-3.5 px-4">
                  <div class="font-mono font-medium text-[#1863dc]">{invoice.invoice_number}</div>
                  <div class="text-[11px] text-[#75758a]">
                    {new Date(invoice.created_at).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </div>
                </td>

                <!-- Tenant / Pemilik -->
                <td class="py-3.5 px-4">
                  <div class="font-medium text-[#212121]">{invoice.user?.name || 'Tanpa Nama'}</div>
                  <div class="text-[11px] text-[#75758a] font-mono">{invoice.user?.email}</div>
                  {#if invoice.user?.workspaces && invoice.user.workspaces.length > 0}
                    <div class="text-[10px] text-[#1863dc] mt-0.5 font-medium">
                      {invoice.user.workspaces.map((w) => w.name).join(', ')}
                    </div>
                  {/if}
                </td>

                <!-- Nominal Pokok -->
                <td class="py-3.5 px-4 font-mono text-[#616161]">
                  {formatRupiah(invoice.amount_base)}
                </td>

                <!-- 3-Digit Unique Code -->
                <td class="py-3.5 px-4">
                  <span class="inline-block px-2.5 py-0.5 bg-[#f1f5ff] text-[#1863dc] font-mono font-medium text-xs rounded-full">
                    +{invoice.unique_code}
                  </span>
                </td>

                <!-- Total Amount -->
                <td class="py-3.5 px-4 font-mono font-medium text-[#212121]">
                  {formatRupiah(invoice.total_amount)}
                </td>

                <!-- Bukti Bayar Info -->
                <td class="py-3.5 px-4">
                  {#if invoice.confirmation}
                    <div>
                      <div class="font-medium text-[#212121] text-[11px]">{invoice.confirmation.bank_account_name}</div>
                      <div class="text-[10px] text-[#003c33] font-mono">
                        TF: {formatRupiah(invoice.confirmation.transfer_amount)}
                      </div>
                    </div>
                  {:else}
                    <span class="text-[11px] text-[#93939f] italic">Belum diunggah</span>
                  {/if}
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4">
                  {#if invoice.status === 'PENDING_VERIFICATION'}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#eeece7] text-[#616161] text-[11px] font-medium rounded-full">
                      Menunggu Review
                    </span>
                  {:else if invoice.status === 'PAID'}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#edfce9] text-[#003c33] text-[11px] font-medium rounded-full">
                      Lunas Diverifikasi
                    </span>
                  {:else if invoice.status === 'UNPAID'}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#f1f5ff] text-[#1863dc] text-[11px] font-medium rounded-full">
                      Belum Dibayar
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#ffad9b]/20 text-[#b30000] text-[11px] font-medium rounded-full">
                      Kedaluwarsa
                    </span>
                  {/if}
                </td>

                <!-- Action Button -->
                <td class="py-3.5 px-4 text-right">
                  <button
                    type="button"
                    onclick={() => openInspection(invoice)}
                    class={`inline-flex items-center space-x-1.5 px-3.5 py-1.5 text-xs font-medium rounded-full transition-all cursor-pointer ${
                      invoice.status === 'PENDING_VERIFICATION'
                        ? 'bg-[#17171c] hover:bg-[#000000] text-white'
                        : 'bg-white hover:bg-[#eeece7] text-[#212121] border border-[#d9d9dd]'
                    }`}
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>{invoice.status === 'PENDING_VERIFICATION' ? 'Inspeksi & Verifikasi' : 'Detail'}</span>
                  </button>
                </td>
              </tr>
            {/each}
          {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Inspeksi Bukti Transfer -->
{#if inspectingInvoice}
  <div class="fixed inset-0 z-50 bg-[#17171c]/40 backdrop-blur-xs flex items-center justify-center p-4 font-sans">
    <div class="bg-white border border-[#d9d9dd] rounded-[22px] w-full max-w-2xl max-h-[90vh] flex flex-col shadow-none overflow-hidden animate-in fade-in zoom-in-95">
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-[#d9d9dd] bg-[#17171c] text-white">
        <div class="flex items-center space-x-2.5">
          <ShieldCheck class="w-5 h-5 text-[#edfce9]" />
          <div>
            <h2 class="text-sm font-medium tracking-tight">Inspeksi Bukti Pembayaran Faktur</h2>
            <p class="text-[11px] text-[#93939f] font-mono">{inspectingInvoice.invoice_number}</p>
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
          <div class="p-3.5 bg-[#edfce9] border border-[#edfce9] text-[#003c33] font-medium flex items-center space-x-2 rounded-[12px]">
            <CheckCircle class="w-4 h-4 shrink-0" />
            <span>{verificationSuccessMessage}</span>
          </div>
        {/if}

        {#if verificationErrorMessage}
          <div class="p-3.5 bg-[#ffad9b]/15 border border-[#ffad9b] text-[#b30000] font-medium flex items-center space-x-2 rounded-[12px]">
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{verificationErrorMessage}</span>
          </div>
        {/if}

        <!-- Invoice & Payment Metadata Grid -->
        <div class="grid grid-cols-2 gap-4 bg-[#eeece7]/30 p-4 rounded-[16px] border border-[#d9d9dd]">
          <div>
            <span class="text-[11px] text-[#75758a] uppercase font-medium">Tenant Pemilik:</span>
            <div class="font-medium text-[#212121] mt-0.5">{inspectingInvoice.user?.name}</div>
            <div class="font-mono text-[#75758a] text-[11px]">{inspectingInvoice.user?.email}</div>
          </div>

          <div>
            <span class="text-[11px] text-[#75758a] uppercase font-medium">Status Langganan Saat Ini:</span>
            <div class="mt-0.5">
              <span class="inline-block px-2.5 py-0.5 bg-white border border-[#d9d9dd] rounded-full font-mono font-medium text-[#212121]">
                {inspectingInvoice.user?.subscription_status || 'UNKNOWN'}
              </span>
            </div>
          </div>

          <div class="border-t border-[#d9d9dd] pt-3">
            <span class="text-[11px] text-[#75758a] uppercase font-medium">Nominal Wajib Transfer:</span>
            <div class="text-base font-medium font-mono text-[#1863dc] mt-0.5">
              {formatRupiah(inspectingInvoice.total_amount)}
            </div>
            <div class="text-[10px] text-[#75758a]">
              Pokok: {formatRupiah(inspectingInvoice.amount_base)} + Kode Unik: <span class="font-mono font-medium">+{inspectingInvoice.unique_code}</span>
            </div>
          </div>

          <div class="border-t border-[#d9d9dd] pt-3">
            <span class="text-[11px] text-[#75758a] uppercase font-medium">Nominal Ditransfer Tenant:</span>
            {#if inspectingInvoice.confirmation}
              <div class="text-base font-medium font-mono text-[#212121] mt-0.5">
                {formatRupiah(inspectingInvoice.confirmation.transfer_amount)}
              </div>
              <div class="text-[10px] text-[#75758a]">
                Pengirim: <span class="font-medium text-[#212121]">{inspectingInvoice.confirmation.bank_account_name}</span>
              </div>
            {:else}
              <div class="text-sm font-mono text-[#93939f] italic mt-0.5">Belum ada konfirmasi</div>
            {/if}
          </div>
        </div>

        <!-- Payment Proof Image Box -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium uppercase tracking-wider text-[#212121] text-[11px]">
              Lampiran Bukti Struk Transfer:
            </span>
            {#if inspectingInvoice.confirmation?.proof_image_url}
              <a
                href={inspectingInvoice.confirmation.proof_image_url}
                target="_blank"
                rel="noreferrer"
                class="text-[#1863dc] hover:underline inline-flex items-center space-x-1 text-[11px] font-medium"
              >
                <span>Buka Gambar Asli</span>
                <ExternalLink class="w-3 h-3" />
              </a>
            {/if}
          </div>

          {#if inspectingInvoice.confirmation?.proof_image_url}
            <div class="border border-[#d9d9dd] bg-[#17171c] p-2.5 rounded-[16px] flex items-center justify-center max-h-72 overflow-hidden">
              <img
                src={inspectingInvoice.confirmation.proof_image_url}
                alt="Bukti Transfer Pembayaran"
                class="max-h-64 object-contain rounded-[10px]"
                loading="lazy"
              />
            </div>
          {:else}
            <div class="p-8 border border-dashed border-[#d9d9dd] rounded-[16px] text-center text-[#93939f]">
              <AlertCircle class="w-6 h-6 mx-auto mb-1 text-[#93939f]" />
              <span>Tenant belum mengunggah foto struk bukti transfer untuk faktur ini.</span>
            </div>
          {/if}
        </div>
      </div>

      <!-- Modal Footer / Actions -->
      <div class="px-6 py-4 border-t border-[#d9d9dd] bg-[#eeece7]/30 flex items-center justify-between">
        <button
          type="button"
          onclick={closeInspection}
          class="px-4 py-2 bg-white hover:bg-[#eeece7] text-[#616161] text-xs font-medium border border-[#d9d9dd] rounded-full transition-all cursor-pointer"
        >
          Tutup
        </button>

        {#if inspectingInvoice.status === 'PENDING_VERIFICATION'}
          <button
            type="button"
            onclick={() => handleVerify(inspectingInvoice!.id)}
            disabled={isVerifying}
            class="px-5 py-2.5 bg-[#003c33] hover:bg-[#002822] text-white text-xs font-medium rounded-full transition-all inline-flex items-center space-x-2 cursor-pointer disabled:opacity-50 shadow-none"
          >
            {#if isVerifying}
              <RefreshCw class="w-3.5 h-3.5 animate-spin" />
              <span>Memproses Aktivasi...</span>
            {:else}
              <CheckCircle class="w-3.5 h-3.5" />
              <span>Verifikasi &amp; Aktifkan (+30 Hari)</span>
            {/if}
          </button>
        {:else if inspectingInvoice.status === 'PAID'}
          <div class="text-[#003c33] font-medium text-xs flex items-center space-x-1.5">
            <CheckCircle class="w-4 h-4" />
            <span>Faktur ini telah terverifikasi lunas.</span>
          </div>
        {/if}
      </div>
    </div>
  </div>
{/if}
